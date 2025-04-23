<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\SalaryComponent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;

class PayslipPdfService
{
    /**
     * Generate PDF untuk slip gaji
     *
     * @param Payslip $payslip
     * @return string Path ke file yang telah disimpan
     */
    public function generatePayslipPdf(Payslip $payslip): string
    {
        // Load relasi yang diperlukan
        $payslip->load([
            'employee', 
            'employee.position', 
            'employee.department',
            'payslipComponents', 
            'payslipComponents.component', 
            'payslipComponents.component.componentType'
        ]);
        
        // Kelompokkan komponen berdasarkan tipe
        $groupedComponents = $this->groupComponentsByType($payslip);
        
        // Buat nama file yang unik dengan struktur folder yang bagus
        $fileName = $this->getPayslipFileName($payslip);
        $folderPath = $this->getPayslipFolderPath($payslip);
        $fullPath = $folderPath . '/' . $fileName;
        
        // Data untuk view
        $data = [
            'payslip' => $payslip,
            'earningComponents' => $groupedComponents['earnings'] ?? collect(),
            'deductionComponents' => $groupedComponents['deductions'] ?? collect(),
            'bulanText' => $this->getNamaBulan($payslip->month),
            'tanggalCetak' => now()->translatedFormat('d F Y'),
            'logoUrl' => public_path('images/company-logo.png'), // Pastikan logo ada di folder public/images
            'signature' => public_path('images/signature.png'), // Pastikan tanda tangan ada di folder public/images
        ];
        
        // Coba setiap metode secara berurutan sampai berhasil
        $methods = [
            'generateWithDompdf',
            'generateWithBarryvdhDompdf',
            'generateWithPHPDompdf',
            'generateWithHelper',
        ];
        
        // Mencoba beberapa metode secara berurutan
        $lastException = null;
        foreach ($methods as $method) {
            try {
                return $this->$method($data, $payslip, $fullPath, $folderPath);
            } catch (\Exception $e) {
                $lastException = $e;
                // Catat error tapi lanjutkan ke metode berikutnya
                \Illuminate\Support\Facades\Log::error("PDF Generation failed with {$method}: " . $e->getMessage());
            }
        }
        
        // Jika semua metode gagal, lempar exception
        throw new \Exception("Semua metode generate PDF gagal: " . $lastException->getMessage());
    }
    
    /**
     * Metode 1: Generate PDF menggunakan Dompdf langsung
     */
    private function generateWithDompdf(array $data, Payslip $payslip, string $fullPath, string $folderPath): string
    {
        // Render view ke HTML
        $html = view('pdf.payslip', $data)->render();
        
        // Konfigurasi DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');
        
        // Inisialisasi DomPDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4');
        $dompdf->render();
        
        $output = $dompdf->output();
        
        // Pastikan direktori ada
        Storage::disk('public')->makeDirectory($folderPath);
        
        // Simpan PDF ke storage
        Storage::disk('public')->put($fullPath, $output);
        
        // Update record payslip dengan URL pdf
        $payslip->update(['pdf_url' => $fullPath]);
        
        return $fullPath;
    }
    
    /**
     * Metode 2: Generate PDF menggunakan barryvdh/laravel-dompdf dengan pendekatan alternatif
     */
    private function generateWithBarryvdhDompdf(array $data, Payslip $payslip, string $fullPath, string $folderPath): string
    {
        // Gunakan facade dengan namespace lengkap
        $pdf = \Barryvdh\DomPdf\Facade\Pdf::loadView('pdf.payslip', $data);
        $pdf->setPaper('a4');
        
        // Pastikan direktori ada
        Storage::disk('public')->makeDirectory($folderPath);
        
        // Simpan PDF ke storage
        Storage::disk('public')->put($fullPath, $pdf->output());
        
        // Update record payslip dengan URL pdf
        $payslip->update(['pdf_url' => $fullPath]);
        
        return $fullPath;
    }
    
    /**
     * Metode 3: Generate PDF dengan PHP-only approach sebagai fallback terakhir
     */
    private function generateWithPHPDompdf(array $data, Payslip $payslip, string $fullPath, string $folderPath): string
    {
        // Render view ke HTML
        $html = view('pdf.payslip', $data)->render();
        
        // Inisialisasi DomPDF dengan cara alternatif
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper('A4');
        $dompdf->setOptions(new \Dompdf\Options([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif'
        ]));
        $dompdf->loadHtml($html);
        $dompdf->render();
        
        $output = $dompdf->output();
        
        // Pastikan direktori ada
        Storage::disk('public')->makeDirectory($folderPath);
        
        // Simpan PDF ke storage
        Storage::disk('public')->put($fullPath, $output);
        
        // Update record payslip dengan URL pdf
        $payslip->update(['pdf_url' => $fullPath]);
        
        return $fullPath;
    }
    
    /**
     * Metode 4: Generate PDF menggunakan helper kustom
     */
    private function generateWithHelper(array $data, Payslip $payslip, string $fullPath, string $folderPath): string
    {
        // Gunakan custom facade untuk generate PDF
        $output = \App\Facades\PDF::generatePdf('pdf.payslip', $data, 'a4');
        
        // Pastikan direktori ada
        Storage::disk('public')->makeDirectory($folderPath);
        
        // Simpan PDF ke storage
        Storage::disk('public')->put($fullPath, $output);
        
        // Update record payslip dengan URL pdf
        $payslip->update(['pdf_url' => $fullPath]);
        
        return $fullPath;
    }
    
    /**
     * Mengelompokkan komponen berdasarkan tipe (pendapatan/potongan)
     * 
     * @param Payslip $payslip
     * @return array
     */
    private function groupComponentsByType(Payslip $payslip): array
    {
        $grouped = [
            'earnings' => collect(),
            'deductions' => collect()
        ];
        
        foreach ($payslip->payslipComponents as $component) {
            if (!$component->component || !$component->component->componentType) {
                continue;
            }
            
            $type = $component->component->componentType->name;
            
            if ($type === 'Pendapatan') {
                $grouped['earnings']->push($component);
            } elseif ($type === 'Potongan') {
                $grouped['deductions']->push($component);
            }
        }
        
        return $grouped;
    }
    
    /**
     * Mendapatkan nama bulan dalam Bahasa Indonesia
     * 
     * @param int $month
     * @return string
     */
    private function getNamaBulan(int $month): string
    {
        $namaBulan = [
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
            12 => 'Desember'
        ];
        
        return $namaBulan[$month] ?? 'Unknown';
    }
    
    /**
     * Mendapatkan nama file slip gaji
     * 
     * @param Payslip $payslip
     * @return string
     */
    private function getPayslipFileName(Payslip $payslip): string
    {
        $employeeName = Str::slug($payslip->employee->full_name);
        return "slip_gaji_{$employeeName}_{$payslip->year}_{$payslip->month}.pdf";
    }
    
    /**
     * Mendapatkan struktur folder untuk menyimpan slip gaji
     * 
     * @param Payslip $payslip
     * @return string
     */
    private function getPayslipFolderPath(Payslip $payslip): string
    {
        return "payslips/{$payslip->year}/{$payslip->month}";
    }
    
    /**
     * Validasi komponen slip gaji sebelum generate PDF
     * 
     * @param Payslip $payslip
     * @return bool
     */
    public function validatePayslipComponents(Payslip $payslip): bool
    {
        // Load relasi yang diperlukan jika belum
        if (!$payslip->relationLoaded('payslipComponents')) {
            $payslip->load(['payslipComponents']);
        }
        
        // Validasi bahwa slip gaji memiliki minimal satu komponen
        if ($payslip->payslipComponents->isEmpty()) {
            return false;
        }
        
        // Validasi total pendapatan dan potongan
        $earningsExists = $payslip->payslipComponents->contains(function ($component) {
            return $component->component && 
                  $component->component->componentType && 
                  $component->component->componentType->name === 'Pendapatan';
        });
        
        // Minimal harus ada komponen pendapatan
        return $earningsExists;
    }
} 