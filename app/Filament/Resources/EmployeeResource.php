<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
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


class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Organization Management';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('Karyawan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Karyawan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Karyawan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nik')
                                    ->label(__('NIK'))
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('Nomor Induk Karyawan')
                                    ->helperText('Maksimal 20 karakter')
                                    ->hintIcon('heroicon-o-information-circle', tooltip: 'Nomor Induk Karyawan')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->autofocus()
                                    ->unique(
                                        table: 'employees',
                                        column: 'nik',
                                        ignoreRecord: true
                                    )
                                    ->validationMessages([
                                        'required' => 'Harap isi NIK karyawan',
                                        'unique' => 'NIK karyawan ini sudah digunakan',
                                        'max' => 'Maksimal 20 karakter'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),

                                Forms\Components\TextInput::make('full_name')
                                    ->label(__('Nama Lengkap'))
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Nama lengkap karyawan')
                                    ->helperText('Maksimal 100 karakter')
                                    ->prefixIcon('heroicon-o-user')
                                    ->validationMessages([
                                        'required' => 'Harap isi nama lengkap karyawan',
                                        'max' => 'Maksimal 100 karakter'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),
                            ])
                            ->columnSpan(2),
                            
                        Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label(__('Alamat'))
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Alamat lengkap karyawan')
                                    ->helperText('Alamat tempat tinggal saat ini')
                                    ->validationMessages([
                                        'required' => 'Harap isi alamat karyawan',
                                    ])
                                    ->extraInputAttributes(['class' => 'text-base']),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                
                Section::make('Informasi Jabatan')
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih departemen tempat karyawan bekerja')
                            ->validationMessages([
                                'required' => 'Harap pilih departemen'
                            ])
                            ->extraInputAttributes(['class' => 'text-lg font-medium'])
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('position_id', null);
                            })
                            ->live(),

                        Forms\Components\Select::make('position_id')
                            ->label('Jabatan')
                            ->relationship('position', 'title', function (Builder $query, $get) {
                                $departmentId = $get('department_id');
                                if ($departmentId) {
                                    $query->where('department_id', $departmentId);
                                }
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih jabatan karyawan')
                            ->validationMessages([
                                'required' => 'Harap pilih jabatan'
                            ])
                            ->extraInputAttributes(['class' => 'text-lg font-medium']),

                        Forms\Components\DatePicker::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->required()
                            ->maxDate(now())
                            ->default(now())
                            ->displayFormat('d F Y')
                            ->helperText('Tanggal karyawan mulai bekerja')
                            ->prefixIcon('heroicon-o-calendar')
                            ->validationMessages([
                                'required' => 'Harap pilih tanggal bergabung',
                                'date' => 'Format tanggal tidak valid',
                                'max_date' => 'Tanggal tidak boleh lebih dari hari ini'
                            ]),
                            
                        Forms\Components\Select::make('status')
                            ->label('Status Karyawan')
                            ->options([
                                'active' => 'Aktif',
                                'probation' => 'Masa Percobaan',
                                'contract' => 'Kontrak',
                                'inactive' => 'Tidak Aktif',
                                'terminated' => 'Diberhentikan'
                            ])
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->default('active')
                            ->helperText('Status ketenagakerjaan saat ini')
                            ->validationMessages([
                                'required' => 'Harap pilih status karyawan'
                            ])
                            ->extraInputAttributes(['class' => 'text-lg']),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                
                Placeholder::make('')
                    ->content('Pastikan semua kolom wajib (bertanda *) telah terisi')
                    ->extraAttributes(['class' => 'text-lg text-gray-600 font-medium mt-4']),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-identification')
                    ->size(''),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-user')
                    ->weight('medium')
                    ->size(''),
                
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Employee $record) => DepartmentResource::getUrl('view', ['record' => $record->department_id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-building-office')
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('position.title')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Employee $record) => PositionResource::getUrl('view', ['record' => $record->position_id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-rectangle-stack')
                    ->size(''),
                
                Tables\Columns\TextColumn::make('join_date')
                    ->label('Tanggal Bergabung')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar')
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active' => 'Aktif',
                        'probation' => 'Masa Percobaan',
                        'contract' => 'Kontrak',
                        'inactive' => 'Tidak Aktif',
                        'terminated' => 'Diberhentikan',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'warning' => 'probation',
                        'info' => 'contract',
                        'gray' => 'inactive',
                        'danger' => 'terminated',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'active',
                        'heroicon-o-clock' => 'probation',
                        'heroicon-o-document-text' => 'contract',
                        'heroicon-o-x-circle' => 'inactive',
                        'heroicon-o-no-symbol' => 'terminated',
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
                    
                Tables\Filters\SelectFilter::make('position')
                    ->relationship('position', 'title')
                    ->preload()
                    ->searchable()
                    ->label('Filter Jabatan')
                    ->indicator('Jabatan')
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'probation' => 'Masa Percobaan',
                        'contract' => 'Kontrak',
                        'inactive' => 'Tidak Aktif',
                        'terminated' => 'Diberhentikan'
                    ])
                    ->label('Filter Status')
                    ->indicator('Status')
                    ->multiple(),
                
                Tables\Filters\Filter::make('join_date')
                    ->form([
                        Forms\Components\DatePicker::make('join_date_from')
                            ->label('Tanggal Bergabung Dari'),
                        Forms\Components\DatePicker::make('join_date_until')
                            ->label('Tanggal Bergabung Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['join_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('join_date', '>=', $date),
                            )
                            ->when(
                                $data['join_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('join_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['join_date_from'] ?? null) {
                            $indicators['join_date_from'] = 'Mulai dari: ' . \Carbon\Carbon::parse($data['join_date_from'])->format('d/m/Y');
                        }

                        if ($data['join_date_until'] ?? null) {
                            $indicators['join_date_until'] = 'Sampai: ' . \Carbon\Carbon::parse($data['join_date_until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('Lihat detail karyawan'),
                
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit karyawan'),
                
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus karyawan')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus data karyawan ini? Data terkait mungkin terpengaruh.')
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
            ->defaultSort('full_name', 'asc')
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
