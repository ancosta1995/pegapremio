<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Withdrawal;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\PaymentGateways\SeedpayGateway;

class MarkWithdrawalPriorityFeeAsPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdrawal:mark-priority-fee-paid {withdrawal_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca a taxa de prioridade de saque como paga (para testes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $withdrawalId = $this->argument('withdrawal_id');
        
        // Se não passou ID, busca o saque mais recente com taxa de prioridade pendente
        if (!$withdrawalId) {
            $withdrawal = Withdrawal::where('fee_paid', true)
                ->where('priority_fee_paid', false)
                ->whereNotNull('priority_fee_transaction_id')
                ->where('status', 'pending')
                ->latest()
                ->first();
            
            if (!$withdrawal) {
                $this->error('Nenhum saque com taxa de prioridade pendente encontrado.');
                $this->info('Certifique-se de que:');
                $this->info('1. A primeira taxa já foi paga (fee_paid = true)');
                $this->info('2. A taxa de prioridade foi gerada (priority_fee_transaction_id não é null)');
                $this->info('3. O saque está em status "pending"');
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
        
        // Verifica se a primeira taxa foi paga
        if (!$withdrawal->fee_paid) {
            $this->error("A primeira taxa ainda não foi paga. Execute primeiro: php artisan withdrawal:mark-fee-paid {$withdrawalId}");
            return 1;
        }
        
        // Verifica se a taxa de prioridade já foi paga
        if ($withdrawal->priority_fee_paid) {
            $this->warn("Taxa de prioridade já foi paga para o saque #{$withdrawalId}.");
            return 0;
        }
        
        // Busca a transação da taxa de prioridade
        $transaction = PaymentTransaction::find($withdrawal->priority_fee_transaction_id);
        
        if (!$transaction) {
            $this->error("Transação de taxa de prioridade não encontrada para o saque #{$withdrawalId}.");
            $this->info('Gere a taxa de prioridade primeiro através do sistema.');
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
        
        $this->info("✓ Taxa de prioridade processada");
        $this->info("✓ Prioridade ativada - previsão atualizada para 24 horas");
        
        $this->info("\n✅ Taxa de prioridade marcada como paga com sucesso!");
        
        return 0;
    }
}
