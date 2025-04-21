<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Organization Management';
    protected static ?string $navigationLabel = 'Departemen';
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('Departemen');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Departemen');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                        ->label(__('Nama Departemen'))
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Contoh: Keuangan, SDM, IT')
                        ->helperText('Maksimal 100 karakter')
                        ->hintIcon('heroicon-o-information-circle', tooltip: 'Nama resmi departemen')
                        ->prefixIcon('heroicon-o-building-office')
                        ->autofocus()
                        ->columnSpanFull()
                        ->unique(
                            table: 'departments',
                            column: 'name',
                            ignoreRecord: true
                        )
                        ->validationMessages([
                            'required' => 'Harap isi nama departemen',
                            'unique' => 'Nama departemen ini sudah digunakan',
                            'max' => 'Maksimal 100 karakter'
                        ])
                        ->extraInputAttributes(['class' => 'text-lg font-medium']),
                ])
                ->columns(1)
                ->heading(
                    fn ($operation) => str($operation)
                        ->upper()
                        ->append(' DEPARTEMEN')
                        ->toString()
                )
                ->extraAttributes(['class' => 'border-2 border-gray-200 rounded-xl p-6'])
                ->headerActions([
                    // Tambahkan aksi header jika diperlukan
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'view' => Pages\ViewDepartment::route('/{record}'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}