<?php

namespace App\Filament\Resources\GameMultiplierResource\Pages;

use App\Filament\Resources\GameMultiplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRealMultipliers extends ListRecords
{
    protected static string $resource = GameMultiplierResource::class;

    protected static ?string $title = 'Multiplicadores Reais';

    protected static ?string $navigationLabel = 'Multiplicadores Reais';

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
        return 3; // Ordem no menu
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Novo Multiplicador Real')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Criar')
                ->modalCancelActionLabel('Cancelar')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['is_demo'] = false;
                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->where('is_demo', false);
    }
}

