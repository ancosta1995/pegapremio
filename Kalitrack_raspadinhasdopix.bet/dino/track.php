<?php

// Proteção para não definir as mesmas funções duas vezes
if (!function_exists('logTrackingEvent')) {

    /**
     * Função helper para registrar eventos de tracking em arquivo de log
     * 
     * @param string $eventType Tipo do evento
     * @param string $pixelId ID do pixel
     * @param string $clickId ID do clique
     * @param float|null $value Valor monetário (opcional)
     * @param string|null $contentId ID do conteúdo (opcional)
     */
    function logTrackingEvent($eventType, $pixelId, $clickId, $value = null, $contentId = null)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'eventType' => $eventType,
            'pixelId' => $pixelId,
            'clickId' => $clickId,
            'value' => $value,
            'contentId' => $contentId,
            'request_id' => uniqid('track_')
        ];
        
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/tracking_events_' . date('Y-m-d') . '.log';
        $logLine = json_encode($logData) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('sendDinoTrackingEvent')) {

    /**
     * Função para enviar eventos de tracking para DinoTrack (track.raspaganhos.bet)
     * 
     * @param array $params Objeto contendo: config, eventType, clickId, value (opcional), contentId (opcional)
     * @return array Resposta da API ou array com erro
     */
    function sendDinoTrackingEvent($params)
    {
        // Validação dos parâmetros obrigatórios
        if (!isset($params['config']) || !is_array($params['config'])) {
            return [
                'success' => false,
                'error' => 'Configuração obrigatória não fornecida ou inválida'
            ];
        }

        if (!isset($params['eventType']) || !isset($params['clickId'])) {
            return [
                'success' => false,
                'error' => 'eventType e clickId são obrigatórios'
            ];
        }

        // Validação das chaves obrigatórias da configuração
        $requiredConfigKeys = ['pixelId', 'accessToken', 'apiKey'];
        foreach ($requiredConfigKeys as $key) {
            if (!isset($params['config'][$key]) || empty($params['config'][$key])) {
                return [
                    'success' => false,
                    'error' => "Chave obrigatória '{$key}' não encontrada na configuração"
                ];
            }
        }

        // Extração dos parâmetros
        $config = $params['config'];
        $eventType = $params['eventType'];
        $clickId = $params['clickId'];
        $value = $params['value'] ?? null;
        $contentId = $params['contentId'] ?? null;

        // Configurações da API DinoTrack
        $endpoint = 'https://track.raspaganhos.bet/v1/events';
        $pixelId = $config['pixelId'];
        $accessToken = $config['accessToken'];
        $apiKey = $config['apiKey'];

        // Validação do eventType
        $validEventTypes = ['ContentView', 'CompleteRegistration', 'AddToCart', 'Purchase'];

        try {
            if (!in_array($eventType, $validEventTypes)) {
                return [
                    'success' => false,
                    'error' => 'EventType inválido. Valores aceitos: ' . implode(', ', $validEventTypes)
                ];
            }

            // Validação do clickId
            if (empty($clickId)) {
                return [
                    'success' => false,
                    'error' => 'ClickId é obrigatório e não pode ser vazio'
                ];
            }

            // Montagem do body
            $body = [
                'pixelId' => $pixelId,
                'accessToken' => $accessToken,
                'clickId' => $clickId,
                'eventType' => $eventType,
                'isTest' => false
            ];

            // Adiciona properties apenas se tiver value
            if ($value !== null) {
                $body['properties'] = [
                    'content_id' => $contentId ?? 'deposito',
                    'content_type' => 'product',
                    'currency' => 'BRL',
                    'value' => floatval($value)
                ];
            }

            // Configuração do cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-Key: ' . $apiKey
                ],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            // Execução da requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Tratamento da resposta
            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'Erro cURL: ' . $curlError
                ];
            }

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'http_code' => $httpCode,
                    'response' => $decodedResponse
                ];
            } else {
                return [
                    'success' => false,
                    'http_code' => $httpCode,
                    'error' => 'Erro HTTP: ' . $httpCode,
                    'response' => $decodedResponse
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro: ' . $e->getMessage()
            ];
        }
    }
}

if (!function_exists('sendAdsNebulaTrackingEvent')) {

    /**
     * Função para enviar eventos de tracking para AdsNebula
     * 
     * @param array $params Objeto contendo: config, eventType, clickId, value (opcional), contentId (opcional)
     * @return array Resposta da API ou array com erro
     */
    function sendAdsNebulaTrackingEvent($params)
    {
        // Validação dos parâmetros obrigatórios
        if (!isset($params['config']) || !is_array($params['config'])) {
            return [
                'success' => false,
                'error' => 'Configuração obrigatória não fornecida ou inválida'
            ];
        }

        if (!isset($params['eventType']) || !isset($params['clickId'])) {
            return [
                'success' => false,
                'error' => 'eventType e clickId são obrigatórios'
            ];
        }

        // Validação das chaves obrigatórias da configuração
        $requiredConfigKeys = ['pixelId', 'accessToken'];
        foreach ($requiredConfigKeys as $key) {
            if (!isset($params['config'][$key]) || empty($params['config'][$key])) {
                return [
                    'success' => false,
                    'error' => "Chave obrigatória '{$key}' não encontrada na configuração"
                ];
            }
        }

        // Extração dos parâmetros
        $config = $params['config'];
        $eventType = $params['eventType'];
        $clickId = $params['clickId'];
        $value = $params['value'] ?? null;
        $contentId = $params['contentId'] ?? null;

        // Mapeamento dos tipos de eventos do DinoTrack para AdsNebula
        $eventMapping = [
            'ContentView' => 'EVENT_CONTENT_VIEW',
            'CompleteRegistration' => 'EVENT_COMPLETE_REGISTRATION',
            'AddToCart' => 'EVENT_ADD_TO_CART',
            'Purchase' => 'EVENT_PURCHASE'
        ];

        // Validação do mapeamento
        if (!isset($eventMapping[$eventType])) {
            return [
                'success' => false,
                'error' => "EventType '{$eventType}' não possui mapeamento para AdsNebula"
            ];
        }

        $adsNebulaEventType = $eventMapping[$eventType];

        // Configurações da API AdsNebula
        $url = 'https://www.adsnebula.com/log/common/api';
        $accessToken = $config['accessToken'];
        $pixelId = $config['pixelId'];

        // Validação do eventType
        $validEventTypes = ['ContentView', 'CompleteRegistration', 'AddToCart', 'Purchase'];

        try {
            if (!in_array($eventType, $validEventTypes)) {
                return [
                    'success' => false,
                    'error' => 'EventType inválido. Valores aceitos: ' . implode(', ', $validEventTypes)
                ];
            }

            // Validação do clickId
            if (empty($clickId)) {
                return [
                    'success' => false,
                    'error' => 'ClickId é obrigatório e não pode ser vazio'
                ];
            }

            // Montagem do payload
            $payload = [
                'access_token' => $accessToken,
                'clickid' => $clickId,
                'event_name' => $adsNebulaEventType,
                'is_attributed' => 1,
                'mmpcode' => 'PL',
                'pixelId' => $pixelId,
                'pixelSdkVersion' => '9.9.9',
                'testFlag' => false,
                'trackFlag' => false,
            ];

            // Adiciona properties apenas se tiver value
            if ($value !== null) {
                $payload['properties'] = json_encode([
                    'content_id' => $contentId ?? 'deposito',
                    'content_type' => 'product',
                    'content_name' => $contentId ?? 'deposito',
                    'value' => (float) $value,
                    'currency' => 'BRL',
                ]);
            }

            // Configuração do cURL
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);

            // Execução da requisição
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Tratamento da resposta
            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'Erro cURL: ' . $curlError
                ];
            }

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'http_code' => $httpCode,
                    'response' => $decodedResponse
                ];
            } else {
                return [
                    'success' => false,
                    'http_code' => $httpCode,
                    'error' => 'Erro HTTP: ' . $httpCode,
                    'response' => $decodedResponse
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro: ' . $e->getMessage()
            ];
        }
    }
}

if (!function_exists('sendTrackingEvent')) {

    /**
     * Função principal para enviar eventos de tracking com fallback automático
     * Delega para as implementações específicas sem conter lógica de implementação
     * 
     * @param string $eventType Tipo do evento (ContentView, CompleteRegistration, AddToCart, Purchase)
     * @param string $clickId ID do clique (obrigatório)
     * @param float|null $value Valor monetário (opcional)
     * @param string|null $contentId ID do conteúdo (opcional)
     * @return array Resposta da API ou array com erro
     */
    function sendTrackingEvent($eventType, $clickId, $value = null, $contentId = null)
    {
        // Configuração padrão
        $config = [
            'pixelId' => '290714800540320',
            'accessToken' => 'RVLUO5lPW4vzx1BNP1ous2qPmozuVmuXaKt_BklSIXo',
            'apiKey' => 'castro-5f9d9b94-84ae-493b-abfa-a1a999980876'
        ];

        // Validação do eventType
        $validEventTypes = ['ContentView', 'CompleteRegistration', 'AddToCart', 'Purchase'];
        if (!in_array($eventType, $validEventTypes)) {
            return [
                'success' => false,
                'error' => 'EventType inválido. Valores aceitos: ' . implode(', ', $validEventTypes)
            ];
        }

        // Validação do clickId
        if (empty($clickId)) {
            return [
                'success' => false,
                'error' => 'ClickId é obrigatório e não pode ser vazio'
            ];
        }

        // Preparação dos parâmetros para as funções específicas
        $params = [
            'config' => $config,
            'eventType' => $eventType,
            'clickId' => $clickId,
            'value' => $value,
            'contentId' => $contentId
        ];

        // Registrar log apenas para eventos específicos
        $eventsToLog = ['CompleteRegistration', 'AddToCart', 'Purchase'];
        if (in_array($eventType, $eventsToLog)) {
            logTrackingEvent($eventType, $config['pixelId'], $clickId, $value, $contentId);
        }

        // Primeira tentativa: enviar para DinoTrack
        $dinoResult = sendDinoTrackingEvent($params);

        if ($dinoResult['success']) {
            return $dinoResult;
        }

        // Se DinoTrack falhou, tentar AdsNebula como fallback
        $adsNebulaResult = sendAdsNebulaTrackingEvent($params);

        if ($adsNebulaResult['success']) {
            return $adsNebulaResult;
        }

        // Se ambos falharam, retornar erro
        return [
            'success' => false,
            'error' => 'Ambos os provedores falharam. DinoTrack: ' . ($dinoResult['error'] ?? 'Erro desconhecido') . ' | AdsNebula: ' . ($adsNebulaResult['error'] ?? 'Erro desconhecido'),
            'dino_error' => $dinoResult['error'] ?? 'Erro desconhecido',
            'adsnebula_error' => $adsNebulaResult['error'] ?? 'Erro desconhecido'
        ];
    }
}

// Exemplo de uso:
/*
// Evento simples sem properties
$result = sendTrackingEvent('ContentView', 'click123');

// Evento com value e content_id
$result = sendTrackingEvent('Purchase', 'click123', 10.50, 'produto123');

// Verificar resultado
if ($result['success']) {
    echo "Evento enviado com sucesso!";
    echo " HTTP Code: " . $result['http_code'];
} else {
    echo "Erro: " . $result['error'];
}

// Exemplo de uso direto das funções específicas (com objeto de parâmetros):
// Para DinoTrack apenas:
$config = [
    'pixelId' => '289624705944323',
    'accessToken' => 'nTyulC60l-CjfCagT2QDtOshbJUmUJ-4kldALG841WM',
    'apiKey' => 'castro-5f9d9b94-84ae-493b-abfa-a1a999980876'
];

$params = [
    'config' => $config,
    'eventType' => 'Purchase',
    'clickId' => 'click123',
    'value' => 10.50,
    'contentId' => 'produto123'
];
$dinoResult = sendDinoTrackingEvent($params);

// Para AdsNebula apenas:
$nebulaResult = sendAdsNebulaTrackingEvent($params);
*/

?>
