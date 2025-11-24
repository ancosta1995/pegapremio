<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Schemas\Schema as FormSchema;
use Filament\Schemas\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class SystemSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?int $navigationSort = 98;

    protected static ?string $title = 'Configurações do Sistema';

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_cpa' => SystemSetting::get('default_cpa', 10.00),
            'min_deposit_amount' => SystemSetting::get('min_deposit_amount', 20.00),
            'rollover_requirement' => SystemSetting::get('rollover_requirement', 1),
            'min_withdraw_amount' => SystemSetting::get('min_withdraw_amount', 50.00),
            'withdrawal_fee' => SystemSetting::get('withdrawal_fee', 0.00),
            'withdrawal_priority_fee' => SystemSetting::get('withdrawal_priority_fee', 0.00),
            'presell_bet_amount' => SystemSetting::get('presell_bet_amount', 0.50),
            'presell_free_rounds' => SystemSetting::get('presell_free_rounds', 3),
            'seedpay_public_key' => SystemSetting::get('seedpay_public_key', ''),
            'seedpay_secret_key' => SystemSetting::get('seedpay_secret_key', ''),
            'seedpay_base_url' => SystemSetting::get('seedpay_base_url', 'https://api.paymaker.com.br'),
            'seedpay_webhook_secret' => SystemSetting::get('seedpay_webhook_secret', ''),
            'kwai_pixel_id' => SystemSetting::get('kwai_pixel_id', ''),
            'kwai_access_token' => SystemSetting::get('kwai_access_token', ''),
            'kwai_test_token' => SystemSetting::get('kwai_test_token', ''),
            'kwai_is_test' => SystemSetting::get('kwai_is_test', true),
        ]);
    }

    public function form(FormSchema $form): FormSchema
    {
        return $form
            ->schema([
                Components\Section::make('Financeiro')
                    ->description('Definições aplicadas automaticamente para novos usuários e depósitos.')
                    ->schema([
                        Forms\Components\TextInput::make('default_cpa')
                            ->label('CPA Padrão (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('min_deposit_amount')
                            ->label('Depósito mínimo (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('rollover_requirement')
                            ->label('Rollover (x)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.1)
                            ->required(),
                        Forms\Components\TextInput::make('min_withdraw_amount')
                            ->label('Saque mínimo (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('withdrawal_fee')
                            ->label('Taxa de saque - Validação (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->helperText('Primeira taxa cobrada para validar a conta. Se 0, a taxa fica desativada.'),
                        Forms\Components\TextInput::make('withdrawal_priority_fee')
                            ->label('Taxa de prioridade de saque (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->helperText('Taxa opcional para priorizar o processamento do saque. Só aparece após pagar a primeira taxa. Se 0, a taxa fica desativada.'),
                        Forms\Components\TextInput::make('presell_bet_amount')
                            ->label('Valor da aposta presell (R$)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->helperText('Valor fixo da aposta na página de presell'),
                        Forms\Components\TextInput::make('presell_free_rounds')
                            ->label('Quantidade de rodadas grátis')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->required()
                            ->helperText('Número de rodadas grátis na página de presell'),
                    ]),
                Components\Section::make('Gateway de Pagamento (Seedpay)')
                    ->description('Configurações de integração com o gateway de pagamento Seedpay (whitelabel Paymaker).')
                    ->schema([
                        Forms\Components\TextInput::make('seedpay_public_key')
                            ->label('Public Key')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('seedpay_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('seedpay_base_url')
                            ->label('URL Base da API')
                            ->url()
                            ->default('https://api.paymaker.com.br')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('seedpay_webhook_secret')
                            ->label('Webhook Secret')
                            ->maxLength(255)
                            ->helperText('Secret usado para validar webhooks do Seedpay'),
                    ]),
                Components\Section::make('Tracking (Kwai Event API)')
                    ->description('Configurações de tracking para eventos do Kwai Event API (AdsNebula).')
                    ->schema([
                        Forms\Components\TextInput::make('kwai_pixel_id')
                            ->label('Pixel ID')
                            ->maxLength(255)
                            ->required()
                            ->helperText('ID do pixel do Kwai'),
                        Forms\Components\TextInput::make('kwai_access_token')
                            ->label('Access Token')
                            ->password()
                            ->maxLength(255)
                            ->required()
                            ->helperText('Token de acesso do Kwai'),
                        Forms\Components\TextInput::make('kwai_test_token')
                            ->label('Test Token (Click ID para testes)')
                            ->maxLength(255)
                            ->helperText('Token de teste que pode ser usado como click_id em modo de teste'),
                        Forms\Components\Toggle::make('kwai_is_test')
                            ->label('Modo Teste')
                            ->default(true)
                            ->helperText('Se ativado, eventos aparecem em "Test Events" (trackFlag: true)'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set('default_cpa', $data['default_cpa'], 'decimal');
        SystemSetting::set('min_deposit_amount', $data['min_deposit_amount'], 'decimal');
        SystemSetting::set('rollover_requirement', $data['rollover_requirement'], 'decimal');
        SystemSetting::set('min_withdraw_amount', $data['min_withdraw_amount'], 'decimal');
        SystemSetting::set('withdrawal_fee', $data['withdrawal_fee'], 'decimal');
        SystemSetting::set('withdrawal_priority_fee', $data['withdrawal_priority_fee'], 'decimal');
        SystemSetting::set('presell_bet_amount', $data['presell_bet_amount'], 'decimal');
        SystemSetting::set('presell_free_rounds', $data['presell_free_rounds'], 'integer');
        SystemSetting::set('seedpay_public_key', $data['seedpay_public_key'], 'string');
        SystemSetting::set('seedpay_secret_key', $data['seedpay_secret_key'], 'string');
        SystemSetting::set('seedpay_base_url', $data['seedpay_base_url'], 'string');
        SystemSetting::set('seedpay_webhook_secret', $data['seedpay_webhook_secret'], 'string');
        SystemSetting::set('kwai_pixel_id', $data['kwai_pixel_id'], 'string');
        SystemSetting::set('kwai_access_token', $data['kwai_access_token'], 'string');
        SystemSetting::set('kwai_test_token', $data['kwai_test_token'] ?? '', 'string');
        SystemSetting::set('kwai_is_test', $data['kwai_is_test'], 'boolean');

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->send();
    }
}

