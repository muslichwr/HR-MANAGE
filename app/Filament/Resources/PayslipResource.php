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

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Payroll Management';
    protected static ?string $navigationLabel = 'Slip Gaji';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Slip Gaji')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Karyawan')
                            ->required()
                            ->searchable()
                            ->options(Employee::all()->pluck('full_name', 'employee_id'))
                            ->native(false)
                            ->helperText('Pilih karyawan dari daftar')
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
                                'unique' => 'Slip gaji untuk karyawan ini pada periode yang sama sudah ada'
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('month')
                                    ->label('Bulan')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->default(now()->month),
                                    
                                Forms\Components\TextInput::make('year')
                                    ->label('Tahun')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(now()->year + 1)
                                    ->default(now()->year),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('Komponen Slip Gaji')
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
                                    }),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0) 
                                    ->step(10000)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                        static::recalculatePayslipTotals($get, $set);
                                    }),
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
                    ->collapsed(false),
                
                Forms\Components\Section::make('Ringkasan Gaji')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('total_earnings')
                                    ->label('Total Pendapatan')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0)
                                    ->dehydrated(),
                                    
                                Forms\Components\TextInput::make('total_deductions')
                                    ->label('Total Potongan')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->readOnly()
                                    ->default(0)
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
                                    ->helperText('Otomatis dihitung dari pendapatan - potongan'),
                            ]),
                    ]),
                    
                Forms\Components\Section::make('File Slip Gaji')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf_url')
                            ->label('File Slip Gaji')
                            ->directory('payslips')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['application/pdf'])
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
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
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->formatStateUsing(fn($record) => "{$record->month}/{$record->year}")
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('year', $direction)->orderBy('month', $direction);
                    }),
                    
                Tables\Columns\TextColumn::make('total_earnings')
                    ->label('Pendapatan')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_deductions')
                    ->label('Potongan')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('net_salary')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('pdf_url')
                    ->label('Slip Gaji')
                    ->icon(fn ($record) => $record->pdf_url ? 'heroicon-o-document-arrow-down' : 'heroicon-o-x-circle')
                    ->color(fn ($record) => $record->pdf_url ? 'success' : 'danger')
                    ->url(fn ($record) => $record->pdf_url ? asset('storage/' . $record->pdf_url) : null)
                    ->openUrlInNewTab(),
                    
                Tables\Columns\TextColumn::make('payslipComponents_count')
                    ->label('Komponen')
                    ->counts('payslipComponents')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn() => array_combine(
                        $years = range(now()->year, 2000),
                        $years
                    )),
                    
                Tables\Filters\SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ]),
                    
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->searchable()
                    ->options(Employee::all()->pluck('full_name', 'employee_id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => $record->pdf_url ? asset('storage/' . $record->pdf_url) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->pdf_url),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc');
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
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }
}
