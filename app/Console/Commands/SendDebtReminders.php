<?php

namespace App\Console\Commands;

use App\Models\Debt;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDebtReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debts:send-reminders {--days-before=3 : Send reminder X days before due date} {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for debts that are due soon or overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysBefore = (int) $this->option('days-before');
        $isDryRun = $this->option('dry-run');
        
        $this->info('=== Send Debt Reminders ===');
        $this->info('Date: ' . now()->format('d F Y H:i:s'));
        $this->info("Days before due date: {$daysBefore}");
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No reminders will be sent');
        }
        
        $this->newLine();
        
        try {
            $today = Carbon::now()->startOfDay();
            $reminderDate = $today->copy()->addDays($daysBefore);
            
            // Get debts that are:
            // 1. Not fully paid (status != 'paid')
            // 2. Due date is today or within reminder period, OR overdue
            $debts = Debt::whereIn('status', ['pending', 'partial', 'overdue'])
                ->where(function ($query) use ($today, $reminderDate) {
                    $query->whereBetween('due_date', [$today, $reminderDate])
                        ->orWhere('due_date', '<', $today);
                })
                ->with(['creditorUser'])
                ->get();
            
            if ($debts->isEmpty()) {
                $this->info('✅ No debts require reminders.');
                return Command::SUCCESS;
            }
            
            $this->info("Found {$debts->count()} debt(s) requiring reminders:");
            $this->newLine();
            
            // Display table
            $tableData = [];
            foreach ($debts as $debt) {
                $daysUntilDue = Carbon::parse($debt->due_date)->diffInDays(now(), false);
                $statusText = $daysUntilDue > 0 ? "Due in {$daysUntilDue} days" : ($daysUntilDue == 0 ? "Due today" : "Overdue " . abs($daysUntilDue) . " days");
                
                $tableData[] = [
                    'ID' => $debt->id,
                    'Creditor' => $debt->creditor_display_name,
                    'Amount' => 'Rp ' . number_format($debt->amount, 0, ',', '.'),
                    'Paid' => 'Rp ' . number_format($debt->paid_amount, 0, ',', '.'),
                    'Remaining' => 'Rp ' . number_format($debt->remaining_amount, 0, ',', '.'),
                    'Due Date' => $debt->due_date->format('d M Y'),
                    'Status' => $statusText,
                ];
            }
            
            $this->table(
                ['ID', 'Creditor', 'Amount', 'Paid', 'Remaining', 'Due Date', 'Status'],
                $tableData
            );
            
            if ($isDryRun) {
                $this->newLine();
                $this->info("🔍 DRY RUN: {$debts->count()} reminder(s) would be sent");
                return Command::SUCCESS;
            }
            
            // Send reminders
            $sent = 0;
            $failed = 0;
            $errors = [];
            
            $this->newLine();
            $this->info('Sending reminders...');
            
            $whatsAppService = new WhatsAppService();
            
            foreach ($debts as $debt) {
                try {
                    // Update status to overdue if due date has passed
                    if ($debt->due_date < $today && $debt->status !== 'overdue') {
                        $debt->updateStatus();
                    }
                    
                    // Get creditor contact (prefer phone number for WhatsApp)
                    $contact = null;
                    
                    // If creditor_type is 'user', get phone from user relationship
                    if ($debt->creditor_type === 'user' && $debt->creditorUser) {
                        $contact = $debt->creditorUser->phone;
                    }
                    
                    // Fallback to creditor_contact field if no phone from user
                    if (empty($contact)) {
                        $contact = $debt->creditor_contact;
                        // Check if it's a phone number (starts with digit or +)
                        if (!empty($contact) && !preg_match('/^[0-9+]/', $contact)) {
                            $contact = null; // Not a phone number, skip
                        }
                    }
                    
                    // Validate contact is a phone number (WhatsApp requires phone number)
                    if (empty($contact) || !preg_match('/^[0-9+]/', $contact)) {
                        $this->warn("  ⚠️  Debt #{$debt->id} - No valid phone number for creditor");
                        Log::warning("Debt reminder skipped: No valid phone number", [
                            'debt_id' => $debt->id,
                            'creditor' => $debt->creditor_display_name,
                            'contact' => $contact,
                        ]);
                        continue;
                    }
                    
                    // Format message
                    $daysUntilDue = Carbon::parse($debt->due_date)->diffInDays(now(), false);
                    $remaining = number_format($debt->remaining_amount, 0, ',', '.');
                    $dueDate = $debt->due_date->format('d F Y');
                    
                    // daysUntilDue: positive = days until due, negative = days overdue
                    if ($daysUntilDue > 0) {
                        // Still due in the future
                        $message = "📋 *Reminder Hutang*\n\n";
                        $message .= "Kepada: {$debt->creditor_display_name}\n";
                        $message .= "Jumlah Hutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate}\n";
                        $message .= "Sisa Hari: {$daysUntilDue} hari\n\n";
                        $message .= "Mohon persiapkan pembayaran hutang sebelum jatuh tempo.";
                    } elseif ($daysUntilDue == 0) {
                        // Due today
                        $message = "⚠️ *Hutang Jatuh Tempo Hari Ini*\n\n";
                        $message .= "Kepada: {$debt->creditor_display_name}\n";
                        $message .= "Jumlah Hutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate} (HARI INI)\n\n";
                        $message .= "Mohon segera lakukan pembayaran hutang.";
                    } else {
                        // Overdue (daysUntilDue is negative)
                        $daysOverdue = abs($daysUntilDue);
                        $message = "🚨 *Hutang Terlambat*\n\n";
                        $message .= "Kepada: {$debt->creditor_display_name}\n";
                        $message .= "Jumlah Hutang: Rp {$remaining}\n";
                        $message .= "Jatuh Tempo: {$dueDate}\n";
                        $message .= "Terlambat: {$daysOverdue} hari\n\n";
                        $message .= "Mohon segera lakukan pembayaran hutang yang telah terlambat.";
                    }
                    
                    if ($debt->description) {
                        $message .= "\n\nCatatan: {$debt->description}";
                    }
                    
                    // Send WhatsApp message
                    $result = $whatsAppService->sendMessage($contact, $message);
                    
                    if ($result['success']) {
                        $sent++;
                        $this->line("  ✅ Debt #{$debt->id} - Reminder sent to {$debt->creditor_display_name}");
                        
                        Log::info("Debt reminder sent", [
                            'debt_id' => $debt->id,
                            'creditor' => $debt->creditor_display_name,
                            'contact' => $contact,
                            'remaining_amount' => $debt->remaining_amount,
                            'due_date' => $debt->due_date->format('Y-m-d'),
                        ]);
                    } else {
                        throw new \Exception($result['message'] ?? 'Failed to send reminder');
                    }
                    
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Debt #{$debt->id}: {$e->getMessage()}";
                    $this->error("  ❌ Debt #{$debt->id} - {$e->getMessage()}");
                    
                    Log::error("Failed to send debt reminder", [
                        'debt_id' => $debt->id,
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
            
            Log::info("Debt reminders sent", [
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors,
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error sending debt reminders: {$e->getMessage()}");
            Log::error("Error sending debt reminders: {$e->getMessage()}");
            
            return Command::FAILURE;
        }
    }
}
