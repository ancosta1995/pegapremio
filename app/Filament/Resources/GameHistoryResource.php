<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameHistoryResource\Pages;
use App\Models\GameHistory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Table;

class GameHistoryResource extends Resource
{
    protected static ?string $model = GameHistory::class;

    protected static ?string $navigationLabel = 'Histórico de Jogadas';

    protected static ?int $navigationSort = 3; // Depois de Usuários

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-clock';
    }

    protected static ?string $modelLabel = 'Jogada';

    protected static ?string $pluralModelLabel = 'Histórico de Jogadas';

    public static function form(FormSchema $schema): FormSchema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('bet_amount')
                    ->label('Valor da Aposta')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),
                Forms\Components\Select::make('collision_type')
                    ->label('Tipo de Colisão')
                    ->options([
                        'bomb' => 'Bomba',
                        'prize' => 'Prêmio',
                        'none' => 'Nenhum',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_win')
                    ->label('Ganhou')
                    ->default(false),
                Forms\Components\TextInput::make('win_amount')
                    ->label('Valor Ganho')
                    ->numeric()
                    ->prefix('R$')
                    ->default(0),
                Forms\Components\TextInput::make('multiplier')
                    ->label('Multiplicador')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('balance_before')
                    ->label('Saldo Antes')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),
                Forms\Components\TextInput::make('balance_after')
                    ->label('Saldo Depois')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),
                Forms\Components\Toggle::make('is_demo')
                    ->label('Usuário Demo')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bet_amount')
                    ->label('Aposta')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('collision_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bomb' => 'danger',
                        'prize' => 'success',
                        'none' => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_win')
                    ->label('Ganhou')
                    ->boolean(),
                Tables\Columns\TextColumn::make('win_amount')
                    ->label('Ganho')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('multiplier')
                    ->label('Multiplicador')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state . 'x' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Saldo Final')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_demo')
                    ->label('Demo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collision_type')
                    ->label('Tipo de Colisão')
                    ->options([
                        'bomb' => 'Bomba',
                        'prize' => 'Prêmio',
                        'none' => 'Nenhum',
                    ]),
                Tables\Filters\TernaryFilter::make('is_win')
                    ->label('Ganhou'),
                Tables\Filters\TernaryFilter::make('is_demo')
                    ->label('Usuário Demo'),
            ])
            ->actions([
                //
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListGameHistories::route('/'),
        ];
    }
}

