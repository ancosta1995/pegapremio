<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\TrackingConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KwaiTrackingService
{
    private ?string $pixelId;
    private ?string $accessToken;
    private ?string $webhookUrl;

    public function __construct(?string $pixelId = null, ?string $accessToken = null)
    {
        // Se não passar pixel_id e access_token, busca das configurações
        if ($pixelId === null || $accessToken === null) {
            $this->pixelId = (string) (SystemSetting::get('kwai_pixel_id', '') ?? '');
            $this->accessToken = (string) (SystemSetting::get('kwai_access_token', '') ?? '');
        } else {
            $this->pixelId = $pixelId;
            $this->accessToken = $accessToken;
        }
        
        $this->webhookUrl = SystemSetting::get('kwai_tracking_webhook_url', '');
    }

    /**
     * Busca configuração de tracking do banco (múltiplos pixels)
     */
    public static function getConfigFromDatabase(string $source, string $pixelId): ?self
    {
        $config = TrackingConfig::getActiveConfig($source, $pixelId);
        
        if (!$config) {
            return null;
        }
        
        return new self($config->pixel_id, $config->access_token);
    }


    /**
     * Envia evento de tracking para o Kwai (AdsNebula)
     */
    public function sendEvent(string $eventType, string $clickId, ?float $value = null, ?string $contentId = null): array
    {
        // Valida se está configurado
        if (empty($this->pixelId) || empty($this->accessToken)) {
            Log::warning('Kwai tracking não configurado', [
                'pixel_id_set' => !empty($this->pixelId),
                'access_token_set' => !empty($this->accessToken),
            ]);
            return [
                'success' => false,
                'error' => 'Kwai tracking não configurado',
            ];
        }

        // Valida eventType
        $validEventTypes = [
            'EVENT_CONTENT_VIEW',
            'EVENT_COMPLETE_REGISTRATION',
            'EVENT_ADD_TO_CART',
            'EVENT_PURCHASE',
        ];

        if (!in_array($eventType, $validEventTypes)) {
            return [
                'success' => false,
                'error' => 'EventType inválido. Valores aceitos: ' . implode(', ', $validEventTypes),
            ];
        }

        // Valida clickId
        if (empty($clickId)) {
            return [
                'success' => false,
                'error' => 'ClickId é obrigatório',
            ];
        }

        try {
            // Monta payload
            $payload = [
                'access_token' => $this->accessToken,
                'clickid' => $clickId,
                'event_name' => $eventType,
                'is_attributed' => 1,
                'mmpcode' => 'PL',
                'pixelId' => $this->pixelId,
                'pixelSdkVersion' => '9.9.9',
                'testFlag' => false,
                'trackFlag' => false,
            ];

            // Adiciona properties se tiver value
            if ($value !== null) {
                $payload['properties'] = json_encode([
                    'content_id' => $contentId ?? 'deposito',
                    'content_type' => 'product',
                    'content_name' => $contentId ?? 'deposito',
                    'value' => (float) $value,
                    'currency' => 'BRL',
                ]);
            }

            // Envia para AdsNebula
            $url = 'https://www.adsnebula.com/log/common/api';
            
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('AdsNebula (Kwai) tracking event sent', [
                    'event_type' => $eventType,
                    'click_id' => $clickId,
                    'value' => $value,
                    'http_code' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'http_code' => $response->status(),
                    'response' => $response->json(),
                    'provider' => 'AdsNebula',
                ];
            } else {
                Log::error('AdsNebula (Kwai) tracking error', [
                    'event_type' => $eventType,
                    'click_id' => $clickId,
                    'http_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'http_code' => $response->status(),
                    'error' => 'Erro HTTP: ' . $response->status(),
                    'response' => $response->json(),
                    'provider' => 'AdsNebula',
                ];
            }
        } catch (\Exception $e) {
            Log::error('AdsNebula (Kwai) tracking exception', [
                'event_type' => $eventType,
                'click_id' => $clickId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro: ' . $e->getMessage(),
                'provider' => 'AdsNebula',
            ];
        }
    }

    /**
     * Envia evento para webhook de tracking (kalitrack)
     */
    public function sendWebhookEvent(array $eventData): bool
    {
        if (empty($this->webhookUrl)) {
            return false;
        }

        try {
            $payload = [
                'evento' => $eventData['evento'] ?? '',
                'click_id' => $eventData['click_id'] ?? null,
                'pixel_id' => $eventData['pixel_id'] ?? null,
                'user_id' => $eventData['user_id'] ?? null,
                'valor' => $eventData['valor'] ?? null,
                'transaction_id' => $eventData['transaction_id'] ?? null,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip_address' => request()->ip(),
            ];

            // Adiciona dados UTM se disponíveis
            if (!empty($eventData['utm_source'])) $payload['utm_source'] = $eventData['utm_source'];
            if (!empty($eventData['utm_campaign'])) $payload['utm_campaign'] = $eventData['utm_campaign'];
            if (!empty($eventData['utm_medium'])) $payload['utm_medium'] = $eventData['utm_medium'];
            if (!empty($eventData['campaign_id'])) $payload['campaign_id'] = $eventData['campaign_id'];
            if (!empty($eventData['adset_id'])) $payload['adset_id'] = $eventData['adset_id'];
            if (!empty($eventData['creative_id'])) $payload['creative_id'] = $eventData['creative_id'];
            if (!empty($eventData['fbclid'])) $payload['fbclid'] = $eventData['fbclid'];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Sistema-Tracking/1.0',
                ])
                ->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Tracking webhook sent', [
                    'evento' => $eventData['evento'] ?? '',
                    'user_id' => $eventData['user_id'] ?? null,
                ]);
                return true;
            } else {
                Log::warning('Tracking webhook failed', [
                    'evento' => $eventData['evento'] ?? '',
                    'http_code' => $response->status(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Tracking webhook exception', [
                'evento' => $eventData['evento'] ?? '',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

