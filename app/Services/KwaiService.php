<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KwaiService
{
    private ?string $pixelId;
    private ?string $accessToken;
    private ?string $mmpcode;
    private ?string $pixelSdkVersion;
    private bool $isTest;

    public function __construct()
    {
        // Busca configurações do banco de dados
        $this->pixelId = (string) (SystemSetting::get('kwai_pixel_id', '') ?? '');
        $this->accessToken = (string) (SystemSetting::get('kwai_access_token', '') ?? '');
        $this->mmpcode = (string) (SystemSetting::get('kwai_mmpcode', 'PL') ?? 'PL');
        $this->pixelSdkVersion = (string) (SystemSetting::get('kwai_pixel_sdk_version', '9.9.9') ?? '9.9.9');
        $this->isTest = (bool) (SystemSetting::get('kwai_is_test', true) ?? true);
        
        // Remove espaços em branco
        $this->pixelId = trim($this->pixelId);
        $this->accessToken = trim($this->accessToken);
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

        // Valida clickId
        if (empty($clickId)) {
            Log::warning('Kwai click_id vazio', [
                'event_name' => $eventName,
            ]);
            return [
                'success' => false,
                'error' => 'click_id é obrigatório',
            ];
        }

        try {
            // Endpoint conforme código do cliente
            $url = 'https://www.adsnebula.com/log/common/api';

            // Monta payload conforme código do cliente
            $payload = [
                'access_token' => $this->accessToken,
                'clickid' => $clickId,
                'event_name' => $eventName,
                'pixelId' => $this->pixelId,
                'is_attributed' => 1,
                'mmpcode' => $this->mmpcode,
                'pixelSdkVersion' => $this->pixelSdkVersion,
                'testFlag' => false,
                'trackFlag' => $this->isTest, // true = eventos aparecem em "Test Events"
            ];

            // Adiciona currency e value se fornecidos
            if ($value !== null && $currency !== null) {
                $payload['currency'] = (string) $currency;
                $payload['value'] = (string) $value;
            }

            // Adiciona properties se fornecido
            if (!empty($properties)) {
                $payload['properties'] = json_encode($properties);
            }

            Log::info('Kwai Event API Request', [
                'event_name' => $eventName,
                'click_id' => $clickId,
                'pixel_id' => $this->pixelId,
                'value' => $value,
            ]);

            // Envia para API do AdsNebula
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
                Log::error('Kwai Event API Error', [
                    'event_name' => $eventName,
                    'click_id' => $clickId,
                    'http_code' => $response->status(),
                    'response' => $responseData,
                    'response_body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'http_code' => $response->status(),
                    'error' => 'Erro HTTP: ' . $response->status(),
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
