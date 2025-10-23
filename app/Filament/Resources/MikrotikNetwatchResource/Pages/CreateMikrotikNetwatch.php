<?php

namespace App\Filament\Resources\MikrotikNetwatchResource\Pages;

use App\Filament\Resources\MikrotikNetwatchResource;
use App\Services\MikrotikNetwatchService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMikrotikNetwatch extends CreateRecord
{
    protected static string $resource = MikrotikNetwatchResource::class;

    protected function afterCreate(): void
    {
        // Sync to MikroTik after creating in database
        $service = new MikrotikNetwatchService();
        $result = $service->createNetwatch($this->record->mikrotikDevice, $this->record);

        if ($result['success']) {
            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Netwatch berhasil dibuat dan sync ke MikroTik')
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

