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
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;

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
                Section::make()
                    ->schema([
                        Grid::make(2)
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
                            ->columnSpan(2),
                        
                        Placeholder::make('')
                            ->content('Pastikan semua kolom wajib (bertanda *) telah terisi')
                            ->extraAttributes(['class' => 'text-lg text-gray-600 font-medium mt-4']),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                    
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nama Departemen'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-building-office')
                    ->size('')
                    ->weight(''),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size(''),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus departemen')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus departemen ini? Data terkait mungkin terpengaruh.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal'),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'view' => Pages\ViewDepartment::route('/{record}'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}