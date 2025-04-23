<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\PositionResource;
use Filament\Infolists\Infolist;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        TextEntry::make('nik')
                            ->label('NIK')
                            ->icon('heroicon-o-identification'),
                            
                        TextEntry::make('full_name')
                            ->label('Nama Lengkap')
                            ->icon('heroicon-o-user')
                            ->weight('bold'),
                            
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->icon('heroicon-o-home')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                    
                Section::make('Informasi Jabatan')
                    ->schema([
                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->icon('heroicon-o-building-office')
                            ->url(fn ($record) => DepartmentResource::getUrl('view', ['record' => $record->department_id]))
                            ->openUrlInNewTab(),
                            
                        TextEntry::make('position.title')
                            ->label('Jabatan')
                            ->icon('heroicon-o-rectangle-stack')
                            ->url(fn ($record) => PositionResource::getUrl('view', ['record' => $record->position_id]))
                            ->openUrlInNewTab(),
                            
                        TextEntry::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->date('d F Y')
                            ->icon('heroicon-o-calendar'),
                            
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'active' => 'Aktif',
                                'probation' => 'Masa Percobaan',
                                'contract' => 'Kontrak',
                                'inactive' => 'Tidak Aktif',
                                'terminated' => 'Diberhentikan',
                                default => $state,
                            })
                            ->icon(fn (string $state) => match ($state) {
                                'active' => 'heroicon-o-check-circle',
                                'probation' => 'heroicon-o-clock',
                                'contract' => 'heroicon-o-document-text',
                                'inactive' => 'heroicon-o-x-circle',
                                'terminated' => 'heroicon-o-no-symbol',
                                default => 'heroicon-o-question-mark-circle',
                            })
                            ->color(fn (string $state) => match ($state) {
                                'active' => 'success',
                                'probation' => 'warning',
                                'contract' => 'info',
                                'inactive' => 'gray',
                                'terminated' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),
                    
                Section::make('Informasi Sistem')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-clock'),
                            
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->tooltip('Edit karyawan'),
                
            Actions\DeleteAction::make()
                ->tooltip('Hapus karyawan')
                ->requiresConfirmation()
                ->modalDescription('Apakah Anda yakin ingin menghapus data karyawan ini? Data terkait mungkin terpengaruh.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->modalCancelActionLabel('Batal'),
        ];
    }
} 