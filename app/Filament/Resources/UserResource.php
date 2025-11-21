<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?int $navigationSort = 2; // Logo após Dashboard (1)

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    protected static ?string $modelLabel = 'Usuário';

    protected static ?string $pluralModelLabel = 'Usuários';

    public static function form(FormSchema $schema): FormSchema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->maxLength(20)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('document')
                    ->label('CPF')
                    ->maxLength(255)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->hidden(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('referral_code')
                    ->label('Código de Referência')
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('referred_by')
                    ->label('Indicado Por')
                    ->maxLength(10)
                    ->disabled(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('balance')
                    ->label('Saldo')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                Forms\Components\TextInput::make('balance_bonus')
                    ->label('Saldo Bônus')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                Forms\Components\TextInput::make('balance_ref')
                    ->label('Saldo de Afiliado')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                Forms\Components\TextInput::make('cpa')
                    ->label('CPA')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                Forms\Components\Toggle::make('is_demo')
                    ->label('Usuário Demo')
                    ->default(false),
                Forms\Components\Toggle::make('is_admin')
                    ->label('Administrador')
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_demo')
                    ->label('Demo')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_demo')
                    ->label('Usuário Demo'),
                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Administrador'),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modalHeading('Editar Usuário')
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Salvar')
                    ->modalCancelActionLabel('Cancelar'),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            // Removidas rotas de create/edit - agora usa modais
        ];
    }
}

