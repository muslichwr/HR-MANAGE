<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayslipResource\Pages;
use App\Filament\Resources\PayslipResource\RelationManagers;
use App\Filament\Resources\PayslipResource\RelationManagers\PayslipComponentsRelationManager;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\ComponentType;
use App\Models\PayslipComponent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Payroll Management';
    protected static ?string $navigationLabel = 'Slip Gaji';
    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('Slip Gaji');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Slip Gaji');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Slip Gaji')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Karyawan')
                                    ->required()
                                    ->searchable()
                                    ->options(Employee::all()->pluck('full_name', 'employee_id'))
                                    ->native(false)
                                    ->helperText('Pilih karyawan dari daftar')
                                    ->prefixIcon('heroicon-o-users')
                                    ->unique(
                                        ignoreRecord: true, 
                                        modifyRuleUsing: function (Unique $rule, callable $get) {
                                            return $rule
                                                ->where('employee_id', $get('employee_id'))
                                                ->where('month', $get('month'))
                                                ->where('year', $get('year'));
                                        }
                                    )
                                    ->validationMessages([
                                        'required' => 'Harap pilih karyawan',
                                        'unique' => 'Slip gaji untuk karyawan ini pada periode yang sama sudah ada'
                                    ])
                                    ->extraInputAttributes(['class' => 'text-lg font-medium']),
                                
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('month')
                                            ->label('Bulan')
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(12)
                                            ->default(now()->month)
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->helperText('Bulan periode slip gaji')
                                            ->validationMessages([
                                                'required' => 'Harap isi bulan',
                                                'numeric' => 'Harus berupa angka',
                                                'min_value' => 'Minimal 1',
                                                'max_value' => 'Maksimal 12'
                                            ]),
                                            
                                        Forms\Components\TextInput::make('year')
                                            ->label('Tahun')
                                            ->required()
                                            ->numeric()
                                            ->minValue(2000)
                                            ->maxValue(now()->year + 1)
                                            ->default(now()->year)
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->helperText('Tahun periode slip gaji')
                                            ->validationMessages([
                                                'required' => 'Harap isi tahun',
                                                'numeric' => 'Harus berupa angka',
                                                'min_value' => 'Minimal tahun 2000',
                                                'max_value' => 'Maksimal tahun depan'
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->columnSpan(2),
                    
                Section::make('Komponen Slip Gaji')
                    ->schema([
                        Forms\Components\Repeater::make('payslipComponents')
                            ->label('Komponen Gaji')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('component_id')
                                    ->label('Komponen')
                                    ->required()
                                    ->searchable()
                                    ->options(function (callable $get) {
                                        return SalaryComponent::with('componentType')
                                            ->get()
                                            ->mapWithKeys(function ($component) {
                                                $typeEmoji = $component->componentType?->name === 'Pendapatan' ? 
                                                           '➕' : '➖';
                                                return [$component->component_id => "{$typeEmoji} {$component->name}"];
                                            });
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $component = SalaryComponent::find($state);
                                            if ($component) {
                                                $set('amount', $component->default_amount ?? 0);
                                            }
                                        }
                                    })
                                    ->reactive()
                                    ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                        // Jika record memiliki component_id, pastikan tidak muncul di opsi lain
                                        if ($record && $record->component_id) {
                                            $component->disableOptionWhen(
                                                fn ($value) => $value != $record->component_id && 
                                                    PayslipComponent::where('payslip_id', $record->payslip_id)
                                                    ->where('component_id', $value)
                                                    ->exists()
                                            );
                                        }
                                    })
                                    ->validationMessages([
                                        'required' => 'Harap pilih komponen gaji'
                                    ]),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0) 
                                    ->step(10000)
                                    ->reactive()
                                    ->prefixIcon('heroicon-o-currency-rupee')
                                    ->helperText('Jumlah dalam Rupiah')
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                        static::recalculatePayslipTotals($get, $set);
                                    })
                                    ->validationMessages([
                                        'required' => 'Harap isi jumlah',
                                        'numeric' => 'Harus berupa angka',
                                        'min_value' => 'Tidak boleh negatif'
                                    ]),
                            ])
                            ->columns(2)
                            ->itemLabel(function (array $state): ?string {
                                if (!isset($state['component_id'])) {
                                    return null;
                                }
                                
                                $component = SalaryComponent::find($state['component_id']);
                                if (!$component) {
                                    return null;
                                }
                                
                                $amount = $state['amount'] ?? 0;
                                $formattedAmount = number_format($amount, 0, ',', '.');
                                
                                $typeEmoji = $component->componentType?->name === 'Pendapatan' ? 
                                             '➕' : '➖';
                                
                                return "{$typeEmoji} {$component->name}: Rp {$formattedAmount}";
                            })
                            ->addActionLabel('Tambah Komponen Gaji')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->maxItems(function () {
                                return SalaryComponent::count();
                            })
                            ->minItems(0)
                            ->live(true)
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                static::recalculatePayslipTotals($get, $set);
                            })
                            ->helperText('Catatan: Jika komponen yang sama ditambahkan lebih dari sekali, hanya komponen pertama yang akan disimpan.'),
                    ])
                    ->columns(1)
                    ->columnSpan(2)
                    ->collapsed(false),
                
                Section::make('Ringkasan Gaji')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_earnings')
                                    ->label('Total Pendapatan')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0)
                                    ->prefixIcon('heroicon-o-arrow-trending-up')
                                    ->dehydrated(),
                                    
                                Forms\Components\TextInput::make('total_deductions')
                                    ->label('Total Potongan')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0)
                                    ->prefixIcon('heroicon-o-arrow-trending-down')
                                    ->dehydrated(),
                                    
                                Forms\Components\TextInput::make('net_salary')
                                    ->label('Gaji Bersih')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-currency-rupee')
                                    ->helperText('Otomatis dihitung dari pendapatan - potongan'),
                            ]),
                    ])
                    ->columns(1)
                    ->columnSpan(2),
                    
                Section::make('File Slip Gaji')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf_url')
                            ->label('File Slip Gaji')
                            ->directory('payslips')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable()
                            ->helperText('Unggah file PDF slip gaji')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpan(2)
                    ->collapsed(),
                    
                Placeholder::make('')
                    ->content('Pastikan semua kolom wajib (bertanda *) telah terisi')
                    ->extraAttributes(['class' => 'text-lg text-gray-600 font-medium mt-4']),
            ])
            ->columns(2);
    }

    protected static function recalculatePayslipTotals(callable $get, callable $set): void
    {
        $componentsState = $get('payslipComponents') ?? [];
        $totalEarnings = 0;
        $totalDeductions = 0;
        
        if (empty($componentsState)) {
            $set('total_earnings', 0);
            $set('total_deductions', 0);
            $set('net_salary', 0);
            return;
        }
        
        foreach ($componentsState as $index => $componentState) {
            if (!isset($componentState['component_id']) || !isset($componentState['amount'])) {
                continue;
            }
            
            $componentId = $componentState['component_id'];
            $amount = (float) $componentState['amount'];
            
            $component = SalaryComponent::with('componentType')->find($componentId);
            if (!$component) {
                continue;
            }
            
            $componentType = $component->componentType?->name ?? '';
            
            if ($componentType === 'Pendapatan') {
                $totalEarnings += $amount;
            } elseif ($componentType === 'Potongan') {
                $totalDeductions += $amount;
            }
        }
        
        $netSalary = $totalEarnings - $totalDeductions;
        
        $set('total_earnings', $totalEarnings);
        $set('total_deductions', $totalDeductions);
        $set('net_salary', $netSalary);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Nama Karyawan')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-users')
                    ->weight('medium')
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('month')
                    ->label('Bulan')
                    ->formatStateUsing(fn($state) => match($state) {
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                        default => $state,
                    })
                    ->icon('heroicon-o-calendar')
                    ->sortable()
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->icon('heroicon-o-calendar')
                    ->sortable()
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('total_earnings')
                    ->label('Pendapatan')
                    ->money('IDR')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('success')
                    ->sortable()
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('total_deductions')
                    ->label('Potongan')
                    ->money('IDR')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->color('danger')
                    ->sortable()
                    ->size(''),
                    
                Tables\Columns\TextColumn::make('net_salary')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->icon('heroicon-o-currency-rupee')
                    ->color('primary')
                    ->sortable()
                    ->size(''),
                    
                Tables\Columns\IconColumn::make('pdf_url')
                    ->label('Slip Gaji')
                    ->icon(fn ($record) => $record->pdf_url ? 'heroicon-o-document-arrow-down' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => $record->pdf_url ? 'success' : 'danger')
                    ->url(fn ($record) => $record->pdf_url ? asset('storage/' . $record->pdf_url) : null)
                    ->openUrlInNewTab()
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
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn() => array_combine(
                        $years = range(now()->year, 2000),
                        $years
                    ))
                    ->indicator('Tahun'),
                    
                Tables\Filters\SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ])
                    ->indicator('Bulan'),
                    
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->searchable()
                    ->options(Employee::all()->pluck('full_name', 'employee_id'))
                    ->indicator('Karyawan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('Lihat detail slip gaji'),
                    
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit slip gaji'),
                    
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Slip Gaji PDF')
                    ->modalDescription('PDF slip gaji akan dibuat dan disimpan ke sistem. Tindakan ini akan menimpa PDF lama jika sudah ada.')
                    ->modalSubmitActionLabel('Ya, Generate PDF')
                    ->modalCancelActionLabel('Batal')
                    ->action(function ($record) {
                        // Validasi komponen sebelum generate PDF
                        $pdfService = app(\App\Services\PayslipPdfService::class);
                        if (!$pdfService->validatePayslipComponents($record)) {
                            Notification::make()
                                ->title('Komponen slip gaji tidak valid')
                                ->body('Pastikan slip gaji memiliki minimal satu komponen pendapatan.')
                                ->danger()
                                ->send();
                                
                            return;
                        }
                        
                        try {
                            // Generate PDF dan dapatkan path file
                            $pdfPath = $pdfService->generatePayslipPdf($record);
                            
                            Notification::make()
                                ->title('PDF berhasil dibuat')
                                ->body('Slip gaji PDF telah berhasil dibuat dan disimpan.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal membuat PDF')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => $record->pdf_url ? asset('storage/' . $record->pdf_url) : null)
                    ->openUrlInNewTab()
                    ->tooltip('Unduh slip gaji PDF')
                    ->visible(fn ($record) => $record->pdf_url),
                    
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Hapus slip gaji')
                    ->requiresConfirmation()
                    ->modalDescription('Apakah Anda yakin ingin menghapus slip gaji ini?')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generateBulkPdf')
                        ->label('Generate PDF Massal')
                        ->icon('heroicon-o-document')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Generate Slip Gaji PDF Massal')
                        ->modalDescription('Slip gaji PDF akan dibuat untuk semua data yang dipilih.')
                        ->modalSubmitActionLabel('Ya, Generate Semua')
                        ->modalCancelActionLabel('Batal')
                        ->action(function ($records) {
                            $successCount = 0;
                            $failedCount = 0;
                            
                            // Validasi dan generate untuk setiap payslip
                            $pdfService = app(\App\Services\PayslipPdfService::class);
                            
                            foreach ($records as $record) {
                                if (!$pdfService->validatePayslipComponents($record)) {
                                    $failedCount++;
                                    continue;
                                }
                                
                                try {
                                    $pdfService->generatePayslipPdf($record);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $failedCount++;
                                }
                            }
                            
                            // Notifikasi hasil
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('PDF berhasil dibuat')
                                    ->body("Berhasil membuat $successCount PDF slip gaji" . ($failedCount > 0 ? " ($failedCount gagal)" : ''))
                                    ->success()
                                    ->send();
                            }
                            
                            if ($failedCount > 0 && $successCount === 0) {
                                Notification::make()
                                    ->title('Gagal membuat PDF')
                                    ->body("Semua ($failedCount) proses pembuatan PDF gagal")
                                    ->danger()
                                    ->send();
                            }
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal'),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayslipComponentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayslips::route('/'),
            'create' => Pages\CreatePayslip::route('/create'),
            'view' => Pages\ViewPayslip::route('/{record}'),
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }
}
