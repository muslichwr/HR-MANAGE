<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\PositionResource;
use App\Models\Position;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Placeholder;
use Filament\Infolists\Components\Description;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;

class ViewPosition extends ViewRecord
{
    protected static string $resource = PositionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Utama')
                    ->icon('heroicon-o-rectangle-stack')
                    ->iconSize(IconSize::Large)
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Nama Jabatan')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->columnSpan(2)
                                    ->extraAttributes(['class' => 'text-primary-600']),

                                TextEntry::make('department.name')
                                    ->label('Departemen')
                                    ->url(fn (Position $record) => DepartmentResource::getUrl('view', ['record' => $record->department_id]))
                                    ->openUrlInNewTab()
                                    ->icon('heroicon-o-building-office')
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->columnSpan(1)
                                    ->extraAttributes(['class' => 'text-blue-600 hover:underline']),

                                TextEntry::make('level')
                                    ->label('Level Jabatan')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'junior' => 'Junior (Pelaksana)',
                                        'senior' => 'Senior (Penanggung Jawab)',
                                        'manager' => 'Manager (Pengelola)',
                                        'director' => 'Direktur (Pimpinan)',
                                    })
                                    ->color(fn (string $state) => match ($state) {
                                        'director' => 'success',
                                        'manager' => 'warning',
                                        'senior' => 'info',
                                        'junior' => 'gray',
                                    })
                                    ->icon(fn (string $state) => match ($state) {
                                        'director' => 'heroicon-o-user-circle',
                                        'manager' => 'heroicon-o-user-group',
                                        'senior' => 'heroicon-o-user',
                                        'junior' => 'heroicon-o-user',
                                    })
                                    ->size(TextEntry\TextEntrySize::Large)
                                    ->columnSpan(1),

                                // TextEntry::make('employees_count')
                                //     ->label('Jumlah Karyawan')
                                //     ->state(fn (Position $record) => $record->employees()->count())
                                //     ->icon('heroicon-o-users')
                                //     ->size(TextEntry\TextEntrySize::Large)
                                //     ->badge()
                                //     ->color('secondary')
                                //     ->url(fn (Position $record) => EmployeeResource::getUrl('index', ['tableFilters' => ['position' => $record->id]]))
                                //     ->columnSpan(1)
                                //     ->extraAttributes(['class' => 'text-secondary-600 hover:underline']),
                            ]),
                    ]),

                    Section::make('Riwayat Sistem')
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('l, d F Y H:i')
                            ->icon('heroicon-o-plus-circle')
                            ->color('success')
                            ->size(TextEntry\TextEntrySize::Medium),
                            
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diupdate')
                            ->since()
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->size(TextEntry\TextEntrySize::Medium)
                            ->tooltip(fn ($record) => $record->updated_at->format('l, d F Y H:i')),
                    ])
                    ->columns(2)
                    ->description('Catatan waktu pembuatan dan perubahan data')
                    ->collapsible(),
            ])
            ->columns(1);
    }
    

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Jabatan')
                ->icon('heroicon-o-pencil-square')
                ->button()
                ->color('warning')
                ->tooltip('Ubah informasi jabatan ini'),
        ];
    }
}