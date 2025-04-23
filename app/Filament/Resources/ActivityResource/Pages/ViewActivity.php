<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\EmployeeResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\IconEntry;
use Illuminate\Support\Str;

class ViewActivity extends ViewRecord
{
    protected static string $resource = ActivityResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detail Aktivitas')
                    ->schema([
                        TextEntry::make('log_name')
                            ->label('Jenis Log')
                            ->badge()
                            ->icon('heroicon-o-clipboard-document-list')
                            ->color(fn ($state) => match ($state) {
                                'department' => 'primary',
                                'employee' => 'success',
                                'position' => 'info',
                                'user' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'department' => 'Departemen',
                                'employee' => 'Karyawan',
                                'position' => 'Jabatan',
                                'user' => 'Pengguna',
                                default => 'Umum',
                            }),
                            
                        TextEntry::make('event')
                            ->label('Event')
                            ->badge()
                            ->icon(fn ($state) => match ($state) {
                                'created' => 'heroicon-o-plus-circle',
                                'updated' => 'heroicon-o-pencil-square',
                                'deleted' => 'heroicon-o-trash',
                                default => 'heroicon-o-document',
                            })
                            ->color(fn ($state) => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'created' => 'Dibuat',
                                'updated' => 'Diperbarui',
                                'deleted' => 'Dihapus',
                                default => 'Umum',
                            }),
                            
                        TextEntry::make('description')
                            ->label('Aksi')
                            ->formatStateUsing(fn ($state) => Str::headline($state))
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->columnSpanFull(),
                            
                        TextEntry::make('subject_type')
                            ->label('Tipe')
                            ->icon('heroicon-o-cube')
                            ->formatStateUsing(fn ($state) => $state ? Str::of($state)->afterLast('\\')->headline() : '-'),
                            
                        TextEntry::make('subject')
                            ->label('Objek')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return '-';
                                
                                if ($record->log_name === 'department' && $record->subject) {
                                    return $record->subject->name ?? '-';
                                } elseif ($record->log_name === 'position' && $record->subject) {
                                    return $record->subject->title ?? '-';
                                } elseif ($record->log_name === 'employee' && $record->subject) {
                                    return $record->subject->full_name ?? '-';
                                }
                                
                                return $record->subject ? ($record->subject->name ?? ($record->subject->title ?? ($record->subject->full_name ?? '-'))) : '-';
                            })
                            ->url(function ($record) {
                                if (!$record->subject_id) return null;
                                
                                return match($record->subject_type) {
                                    'App\\Models\\Department' => DepartmentResource::getUrl('view', ['record' => $record->subject_id]),
                                    'App\\Models\\Position' => PositionResource::getUrl('view', ['record' => $record->subject_id]),
                                    'App\\Models\\Employee' => EmployeeResource::getUrl('view', ['record' => $record->subject_id]),
                                    default => null,
                                };
                            })
                            ->icon('heroicon-o-link')
                            ->tooltip('Klik untuk melihat detail objek'),
                            
                        TextEntry::make('causer.name')
                            ->label('Oleh')
                            ->icon('heroicon-o-user'),
                            
                        TextEntry::make('created_at')
                            ->label('Waktu')
                            ->icon('heroicon-o-clock')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Section::make('Perubahan Data')
                    ->schema([
                        TextEntry::make('properties.old')
                            ->label('Sebelum')
                            ->icon('heroicon-o-arrow-left')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'Tidak ada data';
                                
                                // Format JSON dengan indentasi dan warna
                                $formatted = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                
                                // Buat tampilan yang lebih baik dengan <pre> tag
                                return view('filament.components.json-viewer', [
                                    'json' => $formatted,
                                    'label' => 'Data Sebelum'
                                ]);
                            })
                            ->html()
                            ->columnSpan(1),
                            
                        TextEntry::make('properties.attributes')
                            ->label('Sesudah')
                            ->icon('heroicon-o-arrow-right')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return 'Tidak ada data';
                                
                                // Format JSON dengan indentasi dan warna
                                $formatted = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                
                                // Buat tampilan yang lebih baik dengan <pre> tag
                                return view('filament.components.json-viewer', [
                                    'json' => $formatted,
                                    'label' => 'Data Sesudah'
                                ]);
                            })
                            ->html()
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->tooltip('Hapus log')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Log Aktivitas')
                ->modalDescription('Apakah Anda yakin ingin menghapus log aktivitas ini?')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->modalCancelActionLabel('Batal'),
        ];
    }
}
