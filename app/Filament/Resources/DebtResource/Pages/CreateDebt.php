<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Services\WhatsAppService;
use App\Models\CashTransaction;
use App\Models\TransactionCategory;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validasi saldo KAS
        $cashBalance = DebtResource::getCurrentCashBalance();
        if ($data['amount'] > $cashBalance) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body("Jumlah hutang (Rp " . number_format($data['amount'], 2) . ") melebihi saldo KAS saat ini (Rp " . number_format($cashBalance, 2) . "). Hutang tidak dapat dibuat.")
                ->persistent()
                ->send();
            
            throw new \Exception("Jumlah hutang melebihi saldo KAS.");
        }

        $data['created_by'] = Auth::id();
        $data['paid_amount'] = 0;
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $debt = $this->record;
            
            // Buat cash transaction EXPENSE saat hutang dibuat
            // Karena hutang = menggunakan dana dari KAS
            try {
                // Cari kategori "Hutang" atau "Lain-lain" untuk expense
                $category = TransactionCategory::where('type', 'expense')
                    ->where(function ($query) {
                        $query->where('name', 'Hutang')
                            ->orWhere('name', 'Lain-lain');
                    })
                    ->first();
                
                // Jika kategori "Hutang" tidak ada, buat kategori baru
                if (!$category || $category->name !== 'Hutang') {
                    $category = TransactionCategory::firstOrCreate(
                        ['name' => 'Hutang', 'type' => 'expense'],
                        ['description' => 'Pengeluaran untuk hutang']
                    );
                }
                
                $cashTransaction = CashTransaction::create([
                    'date' => now(),
                    'type' => 'expense',
                    'amount' => $debt->amount,
                    'description' => "Hutang kepada {$debt->creditor_display_name}" . ($debt->description ? " - {$debt->description}" : ''),
                    'category_id' => $category->id,
                    'created_by' => Auth::id(),
                ]);
                
                // Simpan cash_transaction_id ke debt
                $debt->cash_transaction_id = $cashTransaction->id;
                $debt->save();
                
                Log::info("Cash transaction created for new debt", [
                    'debt_id' => $debt->id,
                    'cash_transaction_id' => $cashTransaction->id,
                    'amount' => $debt->amount,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create cash transaction for debt", [
                    'debt_id' => $debt->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail debt creation if cash transaction fails
                // But show warning to user
                Notification::make()
                    ->warning()
                    ->title('Peringatan')
                    ->body('Hutang berhasil dibuat, tetapi transaksi KAS gagal dibuat. Silakan periksa saldo KAS.')
                    ->send();
            }
            
            // Send WhatsApp notification for new debt
            try {
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
                
                // Only send notification if contact (phone number) is available
                if (!empty($contact) && preg_match('/^[0-9+]/', $contact)) {
                    $whatsapp = new WhatsAppService();
                    
                    // Format message for new debt notification
                    $amount = number_format($debt->amount, 0, ',', '.');
                    $dueDate = Carbon::parse($debt->due_date)->format('d F Y');
                    $daysUntilDue = Carbon::parse($debt->due_date)->diffInDays(now(), false);
                    
                    $message = "📋 *Notifikasi Hutang Baru*\n\n";
                    $message .= "Kepada: {$debt->creditor_display_name}\n";
                    $message .= "Jumlah Hutang: Rp {$amount}\n";
                    $message .= "Jatuh Tempo: {$dueDate}\n";
                    
                    if ($daysUntilDue > 0) {
                        $message .= "Sisa Hari: {$daysUntilDue} hari\n";
                    } elseif ($daysUntilDue == 0) {
                        $message .= "⚠️ Jatuh tempo hari ini\n";
                    } else {
                        $message .= "⚠️ Terlambat: " . abs($daysUntilDue) . " hari\n";
                    }
                    
                    if ($debt->description) {
                        $message .= "\nCatatan: {$debt->description}";
                    }
                    
                    $message .= "\n\nTerima kasih.";
                    
                    // Send WhatsApp message
                    $result = $whatsapp->sendMessage($contact, $message);
                    
                    if ($result['success']) {
                        Log::info("Debt creation WhatsApp notification sent", [
                            'debt_id' => $debt->id,
                            'creditor' => $debt->creditor_display_name,
                            'contact' => $contact,
                            'amount' => $debt->amount,
                        ]);
                    } else {
                        throw new \Exception($result['message'] ?? 'Failed to send notification');
                    }
                } else {
                    // No valid phone number, log warning but don't fail
                    Log::warning("Debt created but WhatsApp notification skipped: No valid phone number", [
                        'debt_id' => $debt->id,
                        'creditor' => $debt->creditor_display_name,
                        'creditor_type' => $debt->creditor_type,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the creation
                Log::error("Failed to send WhatsApp notification for new debt", [
                    'debt_id' => $this->record->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                
                // Show warning notification to user
                Notification::make()
                    ->warning()
                    ->title('Hutang Berhasil Dibuat')
                    ->body('Hutang berhasil dibuat, tetapi gagal mengirim notifikasi WhatsApp. Pastikan kreditur memiliki nomor telepon yang valid.')
                    ->send();
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
