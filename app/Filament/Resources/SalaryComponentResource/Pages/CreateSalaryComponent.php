<?php

namespace App\Filament\Resources\SalaryComponentResource\Pages;

use App\Filament\Resources\SalaryComponentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalaryComponent extends CreateRecord
{
    protected static string $resource = SalaryComponentResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function getTitle(): string
    {
        return 'Tambah Komponen Gaji';
    }
}
