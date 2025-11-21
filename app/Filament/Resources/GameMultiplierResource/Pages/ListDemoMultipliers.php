<?php

namespace App\Filament\Resources\GameMultiplierResource\Pages;

use App\Filament\Resources\GameMultiplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDemoMultipliers extends ListRecords
{
    protected static string $resource = GameMultiplierResource::class;

    protected static ?string $title = 'Multiplicadores Demo';

    protected static ?string $navigationLabel = 'Multiplicadores Demo';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return null; // Sem grupo para aparecer no nível principal
    }

    public static function getNavigationSort(): ?int
    {
        return 4; // Ordem no menu (logo após Reais)
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Novo Multiplicador Demo')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Criar')
                ->modalCancelActionLabel('Cancelar')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['is_demo'] = true;
                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->where('is_demo', true);
    }
}

