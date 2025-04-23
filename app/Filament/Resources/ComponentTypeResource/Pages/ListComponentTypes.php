<?php

namespace App\Filament\Resources\ComponentTypeResource\Pages;

use App\Filament\Resources\ComponentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComponentTypes extends ListRecords
{
    protected static string $resource = ComponentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
