<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikMonitoringResource\Pages;
use App\Models\MikrotikDevice;
use App\Services\MikrotikService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class MikrotikMonitoringResource extends Resource
{
    protected static ?string $model = MikrotikDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationGroup = 'Monitoring';
    
    protected static ?string $navigationLabel = 'Resource Mikrotik';
    
    protected static ?string $slug = 'mikrotik-monitoring';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Alamat IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->label('Port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_resources')
                    ->label('Lihat Resource')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->url(fn (MikrotikDevice $record): string => static::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Perangkat')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Perangkat'),
                                TextEntry::make('ip_address')
                                    ->label('Alamat IP'),
                                TextEntry::make('port')
                                    ->label('Port'),
                            ]),
                    ]),
                
                Section::make('Resource Monitoring')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('cpu_load')
                                    ->label('CPU Load')
                                    ->state(function (MikrotikDevice $record): string {
                                        try {
                                            $service = new MikrotikService();
                                            if ($service->connect($record)) {
                                                $systemInfo = $service->getSystemInfo();
                                                $cpuLoad = $systemInfo[0]['cpu-load'] ?? 'N/A';
                                                $service->disconnect();
                                                return $cpuLoad . '%';
                                            }
                                            return 'Tidak dapat terhubung';
                                        } catch (\Exception $e) {
                                            Log::error('Error getting CPU load: ' . $e->getMessage());
                                            return 'Error: ' . $e->getMessage();
                                        }
                                    })
                                    ->weight(FontWeight::Bold),
                                
                                TextEntry::make('memory_usage')
                                    ->label('Memory Usage')
                                    ->state(function (MikrotikDevice $record): string {
                                        try {
                                            $service = new MikrotikService();
                                            if ($service->connect($record)) {
                                                $systemInfo = $service->getSystemInfo();
                                                $totalMemory = $systemInfo[0]['total-memory'] ?? 0;
                                                $freeMemory = $systemInfo[0]['free-memory'] ?? 0;
                                                $usedMemory = $totalMemory - $freeMemory;
                                                $memoryPercentage = ($totalMemory > 0) ? round(($usedMemory / $totalMemory) * 100, 2) : 0;
                                                
                                                $totalMemoryMB = round($totalMemory / 1024 / 1024, 2);
                                                $usedMemoryMB = round($usedMemory / 1024 / 1024, 2);
                                                
                                                $service->disconnect();
                                                return $memoryPercentage . '% (' . $usedMemoryMB . ' MB / ' . $totalMemoryMB . ' MB)';
                                            }
                                            return 'Tidak dapat terhubung';
                                        } catch (\Exception $e) {
                                            Log::error('Error getting memory usage: ' . $e->getMessage());
                                            return 'Error: ' . $e->getMessage();
                                        }
                                    })
                                    ->weight(FontWeight::Bold),
                                
                                TextEntry::make('disk_usage')
                                    ->label('Disk Usage')
                                    ->state(function (MikrotikDevice $record): string {
                                        try {
                                            $service = new MikrotikService();
                                            if ($service->connect($record)) {
                                                $systemInfo = $service->getSystemInfo();
                                                $totalDisk = $systemInfo[0]['total-hdd-space'] ?? 0;
                                                $freeDisk = $systemInfo[0]['free-hdd-space'] ?? 0;
                                                $usedDisk = $totalDisk - $freeDisk;
                                                $diskPercentage = ($totalDisk > 0) ? round(($usedDisk / $totalDisk) * 100, 2) : 0;
                                                
                                                $totalDiskMB = round($totalDisk / 1024 / 1024, 2);
                                                $usedDiskMB = round($usedDisk / 1024 / 1024, 2);
                                                
                                                $service->disconnect();
                                                return $diskPercentage . '% (' . $usedDiskMB . ' MB / ' . $totalDiskMB . ' MB)';
                                            }
                                            return 'Tidak dapat terhubung';
                                        } catch (\Exception $e) {
                                            Log::error('Error getting disk usage: ' . $e->getMessage());
                                            return 'Error: ' . $e->getMessage();
                                        }
                                    })
                                    ->weight(FontWeight::Bold),
                                
                                TextEntry::make('uptime')
                                    ->label('Uptime')
                                    ->state(function (MikrotikDevice $record): string {
                                        try {
                                            $service = new MikrotikService();
                                            if ($service->connect($record)) {
                                                $systemInfo = $service->getSystemInfo();
                                                $uptime = $systemInfo[0]['uptime'] ?? 'N/A';
                                                $service->disconnect();
                                                return $uptime;
                                            }
                                            return 'Tidak dapat terhubung';
                                        } catch (\Exception $e) {
                                            Log::error('Error getting uptime: ' . $e->getMessage());
                                            return 'Error: ' . $e->getMessage();
                                        }
                                    })
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),
                
                Section::make('Trafik Ethernet')
                    ->schema([
                        TextEntry::make('ethernet_traffic')
                            ->label('Trafik Ethernet')
                            ->state(function (MikrotikDevice $record): string {
                                try {
                                    $service = new MikrotikService();
                                    if ($service->connect($record)) {
                                        $interfaces = $service->getInterfaces();
                                        $ethernetInterfaces = array_filter($interfaces, function($interface) {
                                            return strpos($interface['name'], 'ether') === 0;
                                        });
                                        
                                        $trafficHtml = '<div class="space-y-2">';
                                        foreach ($ethernetInterfaces as $interface) {
                                            $name = $interface['name'];
                                            $rxBytes = isset($interface['rx-byte']) ? self::formatBytes($interface['rx-byte']) : 'N/A';
                                            $txBytes = isset($interface['tx-byte']) ? self::formatBytes($interface['tx-byte']) : 'N/A';
                                            
                                            $trafficHtml .= "<div class='p-2 bg-gray-100 rounded'>";
                                            $trafficHtml .= "<span class='font-bold'>{$name}</span>: ";
                                            $trafficHtml .= "Download: {$rxBytes}, Upload: {$txBytes}";
                                            $trafficHtml .= "</div>";
                                        }
                                        $trafficHtml .= '</div>';
                                        
                                        $service->disconnect();
                                        return $trafficHtml;
                                    }
                                    return 'Tidak dapat terhubung';
                                } catch (\Exception $e) {
                                    Log::error('Error getting ethernet traffic: ' . $e->getMessage());
                                    return 'Error: ' . $e->getMessage();
                                }
                            })
                            ->html(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMikrotikMonitoring::route('/'),
            'view' => Pages\ViewMikrotikMonitoring::route('/{record}'),
        ];
    }
    
    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}