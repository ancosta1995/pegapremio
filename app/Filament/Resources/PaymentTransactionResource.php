<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentTransactionResource\Pages;
use App\Models\PaymentTransaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentTransactionResource extends Resource
{
    protected static ?string $model = PaymentTransaction::class;

    protected static ?string $navigationLabel = 'Histórico de Depósitos';

    protected static ?int $navigationSort = 4; // Depois de Histórico de Jogadas

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-credit-card';
    }

    protected static ?string $modelLabel = 'Transação de Pagamento';

    protected static ?string $pluralModelLabel = 'Histórico de Depósitos';

    public static function form(FormSchema $schema): FormSchema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('gateway')
                    ->label('Gateway')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('gateway_transaction_id')
                    ->label('ID da Transação no Gateway')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Valor')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options([
                        'PIX' => 'PIX',
                        'CREDIT_CARD' => 'Cartão de Crédito',
                        'BOLETO' => 'Boleto',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('payment_url')
                    ->label('URL de Pagamento')
                    ->rows(2),
                Forms\Components\Textarea::make('qr_code_text')
                    ->label('Código PIX')
                    ->rows(4),
                Forms\Components\Textarea::make('error_message')
                    ->label('Mensagem de Erro')
                    ->rows(2),
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
                Tables\Columns\TextColumn::make('gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PIX' => 'success',
                        'CREDIT_CARD' => 'warning',
                        'BOLETO' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal_fee' => 'warning',
                        'withdrawal_priority_fee' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'deposit' => 'Depósito',
                        'withdrawal_fee' => 'Taxa de Saque',
                        'withdrawal_priority_fee' => 'Taxa de Prioridade',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'canceled' => 'gray',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway_transaction_id')
                    ->label('ID Gateway')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->gateway_transaction_id),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Método de Pagamento')
                    ->options([
                        'PIX' => 'PIX',
                        'CREDIT_CARD' => 'Cartão de Crédito',
                        'BOLETO' => 'Boleto',
                    ]),
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('Gateway')
                    ->options([
                        'Seedpay' => 'Seedpay',
                    ]),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Tipo de Transação')
                    ->options([
                        'deposit' => 'Depósito',
                        'withdrawal_fee' => 'Taxa de Saque',
                        'withdrawal_priority_fee' => 'Taxa de Prioridade',
                    ]),
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
            'index' => Pages\ListPaymentTransactions::route('/'),
        ];
    }
}

