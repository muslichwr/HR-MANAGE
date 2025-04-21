<?php

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    protected static ?string $title = 'Riwayat Perubahan';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'create' => 'Dibuat',
                        'update' => 'Diupdate',
                        'delete' => 'Dihapus',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s'),
                    
                Tables\Columns\TextColumn::make('old_value')
                    ->label('Data Lama')
                    ->formatStateUsing(fn ($state) => $state ? 
                        '<pre class="text-xs p-2 rounded">'.json_encode($state, JSON_PRETTY_PRINT).'</pre>' : '-')
                    ->html(),
                    
                Tables\Columns\TextColumn::make('new_value')
                    ->label('Data Baru')
                    ->formatStateUsing(fn ($state) => $state ? 
                        '<pre class="text-xs p-2  rounded">'.json_encode($state, JSON_PRETTY_PRINT).'</pre>' : '-')
                    ->html(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Filter Aksi')
                    ->options([
                        'create' => 'Pembuatan',
                        'update' => 'Update',
                        'delete' => 'Penghapusan',
                    ])
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }
}
