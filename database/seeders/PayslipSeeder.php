<?php

namespace Database\Seeders;

use App\Models\Payslip;
use App\Models\PayslipComponent;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Models\ComponentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayslipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada tipe komponen
        if (ComponentType::count() == 0) {
            $earningType = ComponentType::create([
                'name' => 'Pendapatan'
            ]);
            
            $deductionType = ComponentType::create([
                'name' => 'Potongan'
            ]);
        } else {
            $earningType = ComponentType::where('name', 'Pendapatan')->first();
            $deductionType = ComponentType::where('name', 'Potongan')->first();
        }
        
        // Pastikan ada komponen gaji
        if (SalaryComponent::count() == 0) {
            // Komponen Pendapatan
            SalaryComponent::create([
                'type_id' => $earningType->type_id,
                'name' => 'Gaji Pokok',
                'description' => 'Gaji pokok bulanan',
                'default_amount' => 5000000
            ]);
            
            SalaryComponent::create([
                'type_id' => $earningType->type_id,
                'name' => 'Tunjangan Transportasi',
                'description' => 'Tunjangan transportasi bulanan',
                'default_amount' => 500000
            ]);
            
            SalaryComponent::create([
                'type_id' => $earningType->type_id,
                'name' => 'Tunjangan Makan',
                'description' => 'Tunjangan makan bulanan',
                'default_amount' => 600000
            ]);
            
            // Komponen Potongan
            SalaryComponent::create([
                'type_id' => $deductionType->type_id,
                'name' => 'BPJS Kesehatan',
                'description' => 'Potongan untuk BPJS Kesehatan',
                'default_amount' => 100000
            ]);
            
            SalaryComponent::create([
                'type_id' => $deductionType->type_id,
                'name' => 'BPJS Ketenagakerjaan',
                'description' => 'Potongan untuk BPJS Ketenagakerjaan',
                'default_amount' => 85000
            ]);
            
            SalaryComponent::create([
                'type_id' => $deductionType->type_id,
                'name' => 'PPh 21',
                'description' => 'Potongan pajak penghasilan',
                'default_amount' => 250000
            ]);
        }
        
        // Ambil beberapa karyawan untuk di-assign slip gaji
        $employees = Employee::take(3)->get();
        
        if ($employees->isEmpty()) {
            $this->command->info('Tidak ada karyawan. Silakan jalankan EmployeeSeeder terlebih dahulu.');
            return;
        }
        
        // Ambil komponen gaji
        $components = SalaryComponent::with('componentType')->get();
        
        // Buat slip gaji untuk bulan ini
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        foreach ($employees as $employee) {
            // Cek jika slip gaji sudah ada
            $existingPayslip = Payslip::where('employee_id', $employee->employee_id)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->first();
                
            if ($existingPayslip) {
                $this->command->info("Slip gaji untuk {$employee->full_name} pada bulan {$currentMonth}/{$currentYear} sudah ada.");
                continue;
            }
            
            // Buat slip gaji baru
            $payslip = Payslip::create([
                'employee_id' => $employee->employee_id,
                'month' => $currentMonth,
                'year' => $currentYear,
                'total_earnings' => 0, // Awalnya kosong, akan dihitung berdasarkan komponen
                'total_deductions' => 0, // Awalnya kosong, akan dihitung berdasarkan komponen
                'net_salary' => 0, // Awalnya kosong, akan dihitung berdasarkan komponen
            ]);
            
            // Generate beberapa komponen acak
            $totalEarnings = 0;
            $totalDeductions = 0;
            
            // Tambahkan komponen pendapatan
            $earningComponents = $components->where('componentType.name', 'Pendapatan');
            foreach ($earningComponents as $component) {
                // Acak jumlah antara 80% dan 120% dari default_amount
                $amount = $component->default_amount * (rand(80, 120) / 100);
                
                PayslipComponent::create([
                    'payslip_id' => $payslip->payslip_id,
                    'component_id' => $component->component_id,
                    'amount' => $amount
                ]);
                
                $totalEarnings += $amount;
            }
            
            // Tambahkan komponen potongan
            $deductionComponents = $components->where('componentType.name', 'Potongan');
            foreach ($deductionComponents as $component) {
                // Acak jumlah antara 80% dan 120% dari default_amount
                $amount = $component->default_amount * (rand(80, 120) / 100);
                
                PayslipComponent::create([
                    'payslip_id' => $payslip->payslip_id,
                    'component_id' => $component->component_id,
                    'amount' => $amount
                ]);
                
                $totalDeductions += $amount;
            }
            
            // Update totals
            $payslip->update([
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $totalEarnings - $totalDeductions
            ]);
            
            $this->command->info("Slip gaji untuk {$employee->full_name} pada bulan {$currentMonth}/{$currentYear} berhasil dibuat.");
        }
    }
}
