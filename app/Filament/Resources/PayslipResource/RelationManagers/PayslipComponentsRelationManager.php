<?php

namespace App\Filament\Resources\PayslipResource\RelationManagers;

use App\Models\Payslip;
use App\Models\SalaryComponent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class PayslipComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payslipComponents';

    protected function getTableHeading(): string
    {
        return 'Komponen Slip Gaji';
    }

    protected function getTableDescription(): string
    {
        return 'Tambahkan komponen pendapatan dan potongan untuk perhitungan gaji otomatis.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('component_id')
                    ->label('Komponen Gaji')
                    ->required()
                    ->options(
                        function (RelationManager $livewire) {
                            return SalaryComponent::query()
                                ->with('componentType')
                                ->whereDoesntHave('payslipComponents', function (Builder $query) use ($livewire) {
                                    $query->where('payslip_id', $livewire->getOwnerRecord()->payslip_id);
                                })
                                ->orWhereHas('payslipComponents', function (Builder $query) use ($livewire) {
                                    $query->where('payslip_component_id', $livewire->getOwnerRecord()?->payslip_component_id);
                                })
                                ->get()
                                ->mapWithKeys(function ($component) {
                                    $typeText = $component->componentType?->name === 'Pendapatan' ? '✓ Pendapatan' : '✗ Potongan';
                                    return [$component->component_id => "{$component->name} ({$typeText})"];
                                });
                        }
                    )
                    ->preload()
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $component = SalaryComponent::find($state);
                            if ($component) {
                                $set('amount', $component->default_amount ?? 0);
                            }
                        }
                    })
                    ->helperText('Komponen gaji hanya dapat dipilih sekali per slip gaji'),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->step(10000)
                    ->reactive()
                    ->helperText('Masukkan jumlah untuk komponen gaji ini')
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payslip_component_id')
            ->heading('Komponen Slip Gaji')
            ->description('Komponen pendapatan dan potongan untuk perhitungan gaji otomatis')
            ->columns([
                Tables\Columns\TextColumn::make('component.name')
                    ->label('Komponen')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('component.componentType.name')
                    ->label('Jenis')
                    ->formatStateUsing(function ($state, $record) {
                        $type = $record->component->componentType->name ?? 'Pendapatan';
                        return $type === 'Pendapatan' ? 'Pendapatan' : 'Potongan';
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        $type = $record->component->componentType->name ?? 'Pendapatan';
                        return $type === 'Pendapatan' ? 'success' : 'danger';
                    }),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Komponen')
                    ->options([
                        'Pendapatan' => 'Pendapatan',
                        'Potongan' => 'Potongan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $type) => $query->whereHas(
                                'component.componentType',
                                fn (Builder $query) => $query->where('name', $type)
                            )
                        );
                    })
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($livewire) {
                        $this->updatePayslipTotals($livewire->getOwnerRecord());
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($livewire) {
                        $this->updatePayslipTotals($livewire->getOwnerRecord());
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($livewire) {
                        $this->updatePayslipTotals($livewire->getOwnerRecord());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($livewire) {
                            $this->updatePayslipTotals($livewire->getOwnerRecord());
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada komponen gaji')
            ->emptyStateDescription('Tambahkan komponen pendapatan dan potongan untuk menghitung gaji otomatis')
            ->emptyStateIcon('heroicon-o-calculator');
    }

    protected function updatePayslipTotals($payslip): void
    {
        // Pastikan payslip ada
        if (!$payslip) {
            return;
        }
        
        // Gunakan metode dari model Payslip untuk menghitung ulang total
        Payslip::recalculateFromComponents($payslip);
    }
}
