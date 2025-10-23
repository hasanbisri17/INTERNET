<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\MikrotikDevice;
use App\Models\MikrotikIpBinding;
use App\Services\MikrotikIpBindingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IpBindingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ipBindings';

    protected static ?string $title = 'IP Bindings';

    protected static ?string $recordTitleAttribute = 'address';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mikrotik_device_id')
                    ->label('MikroTik Device')
                    ->options(MikrotikDevice::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('mac_address')
                    ->label('MAC Address')
                    ->placeholder('00:00:00:00:00:00')
                    ->helperText('Format: XX:XX:XX:XX:XX:XX'),

                Forms\Components\TextInput::make('address')
                    ->label('IP Address')
                    ->placeholder('192.168.1.100')
                    ->helperText('IP Address yang akan di-bind')
                    ->required(),

                Forms\Components\TextInput::make('to_address')
                    ->label('To Address')
                    ->placeholder('192.168.1.101')
                    ->helperText('IP Address tujuan (opsional)'),

                Forms\Components\TextInput::make('server')
                    ->label('Hotspot Server')
                    ->default('all')
                    ->helperText('Nama hotspot server'),

                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'regular' => '🟢 Regular',
                        'bypassed' => '🟡 Bypassed',
                        'blocked' => '🔴 Blocked',
                    ])
                    ->default('regular')
                    ->required()
                    ->helperText('Regular: Normal authentication, Bypassed: Skip authentication, Blocked: Block access'),

                Forms\Components\Textarea::make('comment')
                    ->label('Comment')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_disabled')
                    ->label('Disabled')
                    ->helperText('Nonaktifkan IP Binding ini')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('mikrotikDevice.name')
                    ->label('Device')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'regular' => 'Regular',
                        'bypassed' => 'Bypassed',
                        'blocked' => 'Blocked',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match($state) {
                        'regular' => 'success',
                        'bypassed' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Comment')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    })
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_disabled')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('is_synced')
                    ->label('Synced')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'regular' => 'Regular',
                        'bypassed' => 'Bypassed',
                        'blocked' => 'Blocked',
                    ]),

                Tables\Filters\TernaryFilter::make('is_disabled')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Disabled')
                    ->falseLabel('Enabled'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('assign_ip_binding')
                    ->label('Assign IP Binding')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('ip_binding_id')
                            ->label('Pilih IP Binding')
                            ->options(function () {
                                // Get IP Bindings yang belum di-assign ke customer (customer_id = null)
                                return MikrotikIpBinding::whereNull('customer_id')
                                    ->with('mikrotikDevice')
                                    ->get()
                                    ->mapWithKeys(function ($binding) {
                                        $device = $binding->mikrotikDevice?->name ?? 'N/A';
                                        $mac = $binding->mac_address ? " - MAC: {$binding->mac_address}" : '';
                                        $comment = $binding->comment ? " ({$binding->comment})" : '';
                                        $label = "{$binding->address}{$mac} - {$device}{$comment}";
                                        return [$binding->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih IP Binding yang akan di-assign ke customer ini')
                            ->helperText('Hanya menampilkan IP Bindings yang belum di-assign ke customer lain')
                            ->native(false)
                            ->preload(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $ipBinding = MikrotikIpBinding::find($data['ip_binding_id']);
                        
                        if ($ipBinding) {
                            // Get customer ID from the relation manager owner
                            $customerId = $livewire->getOwnerRecord()->id;
                            
                            // Assign IP Binding to customer
                            $ipBinding->update(['customer_id' => $customerId]);
                            
                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body("IP Binding {$ipBinding->address} berhasil di-assign ke customer")
                                ->send();
                        }
                    })
                    ->modalHeading('Assign IP Binding ke Customer')
                    ->modalDescription('Pilih IP Binding dari list yang tersedia untuk di-assign ke customer ini.')
                    ->modalSubmitActionLabel('Assign')
                    ->modalWidth('lg'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('change_to_regular')
                        ->label('Change to Regular')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->visible(fn (MikrotikIpBinding $record) => $record->type !== 'regular')
                        ->action(function (MikrotikIpBinding $record) {
                            try {
                                $record->update(['type' => 'regular']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('Type berhasil diubah ke Regular')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('change_to_bypassed')
                        ->label('Change to Bypassed')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (MikrotikIpBinding $record) => $record->type !== 'bypassed')
                        ->action(function (MikrotikIpBinding $record) {
                            try {
                                $record->update(['type' => 'bypassed']);
                                
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('Type berhasil diubah ke Bypassed')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\EditAction::make()
                        ->after(function ($record) {
                            // Sync to MikroTik after updating
                            $service = new MikrotikIpBindingService();
                            $result = $service->updateBinding($record->mikrotikDevice, $record);

                            if ($result['success']) {
                                Notification::make()
                                    ->success()
                                    ->title('Berhasil')
                                    ->body('IP Binding berhasil diupdate dan sync ke MikroTik')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Tersimpan tapi belum sync')
                                    ->body('IP Binding tersimpan di database tapi gagal sync ke MikroTik: ' . $result['message'])
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('unlink')
                        ->label('Unlink dari Customer')
                        ->icon('heroicon-o-link-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Unlink IP Binding dari Customer')
                        ->modalDescription('IP Binding tidak akan dihapus dari MikroTik, hanya hubungannya dengan customer ini yang diputus. IP Binding akan menjadi "unassigned".')
                        ->action(function (MikrotikIpBinding $record) {
                            $record->update(['customer_id' => null]);
                            
                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body('IP Binding berhasil di-unlink dari customer')
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->after(function ($record) {
                            // Delete from MikroTik after delete from database
                            if ($record->binding_id && $record->mikrotikDevice) {
                                $service = new MikrotikIpBindingService();
                                $service->deleteBinding($record->mikrotikDevice, $record);
                            }
                        }),
                ])
                ->button()
                ->label('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('unlink')
                        ->label('Unlink dari Customer')
                        ->icon('heroicon-o-link-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['customer_id' => null]);
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body(count($records) . ' IP Binding berhasil di-unlink dari customer')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada IP Bindings')
            ->emptyStateDescription('Assign IP Binding dari list yang tersedia atau sync dari MikroTik terlebih dahulu.')
            ->emptyStateActions([
                Tables\Actions\Action::make('assign_ip_binding')
                    ->label('Assign IP Binding')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('ip_binding_id')
                            ->label('Pilih IP Binding')
                            ->options(function () {
                                return MikrotikIpBinding::whereNull('customer_id')
                                    ->with('mikrotikDevice')
                                    ->get()
                                    ->mapWithKeys(function ($binding) {
                                        $device = $binding->mikrotikDevice?->name ?? 'N/A';
                                        $mac = $binding->mac_address ? " - MAC: {$binding->mac_address}" : '';
                                        $comment = $binding->comment ? " ({$binding->comment})" : '';
                                        $label = "{$binding->address}{$mac} - {$device}{$comment}";
                                        return [$binding->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih IP Binding yang akan di-assign')
                            ->helperText('Hanya menampilkan IP Bindings yang belum di-assign ke customer lain')
                            ->native(false)
                            ->preload(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $ipBinding = MikrotikIpBinding::find($data['ip_binding_id']);
                        
                        if ($ipBinding) {
                            $customerId = $livewire->getOwnerRecord()->id;
                            $ipBinding->update(['customer_id' => $customerId]);
                            
                            Notification::make()
                                ->success()
                                ->title('Berhasil')
                                ->body("IP Binding {$ipBinding->address} berhasil di-assign ke customer")
                                ->send();
                        }
                    })
                    ->modalHeading('Assign IP Binding ke Customer')
                    ->modalDescription('Pilih IP Binding dari list yang tersedia.')
                    ->modalSubmitActionLabel('Assign')
                    ->modalWidth('lg'),
            ]);
    }
}
