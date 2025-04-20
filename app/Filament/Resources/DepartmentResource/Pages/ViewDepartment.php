<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDepartment extends ViewRecord
{
    protected static string $resource = DepartmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Utama Departemen')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        TextEntry::make('department_id')
                            ->label('ID Departemen')
                            ->weight('bold')
                            ->color('primary')
                            ->icon('heroicon-o-identification')
                            ->size(TextEntry\TextEntrySize::Large),
                            
                        TextEntry::make('name')
                            ->label('Nama Departemen')
                            ->icon('heroicon-o-tag')
                            ->size(TextEntry\TextEntrySize::Large)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-2xl font-bold']),
                    ])
                    ->columns(2)
                    ->collapsible(false),
                
                Section::make('Riwayat Sistem')
                    ->icon('heroicon-o-clock')
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
            \Filament\Actions\EditAction::make()
                ->label('Edit Data')
                ->icon('heroicon-o-pencil-square')
                ->color('warning'),
                
            \Filament\Actions\Action::make('back')
                ->label('Kembali ke Daftar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Departemen';
    }

}