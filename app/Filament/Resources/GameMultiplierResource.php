<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameMultiplierResource\Pages;
use App\Models\GameMultiplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameMultiplierResource extends Resource
{
    protected static ?string $model = GameMultiplier::class;

    protected static ?string $navigationLabel = null; // Não aparece, apenas as páginas

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Não aparece no menu, apenas as páginas customizadas
    }

    protected static ?string $modelLabel = 'Multiplicador';

    protected static ?string $pluralModelLabel = 'Multiplicadores';

    public static function form(FormSchema $schema): FormSchema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('multiplier')
                    ->label('Multiplicador')
                    ->numeric()
                    ->required()
                    ->suffix('x'),
                Forms\Components\TextInput::make('probability')
                    ->label('Probabilidade (%)')
                    ->numeric()
                    ->required()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
                Forms\Components\Toggle::make('is_demo')
                    ->label('Para Usuários Demo')
                    ->default(false)
                    ->hidden(), // Oculto pois é definido automaticamente pela aba
                Forms\Components\Toggle::make('active')
                    ->label('Ativo')
                    ->default(true),
                Forms\Components\TextInput::make('order')
                    ->label('Ordem')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('multiplier')
                    ->label('Multiplicador')
                    ->formatStateUsing(fn ($state) => $state . 'x')
                    ->sortable(),
                Tables\Columns\TextColumn::make('probability')
                    ->label('Probabilidade')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_demo')
                    ->label('Tipo')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Ordem')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Ativo'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modalHeading('Editar Multiplicador')
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Salvar')
                    ->modalCancelActionLabel('Cancelar'),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRealMultipliers::route('/'),
            'real' => Pages\ListRealMultipliers::route('/real'),
            'demo' => Pages\ListDemoMultipliers::route('/demo'),
            // Removidas rotas de create/edit - agora usa modais
        ];
    }
}

