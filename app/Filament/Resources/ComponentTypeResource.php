<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComponentTypeResource\Pages;
use App\Filament\Resources\ComponentTypeResource\RelationManagers;
use App\Models\ComponentType;
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

class ComponentTypeResource extends Resource
{
    protected static ?string $model = ComponentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Payroll Management';
    protected static ?string $navigationLabel = 'Tipe Komponen';
    protected static ?int $navigationSort = 4;

    public static function getModelLabel(): string
    {
        return __('Tipe Komponen');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Tipe Komponen');
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
                                    ->label(__('Nama Tipe Komponen'))
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('Contoh: Tunjangan, Potongan, Bonus')
                                    ->helperText('Maksimal 20 karakter')
                                    ->hintIcon('heroicon-o-information-circle', tooltip: 'Nama untuk kategori komponen gaji')
                                    ->prefixIcon('heroicon-o-clipboard-document-list')
                                    ->autofocus()
                                    ->columnSpanFull()
                                    ->unique(
                                        table: 'component_types',
                                        column: 'name',
                                        ignoreRecord: true
                                    )
                                    ->validationMessages([
                                        'required' => 'Harap isi nama tipe komponen',
                                        'unique' => 'Nama tipe komponen ini sudah digunakan',
                                        'max' => 'Maksimal 20 karakter'
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
                    ->label(__('Nama Tipe Komponen'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-clipboard-document-list')
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
                    ->tooltip('Hapus tipe komponen')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus tipe komponen ini? Data terkait mungkin terpengaruh.')
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
            'index' => Pages\ListComponentTypes::route('/'),
            'create' => Pages\CreateComponentType::route('/create'),
            'view' => Pages\ViewComponentType::route('/{record}'),
            'edit' => Pages\EditComponentType::route('/{record}/edit'),
        ];
    }
}
