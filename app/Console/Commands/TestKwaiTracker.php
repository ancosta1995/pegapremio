<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KwaiService;
use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class TestKwaiTracker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kwai:test 
                            {event : Tipo de evento (registration, add-to-cart, purchase)}
                            {--click-id= : Click ID do Kwai (opcional, usa do usuário se não informado)}
                            {--user-id= : ID do usuário para pegar o click_id}
                            {--value= : Valor do evento (para add-to-cart e purchase)}
                            {--currency=BRL : Moeda (padrão BRL)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o envio de eventos para o Kwai Event API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventType = $this->argument('event');
        $clickId = $this->option('click-id');
        $userId = $this->option('user-id');
        $value = $this->option('value');
        $currency = $this->option('currency');

        // Verifica configurações
        $pixelId = SystemSetting::get('kwai_pixel_id', '');
        $accessToken = SystemSetting::get('kwai_access_token', '');

        if (empty($pixelId) || empty($accessToken)) {
            $this->error('❌ Kwai não está configurado!');
            $this->info('Configure no painel admin:');
            $this->info('  - kwai_pixel_id');
            $this->info('  - kwai_access_token');
            return 1;
        }

        $this->info('📊 Configurações do Kwai:');
        $this->info("  Pixel ID: {$pixelId}");
        $this->info("  Access Token: " . substr($accessToken, 0, 10) . '...');
        $this->info("  MMP Code: " . SystemSetting::get('kwai_mmpcode', 'PL'));
        $this->info("  SDK Version: " . SystemSetting::get('kwai_pixel_sdk_version', '9.9.9'));
        $this->info("  Test Mode: " . (SystemSetting::get('kwai_is_test', true) ? 'Sim' : 'Não'));
        $this->newLine();

        // Busca click_id se não foi informado
        if (!$clickId) {
            if ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    $this->error("Usuário #{$userId} não encontrado.");
                    return 1;
                }
                $clickId = $user->kwai_click_id;
            } else {
                // Busca o primeiro usuário com click_id
                $user = User::whereNotNull('kwai_click_id')
                    ->where('kwai_click_id', '!=', '')
                    ->first();
                
                if ($user) {
                    $clickId = $user->kwai_click_id;
                    $this->info("Usando click_id do usuário #{$user->id}: {$clickId}");
                } else {
                    // Tenta usar testToken se estiver em modo teste
                    $testToken = SystemSetting::get('kwai_test_token', '');
                    $isTest = SystemSetting::get('kwai_is_test', true);
                    
                    if ($isTest && !empty($testToken)) {
                        $clickId = $testToken;
                        $this->info("Usando testToken como click_id (modo teste): {$clickId}");
                    } else {
                        $this->error('Nenhum click_id encontrado.');
                        $this->info('Opções:');
                        $this->info('  1. Use --click-id=KWC.abc123...');
                        $this->info('  2. Use --user-id=123 (para pegar do usuário)');
                        $this->info('  3. Configure kwai_test_token no painel admin (para modo teste)');
                        $this->info('  4. Cadastre um usuário com kwai_click_id primeiro');
                        return 1;
                    }
                }
            }
        }

        if (empty($clickId)) {
            $this->error('click_id é obrigatório!');
            return 1;
        }

        // Mapeia o tipo de evento
        $eventName = match($eventType) {
            'registration' => 'EVENT_COMPLETE_REGISTRATION',
            'add-to-cart' => 'EVENT_ADD_TO_CART',
            'purchase' => 'EVENT_PURCHASE',
            default => null,
        };

        if (!$eventName) {
            $this->error("Tipo de evento inválido: {$eventType}");
            $this->info('Tipos válidos: registration, add-to-cart, purchase');
            return 1;
        }

        // Prepara propriedades e valor baseado no evento
        $properties = [];
        $eventValue = null;

        switch ($eventType) {
            case 'registration':
                $properties = [
                    'content_type' => 'user',
                    'content_name' => 'Registro de Usuário',
                    'event_timestamp' => time() * 1000,
                ];
                break;

            case 'add-to-cart':
                $eventValue = $value ? (float) $value : 10.00;
                $properties = [
                    'content_type' => 'product',
                    'content_id' => 'deposito',
                    'content_name' => 'Depósito',
                    'quantity' => 1,
                    'price' => $eventValue,
                    'event_timestamp' => time() * 1000,
                ];
                break;

            case 'purchase':
                $eventValue = $value ? (float) $value : 10.00;
                $properties = [
                    'content_type' => 'product',
                    'content_id' => 'test-' . time(),
                    'content_name' => 'Depósito - Compra Finalizada',
                    'event_timestamp' => time() * 1000,
                ];
                break;
        }

        $this->info("📤 Enviando evento para Kwai...");
        $this->info("  Evento: {$eventName}");
        $this->info("  Click ID: {$clickId}");
        if ($eventValue) {
            $this->info("  Valor: R$ " . number_format($eventValue, 2, ',', '.'));
        }
        $this->info("  Moeda: {$currency}");
        $this->newLine();

        try {
            $kwaiService = new KwaiService();
            $result = $kwaiService->sendEvent(
                clickId: $clickId,
                eventName: $eventName,
                properties: $properties,
                value: $eventValue,
                currency: $eventValue ? $currency : null
            );

            if ($result['success']) {
                $this->info('✅ Evento enviado com sucesso!');
                $this->info('Resposta:');
                $this->line(json_encode($result['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                if (isset($result['http_code'])) {
                    $this->info("HTTP Code: {$result['http_code']}");
                }
            } else {
                $this->error('❌ Erro ao enviar evento:');
                
                $errorMsg = $result['error'] ?? 'Erro desconhecido';
                $resultCode = $result['result_code'] ?? null;
                
                // Traduz mensagens comuns de erro
                $errorTranslations = [
                    '内部错误，请稍后重试' => 'Erro interno. Tente novamente mais tarde.',
                    'callback字段不合法' => 'Campo callback inválido.',
                ];
                
                $translatedMsg = $errorTranslations[$errorMsg] ?? $errorMsg;
                $this->error($translatedMsg);
                
                if ($resultCode) {
                    $this->error("Código de erro: {$resultCode}");
                    if ($errorMsg !== $translatedMsg) {
                        $this->info("Mensagem original: {$errorMsg}");
                    }
                }
                
                if (isset($result['response'])) {
                    $this->info('Resposta completa:');
                    $this->line(json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                
                if (isset($result['http_code'])) {
                    $this->error("HTTP Code: {$result['http_code']}");
                }
                
                $this->newLine();
                
                // Mensagens específicas por código de erro
                $resultCode = $result['result_code'] ?? null;
                if ($resultCode == 20001) {
                    $this->warn('💡 Erro 20001: "Erro interno" geralmente significa:');
                    $this->info('  - O click_id pode não ser válido (KWC.123 é apenas um exemplo)');
                    $this->info('  - Use um click_id real gerado pelo Kwai Ads');
                    $this->info('  - O click_id pode ter expirado');
                    $this->info('  - Tente novamente em alguns minutos');
                } elseif ($resultCode == 10005) {
                    $this->warn('💡 Erro 10005: "Campo callback inválido"');
                    $this->info('  - Verifique o formato do payload');
                    $this->info('  - Verifique se todos os campos obrigatórios estão presentes');
                } else {
                    $this->warn('💡 Dicas para resolver:');
                    $this->info('  1. Verifique se o access_token está correto');
                    $this->info('  2. Verifique se o pixel_id está correto');
                    $this->info('  3. Verifique se o click_id é válido e não expirou');
                }
                
                $this->info('  4. Verifique os logs em storage/logs/laravel.log');
                $this->newLine();
                $this->info('📝 Nota: Para testar com um click_id real:');
                $this->info('  1. Acesse sua landing page com ?kwai_click_id=KWC.abc123...');
                $this->info('  2. O click_id será salvo no banco de dados');
                $this->info('  3. Use --user-id=X para usar o click_id de um usuário');
                
                return 1;
            }

            $this->newLine();
            $this->info('💡 Dica: Verifique os logs em storage/logs/laravel.log para mais detalhes');
            $this->info('💡 Dica: Se estiver em modo teste, o evento aparecerá em "Test Events" no painel do Kwai');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Exceção ao enviar evento:');
            $this->error($e->getMessage());
            $this->error("\nStack trace:");
            $this->error($e->getTraceAsString());
            
            return 1;
        }
    }
}

