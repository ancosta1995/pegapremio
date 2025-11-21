<?php

namespace App\Filament\Resources\GameMultiplierResource\Pages;

use App\Filament\Resources\GameMultiplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameMultiplier extends EditRecord
{
    protected static string $resource = GameMultiplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

