<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Withdrawal;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\PaymentGateways\SeedpayGateway;

class MarkWithdrawalFeeAsPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdrawal:mark-fee-paid {withdrawal_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca a primeira taxa de saque como paga (para testes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $withdrawalId = $this->argument('withdrawal_id');
        
        // Se não passou ID, busca o saque mais recente com taxa pendente
        if (!$withdrawalId) {
            $withdrawal = Withdrawal::where('fee_paid', false)
                ->whereNotNull('fee_transaction_id')
                ->where('status', 'pending_fee')
                ->latest()
                ->first();
            
            if (!$withdrawal) {
                $this->error('Nenhum saque com taxa pendente encontrado.');
                return 1;
            }
            
            $withdrawalId = $withdrawal->id;
        } else {
            $withdrawal = Withdrawal::find($withdrawalId);
            
            if (!$withdrawal) {
                $this->error("Saque #{$withdrawalId} não encontrado.");
                return 1;
            }
        }
        
        $this->info("Processando saque #{$withdrawalId}...");
        
        // Busca a transação da taxa
        $transaction = PaymentTransaction::find($withdrawal->fee_transaction_id);
        
        if (!$transaction) {
            $this->error("Transação de taxa não encontrada para o saque #{$withdrawalId}.");
            return 1;
        }
        
        // Marca a transação como aprovada
        $transaction->status = 'approved';
        $transaction->save();
        
        $this->info("✓ Transação #{$transaction->id} marcada como aprovada");
        
        // Processa como se fosse um webhook
        $gateway = new SeedpayGateway();
        $paymentService = new PaymentService($gateway);
        
        // Usa reflexão para chamar o método privado approveTransaction
        $reflection = new \ReflectionClass($paymentService);
        $method = $reflection->getMethod('approveTransaction');
        $method->setAccessible(true);
        $method->invoke($paymentService, $transaction);
        
        $this->info("✓ Taxa processada e saldo debitado");
        $this->info("✓ Saque movido para análise (status: pending)");
        $this->info("✓ Posição na fila: #{$withdrawal->fresh()->queue_position}");
        
        $this->info("\n✅ Primeira taxa marcada como paga com sucesso!");
        $this->info("Agora você pode testar a taxa de prioridade.");
        
        return 0;
    }
}
