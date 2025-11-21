<?php

namespace App\Filament\Resources\GameHistoryResource\Pages;

use App\Filament\Resources\GameHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameHistories extends ListRecords
{
    protected static string $resource = GameHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

