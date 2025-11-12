<?php

namespace App\Console\Commands;

use App\Models\Receivable;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReceivableReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receivables:send-reminders {--days-before=3 : Send reminder X days before due date} {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for receivables that are due soon or overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysBefore = (int) $this->option('days-before');
        $isDryRun = $this->option('dry-run');
        
        $this->info('=== Send Receivable Reminders ===');
        $this->info('Date: ' . now()->format('d F Y H:i:s'));
        $this->info("Days before due date: {$daysBefore}");
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No reminders will be sent');
        }
        
        $this->newLine();
        
        try {
            $today = Carbon::now()->startOfDay();
            $reminderDate = $today->copy()->addDays($daysBefore);
            
            // Get receivables that are:
            // 1. Not fully paid (status != 'paid')
            // 2. Due date is today or within reminder period, OR overdue
            $receivables = Receivable::whereIn('status', ['pending', 'partial', 'overdue'])
                ->where(function ($query) use ($today, $reminderDate) {
                    $query->whereBetween('due_date', [$today, $reminderDate])
                        ->orWhere('due_date', '<', $today);
                })
                ->with(['debtorCustomer', 'debtorUser'])
                ->get();
            
            if ($receivables->isEmpty()) {
                $this->info('✅ No receivables require reminders.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$receivables->count()} receivable(s) requiring reminders:");
            $this->newLine();
            
            // Display table
            $tableData = [];
            foreach ($receivables as $receivable) {
                $daysUntilDue = Carbon::parse($receivable->due_date)->diffInDays(now(), false);
                $statusText = $daysUntilDue > 0 ? "Due in {$daysUntilDue} days" : ($daysUntilDue == 0 ? "Due today" : "Overdue " . abs($daysUntilDue) . " days");
                
                $tableData[] = [
                    'ID' => $receivable->id,
                    'Debtor' => $receivable->debtor_display_name,
                    'Amount' => 'Rp ' . number_format($receivable->amount, 0, ',', '.'),
                    'Paid' => 'Rp ' . number_format($receivable->paid_amount, 0, ',', '.'),
                    'Remaining' => 'Rp ' . number_format($receivable->remaining_amount, 0, ',', '.'),
                    'Due Date' => $receivable->due_date->format('d M Y'),
                    'Status' => $statusText,
                ];
            }
            
            $this->table(
                ['ID', 'Debtor', 'Amount', 'Paid', 'Remaining', 'Due Date', 'Status'],
                $tableData
            );
            
            if ($isDryRun) {
                $this->newLine();
                $this->info("🔍 DRY RUN: {$receivables->count()} reminder(s) would be sent");
                return Command::SUCCESS;
            }
            
            // Send reminders
            $sent = 0;
            $failed = 0;
            $errors = [];
            
            $this->newLine();
            $this->info('Sending reminders...');
            
            $whatsAppService = new WhatsAppService();
            
            foreach ($receivables as $receivable) {
                try {
                    // Update status to overdue if due date has passed
                    if ($receivable->due_date < $today && $receivable->status !== 'overdue') {
                        $receivable->updateStatus();
                    }
                    
                    // Get debtor contact (prefer phone number for WhatsApp)
                    $contact = null;
                    
                    // If debtor_type is 'customer', get phone from customer relationship
                    if ($receivable->debtor_type === 'customer' && $receivable->debtorCustomer) {
                        $contact = $receivable->debtorCustomer->phone;
                    }
                    // If debtor_type is 'user', get phone from user relationship
                    elseif ($receivable->debtor_type === 'user' && $receivable->debtorUser) {
                        $contact = $receivable->debtorUser->phone;
                    }
                    
                    // Fallback to debtor_contact field if no phone from relationship
                    if (empty($contact)) {
                        $contact = $receivable->debtor_contact;
                        // Check if it's a phone number (starts with digit or +)
                        if (!empty($contact) && !preg_match('/^[0-9+]/', $contact)) {
                            $contact = null; // Not a phone number, skip
                        }
                    }
                    
                    // Validate contact is a phone number (WhatsApp requires phone number)
                    if (empty($contact) || !preg_match('/^[0-9+]/', $contact)) {
                        $this->warn("  ⚠️  Receivable #{$receivable->id} - No valid phone number for debtor");
                        Log::warning("Receivable reminder skipped: No valid phone number", [
                            'receivable_id' => $receivable->id,
                            'debtor' => $receivable->debtor_display_name,
                            'contact' => $contact,
                        ]);
                        continue;
                    }
                    
                    // Format message
                    $daysUntilDue = Carbon::parse($receivable->due_date)->diffInDays(now(), false);
                    $remaining = number_format($receivable->remaining_amount, 0, ',', '.');
                    $dueDate = $receivable->due_date->format('d F Y');
                    
                    // daysUntilDue: positive = days until due, negative = days overdue
                    if ($daysUntilDue > 0) {
                        // Still due in the future
                        $message = "📋 *Reminder Piutang*\n\n";
                        $message .= "Kepada: {$receivable->debtor_display_name}\n";
                        $message .= "Jumlah Piutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate}\n";
                        $message .= "Sisa Hari: {$daysUntilDue} hari\n\n";
                        $message .= "Mohon persiapkan pembayaran piutang sebelum jatuh tempo.";
                    } elseif ($daysUntilDue == 0) {
                        // Due today
                        $message = "⚠️ *Piutang Jatuh Tempo Hari Ini*\n\n";
                        $message .= "Kepada: {$receivable->debtor_display_name}\n";
                        $message .= "Jumlah Piutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate} (HARI INI)\n\n";
                        $message .= "Mohon segera lakukan pembayaran piutang.";
                    } else {
                        // Overdue (daysUntilDue is negative)
                        $daysOverdue = abs($daysUntilDue);
                        $message = "🚨 *Piutang Terlambat*\n\n";
                        $message .= "Kepada: {$receivable->debtor_display_name}\n";
                        $message .= "Jumlah Piutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate}\n";
                        $message .= "Terlambat: {$daysOverdue} hari\n\n";
                        $message .= "Mohon segera lakukan pembayaran piutang yang telah terlambat.";
                    }
                    
                    if ($receivable->description) {
                        $message .= "\n\nCatatan: {$receivable->description}";
                    }
                    
                    // Send WhatsApp message
                    $result = $whatsAppService->sendMessage($contact, $message);
                    
                    if ($result['success']) {
                        $sent++;
                        $this->line("  ✅ Receivable #{$receivable->id} - Reminder sent to {$receivable->debtor_display_name}");
                        
                        Log::info("Receivable reminder sent", [
                            'receivable_id' => $receivable->id,
                            'debtor' => $receivable->debtor_display_name,
                            'contact' => $contact,
                            'remaining_amount' => $receivable->remaining_amount,
                            'due_date' => $receivable->due_date->format('Y-m-d'),
                        ]);
                    } else {
                        throw new \Exception($result['message'] ?? 'Failed to send reminder');
                    }
                    
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Receivable #{$receivable->id}: {$e->getMessage()}";
                    $this->error("  ❌ Receivable #{$receivable->id} - {$e->getMessage()}");
                    
                    Log::error("Failed to send receivable reminder", [
                        'receivable_id' => $receivable->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            $this->newLine();
            $this->info('=== Summary ===');
            $this->line("✅ Sent: {$sent}");
            
            if ($failed > 0) {
                $this->error("❌ Failed: {$failed}");
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }
            }
            
            Log::info("Receivable reminders sent", [
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors,
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error sending receivable reminders: {$e->getMessage()}");
            Log::error("Error sending receivable reminders: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
}
