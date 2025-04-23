<?php

namespace App\Filament\Resources\PayslipResource\Pages;

use App\Filament\Resources\PayslipResource;
use App\Models\SalaryComponent;
use App\Models\PayslipComponent;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePayslip extends CreateRecord
{
    protected static string $resource = PayslipResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan gaji bersih dihitung ulang dari komponen-komponen yang diinput
        if (isset($data['payslipComponents']) && is_array($data['payslipComponents'])) {
            $totalEarnings = 0;
            $totalDeductions = 0;
            
            foreach ($data['payslipComponents'] as $component) {
                if (!isset($component['component_id']) || !isset($component['amount'])) {
                    continue;
                }
                
                $salaryComponent = SalaryComponent::with('componentType')->find($component['component_id']);
                if (!$salaryComponent) {
                    continue;
                }
                
                $componentType = $salaryComponent->componentType?->name ?? '';
                $amount = (float) $component['amount'];
                
                if ($componentType === 'Pendapatan') {
                    $totalEarnings += $amount;
                } elseif ($componentType === 'Potongan') {
                    $totalDeductions += $amount;
                }
            }
            
            $data['total_earnings'] = $totalEarnings;
            $data['total_deductions'] = $totalDeductions;
            $data['net_salary'] = $totalEarnings - $totalDeductions;
        }
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        // Gunakan database transactions untuk memastikan integritas data
        return DB::transaction(function () use ($data) {
            // Filter out nested data before creating main record
            $payslipData = collect($data)->except('payslipComponents')->toArray();
            
            // Create the payslip record
            $payslip = static::getModel()::create($payslipData);
            
            // Jika ada komponen payslip, tambahkan secara langsung tanpa menunggu afterCreate
            if (isset($data['payslipComponents']) && is_array($data['payslipComponents'])) {
                // Cek komponen duplikat
                $componentIds = collect($data['payslipComponents'])
                    ->pluck('component_id')
                    ->filter()
                    ->toArray();
                
                // Jika ada komponen duplikat, hanya gunakan yang pertama
                $uniqueComponentIds = array_unique($componentIds);
                $processedIds = [];
                
                foreach ($data['payslipComponents'] as $component) {
                    if (!isset($component['component_id']) || !isset($component['amount'])) {
                        continue;
                    }
                    
                    // Skip jika komponen ini sudah diproses
                    if (in_array($component['component_id'], $processedIds)) {
                        continue;
                    }
                    
                    PayslipComponent::create([
                        'payslip_id' => $payslip->payslip_id,
                        'component_id' => $component['component_id'],
                        'amount' => $component['amount']
                    ]);
                    
                    // Tandai komponen ini sudah diproses
                    $processedIds[] = $component['component_id'];
                }
            }
            
            return $payslip;
        });
    }
    
    protected function afterCreate(): void
    {
        $payslip = $this->record;
        
        // Karena komponen sudah dibuat di handleRecordCreation, kita hanya perlu menghitung ulang total
        if ($payslip) {
            // Hitung ulang total
            $payslip->refresh();
            $payslip->loadMissing(['payslipComponents', 'payslipComponents.component', 'payslipComponents.component.componentType']);
            
            // Memastikan total-total sudah dihitung dengan benar
            \App\Models\Payslip::recalculateFromComponents($payslip);
        }
    }
}
