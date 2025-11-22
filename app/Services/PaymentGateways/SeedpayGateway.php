<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeedpayGateway implements PaymentGatewayInterface
{
    private string $publicKey;
    private string $secretKey;
    private string $baseUrl;
    private string $webhookSecret;

    public function __construct()
    {
        // Busca as configurações diretamente do banco
        $publicKeySetting = \App\Models\SystemSetting::where('key', 'seedpay_public_key')->first();
        $secretKeySetting = \App\Models\SystemSetting::where('key', 'seedpay_secret_key')->first();
        
        $this->publicKey = (string) ($publicKeySetting->value ?? '');
        $this->secretKey = (string) ($secretKeySetting->value ?? '');
        $this->baseUrl = (string) (\App\Models\SystemSetting::get('seedpay_base_url', 'https://api.paymaker.com.br') ?? 'https://api.paymaker.com.br');
        $this->webhookSecret = (string) (\App\Models\SystemSetting::get('seedpay_webhook_secret', '') ?? '');
        
        // Remove espaços em branco das chaves
        $this->publicKey = trim($this->publicKey);
        $this->secretKey = trim($this->secretKey);
        
        // Log para debug (sem expor as chaves completas)
        Log::info('SeedpayGateway initialized', [
            'public_key_set' => !empty($this->publicKey),
            'secret_key_set' => !empty($this->secretKey),
            'public_key_length' => strlen($this->publicKey),
            'secret_key_length' => strlen($this->secretKey),
            'public_key_preview' => !empty($this->publicKey) ? substr($this->publicKey, 0, 10) . '...' : 'EMPTY',
            'secret_key_preview' => !empty($this->secretKey) ? substr($this->secretKey, 0, 10) . '...' : 'EMPTY',
            'base_url' => $this->baseUrl,
        ]);
        
        // Valida se as credenciais estão configuradas
        if (empty($this->publicKey) || empty($this->secretKey)) {
            Log::warning('Seedpay credentials not configured', [
                'public_key_set' => !empty($this->publicKey),
                'secret_key_set' => !empty($this->secretKey),
                'public_key_exists_in_db' => $publicKeySetting !== null,
                'secret_key_exists_in_db' => $secretKeySetting !== null,
            ]);
        }
    }

    /**
     * Gera o token de autenticação Base64
     */
    private function getAuthToken(): string
    {
        // Valida se as credenciais estão preenchidas
        if (empty($this->publicKey) || empty($this->secretKey)) {
            Log::error('Seedpay credentials empty', [
                'public_key_empty' => empty($this->publicKey),
                'secret_key_empty' => empty($this->secretKey),
                'public_key_length' => strlen($this->publicKey ?? ''),
                'secret_key_length' => strlen($this->secretKey ?? ''),
            ]);
            throw new \Exception('Credenciais do Seedpay não configuradas. Configure Public Key e Secret Key no painel admin (Configurações).');
        }
        
        $credentials = "{$this->publicKey}:{$this->secretKey}";
        $token = base64_encode($credentials);
        
        // Log para debug (sem expor as chaves completas)
        Log::debug('Seedpay auth token generated', [
            'public_key_preview' => substr($this->publicKey, 0, 10) . '...',
            'secret_key_preview' => substr($this->secretKey, 0, 10) . '...',
            'token_preview' => substr($token, 0, 20) . '...',
        ]);
        
        return $token;
    }

    /**
     * Cria uma nova transação de pagamento
     */
    public function createTransaction(array $data): array
    {
        try {
            // Valida dados obrigatórios
            $required = ['amount', 'user', 'payment_method'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Campo obrigatório ausente: {$field}");
                }
            }

            $user = $data['user'];
            $amount = $data['amount']; // Valor em reais
            $amountInCents = (int) ($amount * 100); // Converte para centavos
            
            // Valida se o valor é válido
            if ($amountInCents <= 0) {
                throw new \Exception('Valor inválido para transação');
            }

            // Prepara dados para a API (Paymaker whitelabel)
            // Formato baseado no sistema antigo que funciona
            $payload = [
                'name' => $user->name,
                'email' => $user->email,
                'tel' => $user->phone ?? '',
                'document' => $user->document ?? '',
                'payType' => $this->mapPaymentMethod($data['payment_method']),
                'installments' => 0,
                'cardId' => 'string',
                'transAmt' => $amountInCents,
                'trans_utm_query' => 'string',
                'product' => [
                    'pro_name' => 'Depósito',
                    'pro_text' => 'Depósito para conta.',
                    'pro_category' => 'Depósito',
                    'pro_email' => $user->email,
                    'pro_phone' => $user->phone ?? '+55 11 999999999',
                    'pro_days_warranty' => 1,
                    'pro_delivery_type' => 'Digital',
                    'pro_text_email' => 'Seu depósito foi processado!',
                    'pro_site' => ''
                ],
                'address_cep' => '04567-000',
                'address_street' => 'Av. Brigadeiro Faria Lima',
                'address_number' => '1234',
                'address_district' => 'Itaim Bibi',
                'address_city' => 'São Paulo',
                'address_state' => 'SP',
                'address_country' => 'Brasil',
                'address_complement' => 'Conjunto'
            ];

            // Adiciona ad_cuid se fornecido (ID interno da transação)
            if (isset($data['internal_transaction_id'])) {
                $payload['ad_cuid'] = $data['internal_transaction_id'];
            }

            // Adiciona webhook URL se configurado
            $webhookUrl = url('/api/payments/webhook/seedpay');
            $payload['trans_webhook_url'] = $webhookUrl;

            // Faz requisição para criar transação
            $authToken = $this->getAuthToken();
            
            Log::info('Seedpay API Request', [
                'url' => "{$this->baseUrl}/api/transactions",
                'public_key_preview' => substr($this->publicKey, 0, 10) . '...',
                'token_preview' => substr($authToken, 0, 20) . '...',
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => $authToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/transactions", $payload);

            if (!$response->successful()) {
                $error = $response->json();
                $responseBody = $response->body();
                
                Log::error('Seedpay API Error', [
                    'status' => $response->status(),
                    'response' => $error,
                    'response_body' => $responseBody,
                    'public_key_preview' => substr($this->publicKey, 0, 10) . '...',
                    'secret_key_preview' => substr($this->secretKey, 0, 10) . '...',
                    'token_preview' => substr($authToken, 0, 20) . '...',
                ]);
                
                $errorMessage = $error['message'] ?? $error['error'] ?? 'Erro ao criar transação no Seedpay';
                
                // Mensagem mais específica para erro de autenticação
                if ($response->status() === 401) {
                    $errorMessage = 'Credenciais inválidas. Verifique se Public Key e Secret Key estão corretas no painel admin (Configurações).';
                }
                
                throw new \Exception($errorMessage);
            }

            $responseData = $response->json();

            // Verifica se a resposta é válida
            if (!is_array($responseData)) {
                Log::error('Seedpay createTransaction invalid response', [
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                    'response_data' => $responseData,
                ]);
                
                throw new \Exception('Resposta inválida da API do Seedpay. Verifique as credenciais e tente novamente.');
            }

            Log::info('Seedpay createTransaction response', [
                'response_keys' => array_keys($responseData),
                'response_preview' => json_encode($responseData),
            ]);

            // Seedpay/Hyperwallet pode retornar paymentId, trans_id, pay_id, etc.
            $transactionId = $responseData['paymentId'] ?? $responseData['trans_id'] ?? $responseData['pay_id'] ?? $responseData['transaction_id'] ?? $responseData['id'] ?? $responseData['payload']['id'] ?? null;
            
            // Seedpay/Hyperwallet retorna o código PIX em pixCode ou pay_codepix
            $qrCodeText = $responseData['pixCode'] ?? $responseData['pay_codepix'] ?? $responseData['qr_code_text'] ?? $responseData['payload']['qr_code_text'] ?? null;
            
            // Gera URL do QR Code se tiver o código PIX
            $qrCodeImageUrl = null;
            if ($qrCodeText) {
                $qrCodeImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrCodeText);
            }

            // Retorna dados padronizados
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'payment_url' => $responseData['payment_url'] ?? $responseData['payload']['payment_url'] ?? null,
                'qr_code' => $qrCodeImageUrl,
                'qr_code_text' => $qrCodeText,
                'status' => $responseData['status'] ?? $responseData['payload']['status'] ?? 'pending',
                'raw_response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('Seedpay createTransaction error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Verifica o status de uma transação
     */
    public function getTransactionStatus(string $transactionId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthToken(),
            ])->get("{$this->baseUrl}/api/transactions/{$transactionId}");

            if (!$response->successful()) {
                throw new \Exception('Erro ao consultar status da transação');
            }

            $data = $response->json();
            $payload = $data['payload'] ?? $data;

            return [
                'transaction_id' => $transactionId,
                'status' => $this->mapStatus($payload['status'] ?? 'pending'),
                'amount' => isset($payload['amount']) ? $payload['amount'] / 100 : null,
                'payment_method' => $payload['payment_method'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Seedpay getTransactionStatus error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);
            throw $e;
        }
    }

    /**
     * Processa um webhook do gateway
     */
    public function processWebhook(array $payload, ?string $secretFromUrl = null): array
    {
        try {
            Log::info('Seedpay webhook received', [
                'payload_keys' => array_keys($payload),
                'status' => $payload['status'] ?? 'not_set',
                'paymentId' => $payload['paymentId'] ?? 'not_set',
                'totalValue' => $payload['totalValue'] ?? 'not_set',
            ]);

            // Valida secret do webhook se configurado
            // O secret pode vir na URL ou no payload
            $providedSecret = $secretFromUrl ?? $payload['secret'] ?? null;
            
            if (!empty($this->webhookSecret) && $providedSecret) {
                if ($providedSecret !== $this->webhookSecret) {
                    throw new \Exception('Webhook secret inválido');
                }
            }
            // Se não tiver secret configurado, aceita qualquer requisição (como no sistema antigo)

            // Seedpay/Hyperwallet envia paymentId como identificador
            $transactionId = $payload['paymentId'] ?? $payload['trans_id'] ?? $payload['transaction_id'] ?? $payload['chargeId'] ?? $payload['id'] ?? null;
            
            if (!$transactionId) {
                throw new \Exception('ID da transação não encontrado no webhook');
            }

            // Mapeia status (pode vir como "APPROVED" em uppercase)
            $status = $this->mapStatus($payload['status'] ?? 'pending');
            
            // Calcula valor: totalValue está em centavos (ex: 1000 = R$ 10,00)
            $amount = null;
            if (isset($payload['totalValue'])) {
                $amount = (float) $payload['totalValue'] / 100;
            } elseif (isset($payload['amount'])) {
                $amount = (float) $payload['amount'];
                // Se o valor for muito grande (provavelmente está em centavos), converte
                if ($amount > 1000) {
                    $amount = $amount / 100;
                }
            }

            // Pega método de pagamento
            $paymentMethod = $payload['paymentMethod'] ?? $payload['payment_method'] ?? 'PIX';

            Log::info('Seedpay webhook processed', [
                'transaction_id' => $transactionId,
                'status' => $status,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]);

            return [
                'success' => true,
                'transaction_id' => (string) $transactionId,
                'status' => $status,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'provider_transaction_id' => (string) $transactionId,
            ];
        } catch (\Exception $e) {
            Log::error('Seedpay processWebhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Retorna o nome do gateway
     */
    public function getName(): string
    {
        return 'Seedpay';
    }

    /**
     * Retorna os métodos de pagamento suportados
     */
    public function getSupportedPaymentMethods(): array
    {
        return ['PIX'];
    }

    /**
     * Mapeia método de pagamento interno para formato da API
     */
    private function mapPaymentMethod(string $method): string
    {
        return match(strtoupper($method)) {
            'PIX' => 'PIX',
            'CREDIT_CARD' => 'CREDIT_CARD',
            'BOLETO' => 'BOLETO',
            default => 'PIX',
        };
    }

    /**
     * Mapeia status da API para status interno
     */
    private function mapStatus(string $status): string
    {
        return match(strtolower($status)) {
            'pending' => 'pending',
            'approved', 'paid', 'completed' => 'approved',
            'rejected', 'failed' => 'rejected',
            'canceled', 'cancelled' => 'canceled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }
}

