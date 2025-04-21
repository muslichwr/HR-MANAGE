<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Filament\Resources\PositionResource\RelationManagers;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Organization Management';
    protected static ?string $navigationLabel = 'Jabatan';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('Jabatan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Jabatan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label(__('Nama Jabatan'))
                                ->required()
                                ->maxLength(50)
                                ->placeholder('Contoh: Kepala Departemen, Direktur, Manager')
                                ->helperText('Maksimal 50 karakter')
                                ->hintIcon('heroicon-o-information-circle', tooltip: 'Nama resmi jabatan')
                                ->prefixIcon('heroicon-o-rectangle-stack')
                                ->autofocus()
                                ->columnSpanFull()
                                ->unique(
                                    table: 'positions',
                                    column: 'title',
                                    ignoreRecord: true
                                )
                                ->validationMessages([
                                    'required' => 'Harap isi nama jabatan',
                                    'unique' => 'Nama jabatan ini sudah digunakan',
                                    'max' => 'Maksimal 50 karakter'
                                ])
                                ->extraInputAttributes(['class' => 'text-lg font-medium']),
                            
                            Forms\Components\Select::make('department_id')
                                    ->label('Departmen')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Pilih departemen terkait')
                                    ->validationMessages([
                                        'required' => 'Harap pilih departemen'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),

                            Forms\Components\Select::make('level')
                                    ->label('Tingkatan Jabatan')
                                    ->options([
                                        'junior' => 'Junior (Pelaksana)',
                                        'senior' => 'Senior (Penanggung Jawab)',
                                        'manager' => 'Manager (Pengelola)',
                                        'director' => 'Direktur (Pimpinan)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->searchable()
                                    ->placeholder('Pilih level jabatan')
                                    ->helperText('Tingkat hierarki dalam organisasi')
                                    ->validationMessages([
                                        'required' => 'Level jabatan harus dipilih'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-xl']),
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
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Jabatan'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('primary')
                    ->size('')
                    ->weight(''),
                
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Position $record) => DepartmentResource::getUrl('view', ['record' => $record->department_id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-building-office')
                    ->size(''),

                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'junior' => 'Junior (Pelaksana)',
                        'senior' => 'Senior (Penanggung Jawab)',
                        'manager' => 'Manager (Pengelola)',
                        'director' => 'Direktur (Pimpinan)',
                    })
                    ->colors([
                        'success' => 'director',
                        'warning' => 'manager',
                        'info' => 'senior',
                        'gray' => 'junior',
                    ])
                    ->icons([
                        'heroicon-o-user-circle' => 'director',
                        'heroicon-o-user-group' => 'manager',
                        'heroicon-o-user' => 'senior',
                        'heroicon-o-user' => 'junior',
                    ])
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
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->preload()
                    ->searchable()
                    ->label('Filter Departemen')
                    ->indicator('Departemen')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'junior' => 'Junior (Pelaksana)',
                        'senior' => 'Senior (Penanggung Jawab)',
                        'manager' => 'Manager (Pengelola)',
                        'director' => 'Direktur (Pimpinan)',
                    ])
                    ->label('Filter Level')
                    ->indicator('Level')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus jabatan')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus jabatan ini? Data terkait mungkin terpengaruh.')
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
            ->defaultSort('title', 'asc');
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
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'view' => Pages\ViewPosition::route('/{record}'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
