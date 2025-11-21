<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationLabel = 'Solicitações de Saque';

    protected static ?int $navigationSort = 5; // Depois de Histórico de Depósitos

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    protected static ?string $modelLabel = 'Solicitação de Saque';

    protected static ?string $pluralModelLabel = 'Solicitações de Saque';

    public static function form(FormSchema $schema): FormSchema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('Valor')
                    ->numeric()
                    ->prefix('R$')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('pix_key_type')
                    ->label('Tipo de Chave PIX')
                    ->options([
                        'CPF' => 'CPF',
                        'EMAIL' => 'E-mail',
                        'PHONE' => 'Telefone',
                        'RANDOM' => 'Chave Aleatória',
                    ])
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('pix_key')
                    ->label('Chave PIX')
                    ->required()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'processing' => 'Processando',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Motivo da Rejeição')
                    ->rows(3)
                    ->visible(fn ($record) => $record && in_array($record->status, ['rejected', 'canceled'])),
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
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pix_key_type')
                    ->label('Tipo PIX')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('pix_key')
                    ->label('Chave PIX')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->pix_key),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'canceled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'processing' => 'Processando',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('rollover_progress_at_time')
                    ->label('Rollover')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 1) . '%' : '-')
                    ->sortable(),
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
                        'processing' => 'Processando',
                        'approved' => 'Aprovado',
                        'rejected' => 'Rejeitado',
                        'canceled' => 'Cancelado',
                    ]),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprovar Saque')
                    ->modalDescription('Tem certeza que deseja aprovar este saque? O valor será processado.')
                    ->action(function (Withdrawal $record) {
                        $record->status = 'approved';
                        $record->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Saque aprovado com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Withdrawal $record) => in_array($record->status, ['pending', 'pending_fee'])),
                Actions\Action::make('reject')
                    ->label('Rejeitar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeitar Saque')
                    ->modalDescription('Tem certeza que deseja rejeitar este saque? O saldo será devolvido ao usuário.')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo da Rejeição')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Withdrawal $record, array $data) {
                        // Só devolve o saldo se o saque ainda não foi processado (pending ou processing)
                        // Se já foi aprovado, não devolve (já foi pago)
                        if (in_array($record->status, ['pending', 'pending_fee', 'processing'])) {
                            // Devolve o saldo ao usuário
                            $user = $record->user;
                            $user->balance += $record->amount;
                            $user->save();
                            
                            \Illuminate\Support\Facades\Log::info('Withdrawal rejected - balance returned', [
                                'withdrawal_id' => $record->id,
                                'user_id' => $user->id,
                                'amount' => $record->amount,
                                'new_balance' => $user->balance,
                            ]);
                        }
                        
                        $record->status = 'rejected';
                        $record->rejection_reason = $data['rejection_reason'];
                        $record->save();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Saque rejeitado')
                            ->body(in_array($record->getOriginal('status'), ['pending', 'pending_fee', 'processing']) 
                                ? 'O saldo foi devolvido ao usuário.' 
                                : 'Status atualizado.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Withdrawal $record) => in_array($record->status, ['pending', 'pending_fee', 'processing'])),
                Actions\EditAction::make()
                    ->modalHeading('Editar Solicitação de Saque')
                    ->modalWidth('md'),
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
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }
}

