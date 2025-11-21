<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Cria uma nova transação de pagamento
     * 
     * @param array $data Dados da transação (amount, user, etc)
     * @return array Resposta do gateway com transaction_id, payment_url, etc
     * @throws \Exception Em caso de erro na criação da transação
     */
    public function createTransaction(array $data): array;

    /**
     * Verifica o status de uma transação
     * 
     * @param string $transactionId ID da transação no gateway
     * @return array Status da transação
     */
    public function getTransactionStatus(string $transactionId): array;

    /**
     * Processa um webhook do gateway
     * 
     * @param array $payload Dados recebidos do webhook
     * @return array Resposta processada
     */
    public function processWebhook(array $payload): array;

    /**
     * Retorna o nome do gateway
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Retorna os métodos de pagamento suportados
     * 
     * @return array
     */
    public function getSupportedPaymentMethods(): array;
}

