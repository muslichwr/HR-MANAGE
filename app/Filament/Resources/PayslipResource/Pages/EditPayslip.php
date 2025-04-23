<?php

namespace App\Filament\Resources\PayslipResource\Pages;

use App\Filament\Resources\PayslipResource;
use App\Models\Payslip;
use App\Models\PayslipComponent;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class EditPayslip extends EditRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update payslip data
            $record->update(collect($data)->except('payslipComponents')->toArray());
            
            // Hapus dan buat ulang semua komponen jika ada
            if (isset($data['payslipComponents'])) {
                // Hapus komponen lama
                $record->payslipComponents()->delete();
                
                // Cek komponen duplikat
                $componentIds = collect($data['payslipComponents'])
                    ->pluck('component_id')
                    ->filter()
                    ->toArray();
                
                // Jika ada komponen duplikat, hanya gunakan yang pertama
                $uniqueComponentIds = array_unique($componentIds);
                $processedIds = [];
                
                // Buat komponen baru
                foreach ($data['payslipComponents'] as $component) {
                    if (!isset($component['component_id']) || !isset($component['amount'])) {
                        continue;
                    }
                    
                    // Skip jika komponen ini sudah diproses
                    if (in_array($component['component_id'], $processedIds)) {
                        continue;
                    }
                    
                    PayslipComponent::create([
                        'payslip_id' => $record->payslip_id,
                        'component_id' => $component['component_id'],
                        'amount' => $component['amount']
                    ]);
                    
                    // Tandai komponen ini sudah diproses
                    $processedIds[] = $component['component_id'];
                }
            }
            
            return $record;
        });
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hitung ulang total berdasarkan komponen yang diinput
        if (isset($data['payslipComponents']) && is_array($data['payslipComponents'])) {
            // Akan menggunakan recalculateFromComponents setelah save
            // Karena komponen mungkin memerlukan validasi terlebih dahulu
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Ambil record yang baru disimpan
        $record = $this->getRecord();
        
        // Gunakan metode dari model untuk menghitung ulang total dari komponen
        Payslip::recalculateFromComponents($record);
    }
}
