<?php

namespace App\Filament\Resources\MikrotikNetwatchResource\Pages;

use App\Filament\Resources\MikrotikNetwatchResource;
use App\Services\MikrotikNetwatchService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMikrotikNetwatch extends EditRecord
{
    protected static string $resource = MikrotikNetwatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    // Delete from MikroTik after delete from database
                    if ($this->record->netwatch_id && $this->record->mikrotikDevice) {
                        $service = new MikrotikNetwatchService();
                        $service->deleteNetwatch($this->record->mikrotikDevice, $this->record);
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Sync to MikroTik after updating in database
        $service = new MikrotikNetwatchService();
        $result = $service->updateNetwatch($this->record->mikrotikDevice, $this->record);

        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Netwatch berhasil diupdate dan sync ke MikroTik')
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title('Tersimpan tapi belum sync')
                ->body('Netwatch tersimpan di database tapi gagal sync ke MikroTik: ' . $result['message'])
                ->send();
        }
    }
}

