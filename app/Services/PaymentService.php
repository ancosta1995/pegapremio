<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Cria uma nova transação de pagamento
     */
    public function createTransaction(User $user, float $amount, string $paymentMethod = 'PIX', bool $isWithdrawalFee = false, string $transactionType = 'deposit'): PaymentTransaction
    {
        // Se for taxa de saque, não valida valor mínimo de depósito
        if (!$isWithdrawalFee) {
            // Valida valor mínimo
            $minDeposit = \App\Models\SystemSetting::get('min_deposit_amount', 20.00);
            if ($amount < $minDeposit) {
                throw new \Exception("Valor mínimo de depósito é R$ " . number_format($minDeposit, 2, ',', '.'));
            }
        }

        try {
            // Cria transação no banco de dados
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'gateway' => $this->gateway->getName(),
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_type' => $transactionType,
                'status' => 'pending',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('PaymentService createTransaction - Database error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            // Verifica se é erro de tabela não encontrada
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'Base table or view not found')) {
                throw new \Exception('Tabela de transações não encontrada. Execute as migrations: php artisan migrate');
            }
            
            throw new \Exception('Erro ao criar transação no banco de dados: ' . $e->getMessage());
        }

        try {
            // Cria transação no gateway
            $gatewayResponse = $this->gateway->createTransaction([
                'amount' => $amount,
                'user' => $user,
                'payment_method' => $paymentMethod,
                'internal_transaction_id' => $transaction->id,
            ]);

            // Atualiza transação com dados do gateway
            // Converte gateway_transaction_id para string para garantir consistência
            $gatewayTransactionId = $gatewayResponse['transaction_id'] ?? null;
            if ($gatewayTransactionId !== null) {
                $gatewayTransactionId = (string) $gatewayTransactionId;
            }
            
            $transaction->update([
                'gateway_transaction_id' => $gatewayTransactionId,
                'payment_url' => $gatewayResponse['payment_url'] ?? null,
                'qr_code' => $gatewayResponse['qr_code'] ?? null,
                'qr_code_text' => $gatewayResponse['qr_code_text'] ?? null,
                'gateway_response' => $gatewayResponse['raw_response'] ?? null,
            ]);
            
            Log::info('PaymentService transaction created and updated', [
                'transaction_id' => $transaction->id,
                'gateway_transaction_id' => $gatewayTransactionId,
                'amount' => $transaction->amount,
            ]);

            // Envia evento AddToCart quando o QR code é gerado (PIX gerado)
            // Em modo teste, usa testToken como fallback se não tiver kwai_click_id
            try {
                $kwaiService = new \App\Services\KwaiService();
                $result = $kwaiService->sendEvent(
                    clickId: $user->kwai_click_id ?? '',
                    eventName: 'EVENT_ADD_TO_CART',
                    properties: [
                        'content_type' => 'product',
                        'content_id' => 'deposito',
                        'content_name' => 'Depósito',
                        'quantity' => 1,
                        'price' => $amount,
                        'event_timestamp' => time() * 1000,
                    ],
                    value: $amount,
                    currency: 'BRL'
                );
                
                if (!$result['success']) {
                    Log::warning('Kwai AddToCart event failed', [
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                        'error' => $result['error'] ?? 'Unknown error',
                        'has_click_id' => !empty($user->kwai_click_id),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao enviar evento AddToCart para Kwai', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $transaction->fresh();
        } catch (\Exception $e) {
            // Atualiza transação com erro
            $transaction->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Processa webhook do gateway
     */
    public function processWebhook(array $payload, ?string $secretFromUrl = null): PaymentTransaction
    {
        try {
            Log::info('PaymentService processWebhook started', [
                'payload_keys' => array_keys($payload),
                'paymentId' => $payload['paymentId'] ?? 'not_set',
            ]);

            $gatewayResponse = $this->gateway->processWebhook($payload, $secretFromUrl);
            
            Log::info('PaymentService gateway response', [
                'transaction_id' => $gatewayResponse['transaction_id'],
                'status' => $gatewayResponse['status'],
                'amount' => $gatewayResponse['amount'],
            ]);
            
            // Busca transação pelo ID do gateway (pode ser string ou número)
            $transactionId = (string) $gatewayResponse['transaction_id'];
            $transaction = PaymentTransaction::where('gateway_transaction_id', $transactionId)
                ->orWhere('gateway_transaction_id', (int) $transactionId)
                ->first();

            if (!$transaction) {
                // Tenta buscar por qualquer campo que possa ter o ID
                Log::warning('PaymentService transaction not found by gateway_transaction_id', [
                    'gateway_transaction_id' => $transactionId,
                    'payload_paymentId' => $payload['paymentId'] ?? null,
                ]);
                
                // Lista últimas transações pendentes para debug
                $recentTransactions = PaymentTransaction::where('status', 'pending')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'gateway_transaction_id', 'amount', 'created_at']);
                
                Log::info('PaymentService recent pending transactions', [
                    'transactions' => $recentTransactions->toArray(),
                ]);
                
                throw new \Exception("Transação não encontrada: {$transactionId}");
            }

            Log::info('PaymentService transaction found', [
                'transaction_id' => $transaction->id,
                'gateway_transaction_id' => $transaction->gateway_transaction_id,
                'old_status' => $transaction->status,
                'new_status' => $gatewayResponse['status'],
            ]);

            $oldStatus = $transaction->status;
            $newStatus = $gatewayResponse['status'];

            // Atualiza status da transação
            $transaction->update([
                'status' => $newStatus,
                'gateway_response' => array_merge($transaction->gateway_response ?? [], $payload),
            ]);

            // Se foi aprovada, atualiza saldo do usuário
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                $this->approveTransaction($transaction);
            }

            return $transaction->fresh();
        } catch (\Exception $e) {
            Log::error('PaymentService processWebhook error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    /**
     * Aprova uma transação e atualiza saldo do usuário
     */
    private function approveTransaction(PaymentTransaction $transaction): void
    {
        $user = $transaction->user;
        
        // Verifica se é pagamento de taxa de saque (primeira taxa - validação)
        $withdrawal = \App\Models\Withdrawal::where('fee_transaction_id', $transaction->id)->first();
        
        if ($withdrawal) {
            // É pagamento da primeira taxa de saque - marca como pago e debita o saldo do saque
            $withdrawal->fee_paid = true;
            
            // Agora debita o saldo do saque (já que a taxa foi paga)
            $user = $withdrawal->user;
            $balanceBefore = (float) $user->balance;
            
            // Verifica se tem saldo suficiente (pode ter mudado desde a solicitação)
            if ($user->balance < $withdrawal->amount) {
                Log::error('Saldo insuficiente ao processar taxa de saque', [
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $user->id,
                    'required' => $withdrawal->amount,
                    'available' => $user->balance,
                ]);
                throw new \Exception('Saldo insuficiente para processar o saque.');
            }
            
            // Debita o valor do saque
            $user->balance -= $withdrawal->amount;
            $user->save();
            
            // Calcula posição na fila (simula uma fila baseada em quantos saques estão pendentes)
            $queuePosition = \App\Models\Withdrawal::where('status', 'pending')
                ->where('id', '!=', $withdrawal->id)
                ->count() + 1;
            
            // Atualiza o saque com o saldo após e posição na fila
            $withdrawal->balance_after = (float) $user->balance;
            $withdrawal->status = 'pending'; // Muda de pending_fee para pending (aguardando análise)
            $withdrawal->queue_position = $queuePosition;
            $withdrawal->save();
            
            Log::info('Taxa de saque paga e saque debitado', [
                'withdrawal_id' => $withdrawal->id,
                'transaction_id' => $transaction->id,
                'fee_amount' => $transaction->amount,
                'withdrawal_amount' => $withdrawal->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
            ]);
            
            return; // Não processa como depósito normal
        }
        
        // Verifica se é pagamento de taxa de prioridade
        $withdrawalPriority = \App\Models\Withdrawal::where('priority_fee_transaction_id', $transaction->id)->first();
        
        if ($withdrawalPriority) {
            // É pagamento de taxa de prioridade - apenas marca como pago
            $withdrawalPriority->priority_fee_paid = true;
            $withdrawalPriority->save();
            
            Log::info('Taxa de prioridade de saque paga', [
                'withdrawal_id' => $withdrawalPriority->id,
                'user_id' => $withdrawalPriority->user_id,
            ]);
            
            return; // Não processa como depósito normal
        }
        
        // Transação normal de depósito
        // Atualiza saldo
        $user->increment('balance', $transaction->amount);
        
        // Incrementa total depositado para cálculo de rollover
        $user->increment('total_deposited', $transaction->amount);

        // Se o usuário foi referido e ainda não teve o CPA pago, processa o CPA
        if ($user->referred_by && !$user->cpa_paid) {
            $referrer = \App\Models\User::where('referral_code', $user->referred_by)->first();
            if ($referrer) {
                // Adiciona o CPA ao balance_ref do referrer
                $referrer->increment('balance_ref', $user->cpa);
                
                // Marca que o CPA foi pago para este usuário
                $user->cpa_paid = true;
                $user->save();
                
                Log::info('CPA pago ao afiliado', [
                    'referrer_id' => $referrer->id,
                    'referred_user_id' => $user->id,
                    'cpa_amount' => $user->cpa,
                    'referrer_new_balance_ref' => $referrer->fresh()->balance_ref,
                ]);
            }
        }

        // Envia evento Purchase quando o pagamento é aprovado (PIX pago)
        // Em modo teste, usa testToken como fallback se não tiver kwai_click_id
        try {
            $kwaiService = new \App\Services\KwaiService();
            $result = $kwaiService->sendEvent(
                clickId: $user->kwai_click_id ?? '',
                eventName: 'EVENT_PURCHASE',
                properties: [
                    'content_type' => 'product',
                    'content_id' => (string) ($transaction->gateway_transaction_id ?? $transaction->id),
                    'content_name' => 'Depósito - Compra Finalizada',
                    'event_timestamp' => time() * 1000,
                ],
                value: $transaction->amount,
                currency: 'BRL'
            );
            
            if (!$result['success']) {
                Log::warning('Kwai Purchase event failed', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $user->id,
                    'error' => $result['error'] ?? 'Unknown error',
                    'has_click_id' => !empty($user->kwai_click_id),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar evento Purchase para Kwai', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Log da operação
        Log::info('Transação aprovada e saldo atualizado', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'new_balance' => $user->fresh()->balance,
            'total_deposited' => $user->fresh()->total_deposited,
        ]);
    }

    /**
     * Obtém status de uma transação
     */
    public function getTransactionStatus(PaymentTransaction $transaction): array
    {
        if (!$transaction->gateway_transaction_id) {
            return [
                'status' => $transaction->status,
                'amount' => $transaction->amount,
            ];
        }

        try {
            $gatewayStatus = $this->gateway->getTransactionStatus($transaction->gateway_transaction_id);
            
            // Salva status antigo antes de atualizar
            $oldStatus = $transaction->status;
            
            // Atualiza status se mudou
            if ($gatewayStatus['status'] !== $transaction->status) {
                $transaction->update(['status' => $gatewayStatus['status']]);
                
                // Se foi aprovada, atualiza saldo
                if ($gatewayStatus['status'] === 'approved' && $oldStatus !== 'approved') {
                    $this->approveTransaction($transaction);
                }
            }

            return $gatewayStatus;
        } catch (\Exception $e) {
            Log::error('PaymentService getTransactionStatus error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);
            
            return [
                'status' => $transaction->status,
                'amount' => $transaction->amount,
            ];
        }
    }
}

