<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\RelationManagers;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use App\Filament\Resources\PositionResource;

class ActivityResource extends Resource
{
    protected static ?string $model = ModelsActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Log Aktivitas';
    protected static ?int $navigationSort = 98;

    public static function getModelLabel(): string
    {
        return 'Log Aktivitas';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Log Aktivitas';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Jenis Log')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'department' => 'primary',
                        'employee' => 'success',
                        'position' => 'info',
                        'user' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'department' => 'Departemen',
                        'employee' => 'Karyawan',
                        'position' => 'Jabatan',
                        'user' => 'Pengguna',
                        default => 'Umum',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        default => 'Umum',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->wrap()
                    ->formatStateUsing(fn ($state) => Str::headline($state))
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Objek')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '-';
                        
                        if ($record->log_name === 'department' && $record->subject) {
                            return $record->subject->name ?? '-';
                        } elseif ($record->log_name === 'position' && $record->subject) {
                            return $record->subject->title ?? '-';
                        }
                        
                        return $record->subject ? ($record->subject->name ?? ($record->subject->title ?? '-')) : '-';
                    })
                    ->url(function ($record) {
                        if (!$record->subject_id) return null;
                        
                        return match($record->subject_type) {
                            'App\\Models\\Department' => DepartmentResource::getUrl('view', ['record' => $record->subject_id]),
                            'App\\Models\\Position' => PositionResource::getUrl('view', ['record' => $record->subject_id]),
                            default => null,
                        };
                    })
                    ->openUrlInNewTab()
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search) {
                            // Searching in both subject.name and subject.title depending on subject type
                            $query->whereHas('subject', function (Builder $query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('title', 'like', "%{$search}%");
                            });
                        });
                    })
                    ->tooltip('Klik untuk melihat detail objek'),
                    
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => $state ? Str::of($state)->afterLast('\\')->headline() : '-')
                    ->toggleable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('properties')
                    ->label('Perubahan')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->tooltip('Lihat perubahan')
                    ->toggleable()
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Jenis Log')
                    ->options([
                        'department' => 'Departemen',
                        'employee' => 'Karyawan',
                        'position' => 'Jabatan',
                        'user' => 'Pengguna',
                        'default' => 'Umum',
                    ])
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                    ])
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Tipe Objek')
                    ->options([
                        'App\\Models\\Department' => 'Departemen',
                        'App\\Models\\Position' => 'Jabatan',
                        'App\\Models\\Employee' => 'Karyawan',
                        'App\\Models\\User' => 'Pengguna',
                    ])
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\Filter::make('causer')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Pengguna')
                            ->options(function () {
                                return \App\Models\User::whereIn('id', function ($query) {
                                    $query->select('causer_id')
                                        ->from('activity_log')
                                        ->where('causer_type', 'App\\Models\\User')
                                        ->whereNotNull('causer_id')
                                        ->distinct();
                                })->pluck('name', 'id');
                            })
                            ->searchable()
                            ->multiple()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'] ?? null,
                                fn (Builder $query, $userIds): Builder => $query
                                    ->where('causer_type', 'App\\Models\\User')
                                    ->whereIn('causer_id', $userIds)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if (!empty($data['user_id'])) {
                            $userNames = \App\Models\User::whereIn('id', $data['user_id'])->pluck('name')->toArray();
                            $indicators['user_id'] = 'Pengguna: ' . implode(', ', $userNames);
                        }
                        
                        return $indicators;
                    }),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Dari: ' . Carbon::parse($data['created_from'])->format('d/m/Y');
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Sampai: ' . Carbon::parse($data['created_until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('Lihat detail log'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus log')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Log Aktivitas')
                    ->modalDescription('Apakah Anda yakin ingin menghapus log aktivitas ini?')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus log aktivitas yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal'),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Ekspor ke CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            try {
                                // Header kolom CSV
                                $csvHeader = [
                                    'ID', 'Jenis Log', 'Event', 'Deskripsi', 'Objek', 'Oleh', 'Waktu'
                                ];
                                
                                // Data untuk CSV
                                $csvData = $records->map(function ($record) {
                                    $objectName = '';
                                    
                                    if ($record->subject) {
                                        if ($record->log_name === 'department') {
                                            $objectName = $record->subject->name ?? '';
                                        } elseif ($record->log_name === 'position') {
                                            $objectName = $record->subject->title ?? '';
                                        } else {
                                            $objectName = $record->subject->name ?? ($record->subject->title ?? '');
                                        }
                                    }
                                    
                                    return [
                                        $record->id,
                                        $record->log_name ?? '',
                                        $record->event ?? '',
                                        $record->description ?? '',
                                        $objectName,
                                        $record->causer ? ($record->causer->name ?? '') : '',
                                        $record->created_at ? $record->created_at->format('d/m/Y H:i:s') : '',
                                    ];
                                })->toArray();
                                
                                // Buat file CSV
                                $filename = 'log_aktivitas_' . date('YmdHis') . '.csv';
                                $path = storage_path('app/public/' . $filename);
                                
                                $handle = fopen($path, 'w');
                                if (!$handle) {
                                    throw new \Exception('Tidak dapat membuat file CSV');
                                }
                                
                                // Tulis header ke CSV
                                fputcsv($handle, $csvHeader);
                                
                                // Tulis data ke CSV
                                foreach ($csvData as $row) {
                                    fputcsv($handle, $row);
                                }
                                
                                fclose($handle);
                                
                                // Kembalikan URL unduhan
                                return response()->download($path, $filename, [
                                    'Content-Type' => 'text/csv',
                                ])->deleteFileAfterSend();
                                
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Kesalahan Ekspor')
                                    ->body('Terjadi kesalahan saat mengekspor log: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                ]),
            ])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Belum ada log aktivitas')
            ->emptyStateDescription('Log aktivitas akan tercatat saat ada perubahan data di sistem.')
            ->poll('30s');
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
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
