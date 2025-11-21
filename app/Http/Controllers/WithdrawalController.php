<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\SystemSetting;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\PaymentGateways\SeedpayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    /**
     * Cria uma nova solicitação de saque
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'pix_key_type' => 'required|string|in:CPF,EMAIL,PHONE,RANDOM',
            'pix_key' => 'required|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $amount = (float) $request->amount;

            // Valida valor mínimo
            $minWithdraw = SystemSetting::get('min_withdraw_amount', 50.00);
            if ($amount < $minWithdraw) {
                return response()->json([
                    'success' => false,
                    'message' => "Valor mínimo de saque é R$ " . number_format($minWithdraw, 2, ',', '.'),
                ], 400);
            }

            // Valida saldo suficiente
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo insuficiente para realizar o saque.',
                ], 400);
            }

            // Valida rollover
            $rolloverRequirement = SystemSetting::get('rollover_requirement', 1.0);
            $requiredWager = $user->total_deposited * $rolloverRequirement;
            $rolloverProgress = $user->getRolloverProgress();

            if (!$user->hasCompletedRollover()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você ainda não completou o rollover necessário. Você precisa apostar R$ ' . number_format($requiredWager, 2, ',', '.') . ' (você já apostou R$ ' . number_format($user->total_wagered, 2, ',', '.') . ').',
                    'rollover_progress' => $rolloverProgress,
                    'required_wager' => $requiredWager,
                    'current_wager' => $user->total_wagered,
                ], 400);
            }

            // Valida formato da chave PIX baseado no tipo
            $pixKey = trim($request->pix_key);
            if (!$this->validatePixKey($request->pix_key_type, $pixKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chave PIX inválida para o tipo selecionado.',
                ], 400);
            }

            // Salva saldo antes do saque
            $balanceBefore = (float) $user->balance;

            // Verifica se há taxa de saque
            $withdrawalFee = SystemSetting::get('withdrawal_fee', 0.00);
            $needsFee = $withdrawalFee > 0;

            // Se NÃO precisa de taxa, deduz o valor do saldo imediatamente
            // Se precisa de taxa, NÃO debita ainda (só debita quando a taxa for paga)
            if (!$needsFee) {
                // Deduz o valor do saldo
                $user->balance -= $amount;
                $user->save();
            }

            // Calcula saldo após (se não precisa de taxa, já foi debitado acima)
            $balanceAfter = $needsFee ? $balanceBefore : (float) $user->balance;

            // Cria registro de saque
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'pix_key_type' => $request->pix_key_type,
                'pix_key' => $pixKey,
                'status' => $needsFee ? 'pending_fee' : 'pending', // Se tiver taxa, fica pending_fee até pagar
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'total_deposited_at_time' => $user->total_deposited,
                'total_wagered_at_time' => $user->total_wagered,
                'rollover_requirement_at_time' => $rolloverRequirement,
                'rollover_progress_at_time' => $rolloverProgress,
                'fee_paid' => !$needsFee, // Se não tiver taxa, já está pago
            ]);

            Log::info('Withdrawal created', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'pix_key_type' => $request->pix_key_type,
                'needs_fee' => $needsFee,
                'fee_amount' => $withdrawalFee,
            ]);

            // Se não precisa de taxa, retorna sucesso direto
            if (!$needsFee) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitação de saque criada com sucesso! Aguarde a aprovação.',
                    'withdrawal' => [
                        'id' => $withdrawal->id,
                        'amount' => $withdrawal->amount,
                        'status' => $withdrawal->status,
                        'created_at' => $withdrawal->created_at,
                    ],
                ]);
            }

            // Se precisa de taxa, retorna informação para abrir modal
            return response()->json([
                'success' => true,
                'needs_fee' => true,
                'fee_amount' => $withdrawalFee,
                'withdrawal_id' => $withdrawal->id,
                'message' => 'Taxa de saque necessária',
            ]);
        } catch (\Exception $e) {
            Log::error('WithdrawalController createTransaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar solicitação de saque: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista saques do usuário
     */
    public function getWithdrawals(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status');
        $priorityFee = SystemSetting::get('withdrawal_priority_fee', 0.00);

        $query = Withdrawal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $withdrawals = $query->paginate(20);

        // Adiciona informações de prioridade para cada saque
        $withdrawalsData = collect($withdrawals->items())->map(function ($withdrawal) use ($priorityFee) {
            $canPayPriority = $withdrawal->fee_paid && !$withdrawal->priority_fee_paid && $priorityFee > 0;
            
            return [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'pix_key_type' => $withdrawal->pix_key_type,
                'pix_key' => $withdrawal->pix_key,
                'status' => $withdrawal->status,
                'fee_paid' => $withdrawal->fee_paid,
                'fee_transaction_id' => $withdrawal->fee_transaction_id,
                'priority_fee_paid' => $withdrawal->priority_fee_paid,
                'priority_fee_transaction_id' => $withdrawal->priority_fee_transaction_id,
                'queue_position' => $withdrawal->queue_position,
                'priority_fee_amount' => $priorityFee,
                'can_pay_priority' => $canPayPriority,
                'created_at' => $withdrawal->created_at,
                'updated_at' => $withdrawal->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'withdrawals' => $withdrawalsData,
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }

    /**
     * Valida formato da chave PIX
     */
    private function validatePixKey(string $type, string $key): bool
    {
        switch ($type) {
            case 'CPF':
                // Remove caracteres não numéricos
                $cpf = preg_replace('/\D/', '', $key);
                return strlen($cpf) === 11;

            case 'EMAIL':
                return filter_var($key, FILTER_VALIDATE_EMAIL) !== false;

            case 'PHONE':
                // Remove caracteres não numéricos
                $phone = preg_replace('/\D/', '', $key);
                // Telefone deve ter 10 ou 11 dígitos (com DDD)
                return strlen($phone) >= 10 && strlen($phone) <= 11;

            case 'RANDOM':
                // Chave aleatória (UUID) - formato: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
                return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key) === 1;

            default:
                return false;
        }
    }

    /**
     * Cria pagamento da taxa de saque (primeira taxa - validação)
     */
    public function createFeePayment(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|exists:withdrawals,id',
            'fee_amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $user = Auth::user();
            $withdrawal = Withdrawal::findOrFail($request->withdrawal_id);

            // Verifica se o saque pertence ao usuário
            if ($withdrawal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saque não encontrado.',
                ], 404);
            }

            // Verifica se a taxa já foi paga
            if ($withdrawal->fee_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Taxa de saque já foi paga.',
                ], 400);
            }

            // Verifica se já existe uma transação pendente para esta taxa
            $existingTransaction = null;
            if ($withdrawal->fee_transaction_id) {
                $existingTransaction = PaymentTransaction::find($withdrawal->fee_transaction_id);
                if ($existingTransaction && $existingTransaction->status === 'pending') {
                    // Usa a transação existente
                    $transaction = $existingTransaction;
                }
            }

            // Se não existe transação pendente, cria uma nova
            if (!$existingTransaction || $existingTransaction->status !== 'pending') {
                $gateway = new SeedpayGateway();
                $paymentService = new PaymentService($gateway);
                
                $transaction = $paymentService->createTransaction(
                    $user,
                    (float) $request->fee_amount,
                    'PIX',
                    true, // isWithdrawalFee = true
                    'withdrawal_fee' // Tipo: taxa de saque (validação)
                );

                // Atualiza o saque com o ID da transação da taxa
                $withdrawal->fee_transaction_id = $transaction->id;
                $withdrawal->save();
            }

            // Retorna QR code
            return response()->json([
                'success' => true,
                'qr_code_url' => $transaction->qr_code,
                'qr_code' => $transaction->qr_code,
                'pix_code' => $transaction->qr_code_text,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('WithdrawalController createFeePayment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar pagamento da taxa: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cria pagamento da taxa de prioridade de saque
     */
    public function createPriorityFeePayment(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|exists:withdrawals,id',
            'fee_amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $user = Auth::user();
            $withdrawal = Withdrawal::findOrFail($request->withdrawal_id);

            // Verifica se o saque pertence ao usuário
            if ($withdrawal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saque não encontrado.',
                ], 404);
            }

            // Verifica se a primeira taxa já foi paga
            if (!$withdrawal->fee_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você precisa pagar a taxa de validação primeiro.',
                ], 400);
            }

            // Verifica se a taxa de prioridade já foi paga
            if ($withdrawal->priority_fee_paid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Taxa de prioridade já foi paga.',
                ], 400);
            }

            // Verifica se já existe uma transação pendente para esta taxa de prioridade
            $existingTransaction = null;
            if ($withdrawal->priority_fee_transaction_id) {
                $existingTransaction = PaymentTransaction::find($withdrawal->priority_fee_transaction_id);
                if ($existingTransaction && $existingTransaction->status === 'pending') {
                    // Usa a transação existente
                    $transaction = $existingTransaction;
                }
            }

            // Se não existe transação pendente, cria uma nova
            if (!$existingTransaction || $existingTransaction->status !== 'pending') {
                $gateway = new SeedpayGateway();
                $paymentService = new PaymentService($gateway);
                
                $transaction = $paymentService->createTransaction(
                    $user,
                    (float) $request->fee_amount,
                    'PIX',
                    true, // isWithdrawalFee = true
                    'withdrawal_priority_fee' // Tipo: taxa de prioridade de saque
                );

                // Atualiza o saque com o ID da transação da taxa de prioridade
                $withdrawal->priority_fee_transaction_id = $transaction->id;
                $withdrawal->save();
            }

            // Retorna QR code
            return response()->json([
                'success' => true,
                'qr_code_url' => $transaction->qr_code,
                'qr_code' => $transaction->qr_code,
                'pix_code' => $transaction->qr_code_text,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            Log::error('WithdrawalController createPriorityFeePayment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar pagamento da taxa de prioridade: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém informações detalhadas do saque (incluindo posição na fila)
     */
    public function getWithdrawalInfo($id)
    {
        try {
            $user = Auth::user();
            $withdrawal = Withdrawal::findOrFail($id);

            // Verifica se o saque pertence ao usuário
            if ($withdrawal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saque não encontrado.',
                ], 404);
            }

            $priorityFee = SystemSetting::get('withdrawal_priority_fee', 0.00);
            $canPayPriority = $withdrawal->fee_paid && !$withdrawal->priority_fee_paid && $priorityFee > 0;

            return response()->json([
                'success' => true,
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'fee_paid' => $withdrawal->fee_paid,
                    'priority_fee_paid' => $withdrawal->priority_fee_paid,
                    'queue_position' => $withdrawal->queue_position,
                    'can_pay_priority' => $canPayPriority,
                    'priority_fee_amount' => $priorityFee,
                    'created_at' => $withdrawal->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WithdrawalController getWithdrawalInfo error', [
                'error' => $e->getMessage(),
                'withdrawal_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao obter informações do saque.',
            ], 500);
        }
    }

    /**
     * Verifica status do pagamento da taxa (primeira taxa)
     */
    public function getFeeStatus($id)
    {
        try {
            $user = Auth::user();
            $withdrawal = Withdrawal::findOrFail($id);

            // Verifica se o saque pertence ao usuário
            if ($withdrawal->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saque não encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'fee_paid' => $withdrawal->fee_paid,
                'priority_fee_paid' => $withdrawal->priority_fee_paid,
                'withdrawal_status' => $withdrawal->status,
                'queue_position' => $withdrawal->queue_position,
            ]);
        } catch (\Exception $e) {
            Log::error('WithdrawalController getFeeStatus error', [
                'error' => $e->getMessage(),
                'withdrawal_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status da taxa.',
            ], 500);
        }
    }
}

