<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KwaiService
{
    private ?string $pixelId;
    private ?string $accessToken;
    private ?string $testToken;
    private ?string $mmpcode;
    private ?string $pixelSdkVersion;
    private bool $isTest;

    public function __construct()
    {
        // Busca configurações do banco de dados
        $this->pixelId = (string) (SystemSetting::get('kwai_pixel_id', '') ?? '');
        $this->accessToken = (string) (SystemSetting::get('kwai_access_token', '') ?? '');
        $this->testToken = (string) (SystemSetting::get('kwai_test_token', '') ?? '');
        $this->mmpcode = (string) (SystemSetting::get('kwai_mmpcode', 'PL') ?? 'PL');
        $this->pixelSdkVersion = (string) (SystemSetting::get('kwai_pixel_sdk_version', '9.9.9') ?? '9.9.9');
        $this->isTest = (bool) (SystemSetting::get('kwai_is_test', true) ?? true);
        
        // Remove espaços em branco
        $this->pixelId = trim($this->pixelId);
        $this->accessToken = trim($this->accessToken);
        $this->testToken = trim($this->testToken);
    }

    /**
     * Envia evento para a API do Kwai (AdsNebula)
     * 
     * @param string $clickId O click_id capturado
     * @param string $eventName Nome do evento (EVENT_CONTENT_VIEW, EVENT_ADD_TO_CART, EVENT_PURCHASE, EVENT_COMPLETE_REGISTRATION)
     * @param array $properties Propriedades adicionais do evento (opcional)
     * @param float|null $value Valor do evento (opcional)
     * @param string|null $currency Moeda (opcional, padrão BRL)
     * @return array
     */
    public function sendEvent(string $clickId, string $eventName, array $properties = [], ?float $value = null, ?string $currency = 'BRL'): array
    {
        // Valida se está configurado
        if (empty($this->pixelId) || empty($this->accessToken)) {
            Log::warning('Kwai Event API não configurado', [
                'pixel_id_set' => !empty($this->pixelId),
                'access_token_set' => !empty($this->accessToken),
            ]);
            return [
                'success' => false,
                'error' => 'Kwai Event API não configurado. Configure pixel_id e access_token no painel admin.',
            ];
        }

        // Lógica de click_id:
        // 1. Prioridade: usa o clickId passado como parâmetro (vem do banco: $user->kwai_click_id)
        // 2. Fallback (apenas em modo teste): se não tiver clickId, usa testToken
        // 3. Em produção (modo teste desligado): sempre precisa do click_id real da URL
        if (empty($clickId) && $this->isTest && !empty($this->testToken)) {
            $clickId = $this->testToken;
            Log::info('Kwai usando testToken como click_id (modo teste - fallback)', [
                'event_name' => $eventName,
                'test_token' => $this->testToken,
            ]);
        }
        
        // Valida clickId
        if (empty($clickId)) {
            $errorMsg = 'click_id é obrigatório.';
            
            if ($this->isTest) {
                $errorMsg .= ' Configure kwai_test_token no painel admin para testes.';
            } else {
                $errorMsg .= ' O click_id deve ser capturado da URL (?kwai_click_id=...) e salvo no banco.';
            }
            
            Log::warning('Kwai click_id vazio', [
                'event_name' => $eventName,
                'is_test' => $this->isTest,
                'has_test_token' => !empty($this->testToken),
            ]);
            
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }

        try {
            // Endpoint conforme código do cliente
            $url = 'https://www.adsnebula.com/log/common/api';

            // Monta payload conforme código do cliente
            $payload = [
                'access_token' => (string) $this->accessToken,
                'clickid' => (string) $clickId,
                'event_name' => (string) $eventName,
                'pixelId' => (string) $this->pixelId,
                'is_attributed' => 1, // Número, não string
                'mmpcode' => (string) $this->mmpcode,
                'pixelSdkVersion' => (string) $this->pixelSdkVersion,
                'testFlag' => false, // Boolean, não string
                'trackFlag' => $this->isTest ? 1 : 0, // Número: 1 = eventos aparecem em "Test Events"
            ];

            // Adiciona currency e value se fornecidos
            if ($value !== null && $currency !== null) {
                $payload['currency'] = (string) $currency;
                $payload['value'] = (string) number_format($value, 2, '.', '');
            }

            // Adiciona properties se fornecido
            if (!empty($properties)) {
                $payload['properties'] = json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            Log::info('Kwai Event API Request', [
                'event_name' => $eventName,
                'click_id' => $clickId,
                'pixel_id' => $this->pixelId,
                'value' => $value,
                'payload' => $payload,
            ]);

            // Envia para API do AdsNebula
            // Conforme documentação JavaScript, envia como JSON
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json;charset=utf-8',
                ])
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                // Verifica se result === 1 ou '1' (sucesso conforme código do cliente)
                $success = isset($responseData['result']) && ($responseData['result'] === 1 || $responseData['result'] === '1');

                Log::info('Kwai Event API Response', [
                    'event_name' => $eventName,
                    'click_id' => $clickId,
                    'http_code' => $response->status(),
                    'result' => $responseData['result'] ?? null,
                    'success' => $success,
                ]);

                return [
                    'success' => $success,
                    'http_code' => $response->status(),
                    'response' => $responseData,
                ];
            } else {
                $errorMsg = $responseData['error_msg'] ?? $responseData['error'] ?? 'Erro desconhecido';
                
                Log::error('Kwai Event API Error', [
                    'event_name' => $eventName,
                    'click_id' => $clickId,
                    'http_code' => $response->status(),
                    'result_code' => $responseData['result'] ?? null,
                    'error_msg' => $errorMsg,
                    'response' => $responseData,
                    'response_body' => $response->body(),
                    'payload_sent' => $payload,
                    'form_data_sent' => $formData ?? null,
                ]);

                return [
                    'success' => false,
                    'http_code' => $response->status(),
                    'error' => $errorMsg,
                    'result_code' => $responseData['result'] ?? null,
                    'response' => $responseData,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Kwai Event API Exception', [
                'event_name' => $eventName,
                'click_id' => $clickId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro: ' . $e->getMessage(),
            ];
        }
    }
}
