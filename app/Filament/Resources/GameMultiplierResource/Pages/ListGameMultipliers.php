<?php

namespace App\Filament\Resources\GameMultiplierResource\Pages;

use App\Filament\Resources\GameMultiplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameMultipliers extends ListRecords
{
    protected static string $resource = GameMultiplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

