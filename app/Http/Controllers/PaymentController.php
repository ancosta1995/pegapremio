<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\PaymentGateways\SeedpayGateway;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct()
    {
        // Instancia o gateway Seedpay
        $gateway = new SeedpayGateway();
        $this->paymentService = new PaymentService($gateway);
    }

    /**
     * Cria uma nova transação de pagamento
     */
    public function createTransaction(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:PIX,CREDIT_CARD,BOLETO',
            'document' => 'nullable|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $amount = (float) $request->amount;
            $paymentMethod = $request->payment_method ?? 'PIX';

            // Atualiza o documento do usuário se fornecido
            if ($request->has('document') && $request->document) {
                $user->document = $request->document;
                $user->save();
            }

            $transaction = $this->paymentService->createTransaction($user, $amount, $paymentMethod);

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'payment_url' => $transaction->payment_url,
                    'qr_code' => $transaction->qr_code,
                    'qr_code_text' => $transaction->qr_code_text,
                    'created_at' => $transaction->created_at,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('PaymentController createTransaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            // Retorna mensagem mais detalhada em modo debug
            $message = config('app.debug') 
                ? $e->getMessage() . ' (Linha: ' . $e->getLine() . ' em ' . basename($e->getFile()) . ')'
                : 'Erro ao processar pagamento. Verifique os logs para mais detalhes.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }

    /**
     * Obtém status de uma transação
     */
    public function getTransactionStatus($id)
    {
        try {
            $user = Auth::user();
            $transaction = PaymentTransaction::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Recarrega do banco para ter o status mais atualizado (pode ter sido atualizado pelo webhook)
            $transaction->refresh();
            
            // Se a transação já está aprovada no banco, retorna direto (não precisa consultar gateway)
            if ($transaction->status === 'approved') {
                return response()->json([
                    'success' => true,
                    'transaction' => [
                        'id' => $transaction->id,
                        'status' => $transaction->status,
                        'amount' => $transaction->amount,
                        'payment_method' => $transaction->payment_method,
                        'payment_url' => $transaction->payment_url,
                        'qr_code' => $transaction->qr_code,
                        'qr_code_text' => $transaction->qr_code_text,
                    ],
                ]);
            }

            // Se ainda está pendente, consulta o gateway para verificar se mudou
            $status = $this->paymentService->getTransactionStatus($transaction);

            // Recarrega novamente após possível atualização
            $transaction->refresh();

            return response()->json([
                'success' => true,
                'transaction' => [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'payment_method' => $transaction->payment_method,
                    'payment_url' => $transaction->payment_url,
                    'qr_code' => $transaction->qr_code,
                    'qr_code_text' => $transaction->qr_code_text,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentController getTransactionStatus error', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transação não encontrada',
            ], 404);
        }
    }

    /**
     * Lista transações do usuário
     */
    public function getTransactions(Request $request)
    {
        $user = Auth::user();
        
        $query = PaymentTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Webhook do Seedpay
     * 
     * O secret pode ser passado na URL: /api/payments/webhook/seedpay/{secret}
     * Ou configurado no painel admin e validado automaticamente
     */
    public function webhookSeedpay(Request $request, $secret = null)
    {
        try {
            Log::info('Seedpay webhook received', [
                'secret_from_url' => $secret ? 'provided' : 'not_provided',
                'payload_keys' => array_keys($request->all()),
            ]);

            $gateway = new SeedpayGateway();
            $paymentService = new PaymentService($gateway);

            $payload = $request->all();

            // Passa o secret da URL para validação
            $transaction = $paymentService->processWebhook($payload, $secret);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentController webhookSeedpay error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'secret' => $secret,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

