<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryComponentResource\Pages;
use App\Filament\Resources\SalaryComponentResource\RelationManagers;
use App\Models\SalaryComponent;
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

class SalaryComponentResource extends Resource
{
    protected static ?string $model = SalaryComponent::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Payroll Management';
    protected static ?string $navigationLabel = 'Komponen Gaji';
    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('Komponen Gaji');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Komponen Gaji');
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
                                    ->label(__('Nama Komponen'))
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('Contoh: Tunjangan Transportasi, Bonus Kinerja')
                                    ->helperText('Maksimal 50 karakter')
                                    ->hintIcon('heroicon-o-information-circle', tooltip: 'Nama komponen gaji')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->autofocus()
                                    ->columnSpanFull()
                                    ->unique(
                                        table: 'salary_components',
                                        column: 'name',
                                        ignoreRecord: true
                                    )
                                    ->validationMessages([
                                        'required' => 'Harap isi nama komponen',
                                        'unique' => 'Nama komponen ini sudah digunakan',
                                        'max' => 'Maksimal 50 karakter'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),

                                Forms\Components\Select::make('type_id')
                                    ->label('Tipe Komponen')
                                    ->relationship('componentType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Pilih tipe komponen gaji')
                                    ->validationMessages([
                                        'required' => 'Harap pilih tipe komponen'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),

                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Deskripsi tentang komponen gaji ini')
                                    ->maxLength(500)
                                    ->helperText('Maksimal 500 karakter')
                                    ->columnSpanFull(),
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
                    ->label(__('Nama Komponen'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-currency-dollar')
                    ->size('')
                    ->weight(''),
                
                Tables\Columns\TextColumn::make('componentType.name')
                    ->label('Tipe Komponen')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (SalaryComponent $record) => ComponentTypeResource::getUrl('view', ['record' => $record->type_id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-clipboard-document-list')
                    ->size(''),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->toggleable()
                    ->words(10)
                    ->size(''),
                    
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
                Tables\Filters\SelectFilter::make('type_id')
                    ->relationship('componentType', 'name')
                    ->preload()
                    ->searchable()
                    ->label('Filter Tipe Komponen')
                    ->indicator('Tipe Komponen')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus komponen gaji')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus komponen gaji ini? Data terkait mungkin terpengaruh.')
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
            'index' => Pages\ListSalaryComponents::route('/'),
            'create' => Pages\CreateSalaryComponent::route('/create'),
            'view' => Pages\ViewSalaryComponent::route('/{record}'),
            'edit' => Pages\EditSalaryComponent::route('/{record}/edit'),
        ];
    }
}
