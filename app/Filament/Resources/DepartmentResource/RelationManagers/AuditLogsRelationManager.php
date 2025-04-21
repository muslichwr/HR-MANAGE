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

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             Forms\Components\TextInput::make('log_id')
    //                 ->required()
    //                 ->maxLength(255),
    //         ]);
    // }

    public function table(Table $table): Table
    {
        return $table
            // ->recordTitleAttribute('log_id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->color(fn ($state) => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                    }),
                    
                Tables\Columns\TextColumn::make('old_value')
                    ->label('Data Lama')
                    ->formatStateUsing(fn ($state) => collect(json_decode($state, true) ?: [])->map(
                        fn ($value, $key) => "<b>{$key}:</b> {$value}"
                    )->join('<br>'))
                    ->html(),
                    
                Tables\Columns\TextColumn::make('new_value')
                    ->label('Data Baru')
                    ->formatStateUsing(fn ($state) => collect(json_decode($state, true) ?: [])->map(
                        fn ($value, $key) => "<b>{$key}:</b> {$value}"
                    )->join('<br>'))
                    ->html(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                ->options([
                    'create' => 'Dibuat',
                    'update' => 'Diubah',
                    'delete' => 'Dihapus',
                ])
            ])
            ->headerActions([
                
            ])
            ->actions([
                
            ])
            ->bulkActions([
                
            ])
            ->defaultSort('created_at', 'desc');
    }

    // protected function getTableQuery(): Builder
    // {
    //     return parent::getTableQuery()
    //         ->where('table_name', 'departments');
    // }
}
