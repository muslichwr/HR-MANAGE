<?php

namespace App\Filament\Resources\PayslipResource\Pages;

use App\Filament\Resources\PayslipResource;
use App\Services\PayslipPdfService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;


class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('generatePdf')
                ->label('Generate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Slip Gaji PDF')
                ->modalDescription('PDF slip gaji akan dibuat dan disimpan ke sistem. Tindakan ini akan menimpa PDF lama jika sudah ada.')
                ->modalSubmitActionLabel('Ya, Generate PDF')
                ->modalCancelActionLabel('Batal')
                ->action(function () {
                    $payslip = $this->record;
                    
                    // Validasi komponen sebelum generate PDF
                    $pdfService = app(PayslipPdfService::class);
                    if (!$pdfService->validatePayslipComponents($payslip)) {
                        Notification::make()
                            ->title('Komponen slip gaji tidak valid')
                            ->body('Pastikan slip gaji memiliki minimal satu komponen pendapatan.')
                            ->danger()
                            ->send();
                            
                        return;
                    }
                    
                    try {
                        // Generate PDF dan dapatkan path file
                        $pdfPath = $pdfService->generatePayslipPdf($payslip);
                        $pdfUrl = asset('storage/' . $pdfPath);
                        
                        Notification::make()
                            ->title('PDF berhasil dibuat')
                            ->body('Slip gaji PDF telah berhasil dibuat dan disimpan.')
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Download PDF')
                                    ->button()
                                    ->color('primary')
                                    ->extraAttributes(['target' => '_blank'])
                                    ->close()
                                    ->url($pdfUrl, shouldOpenInNewTab: true),
                            ])
                            ->send();
                            
                        // Refresh halaman untuk menampilkan perubahan
                        $this->refresh();
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Gagal membuat PDF')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn () => $this->record->pdf_url ? asset('storage/' . $this->record->pdf_url) : null, shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->pdf_url),
        ];
    }
}
