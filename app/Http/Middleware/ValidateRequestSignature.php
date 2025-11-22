<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequestSignature
{
    /**
     * Valida a assinatura das requisições
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apenas valida requisições POST/PUT/PATCH que tenham dados
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && $request->hasHeader('X-Request-Signature')) {
            $signature = $request->header('X-Request-Signature');
            $timestamp = $request->header('X-Request-Time');
            
            // Valida timestamp (não pode ser muito antigo - 5 minutos)
            if ($timestamp && abs(time() * 1000 - (int)$timestamp) > 300000) {
                return response()->json(['message' => 'Requisição expirada'], 401);
            }
            
            // Valida assinatura (implementação básica - pode ser melhorada)
            // Em produção, use uma chave secreta compartilhada
            $data = $request->all();
            $expectedSignature = $this->generateSignature($data, $timestamp);
            
            if ($signature !== $expectedSignature) {
                // Log tentativa de requisição inválida
                \Log::warning('Tentativa de requisição com assinatura inválida', [
                    'ip' => $request->ip(),
                    'url' => $request->url(),
                ]);
                
                return response()->json(['message' => 'Assinatura inválida'], 403);
            }
        }
        
        return $next($request);
    }
    
    /**
     * Gera assinatura esperada (deve corresponder ao frontend)
     */
    private function generateSignature(array $data, string $timestamp): string
    {
        // Mesma lógica do frontend
        // IMPORTANTE: Esta implementação deve corresponder exatamente ao frontend
        $hostname = request()->getHost();
        $secretKey = base64_encode($hostname . ':' . $timestamp);
        $secretKey = substr($secretKey, 0, 16);
        
        // Ordena os dados para garantir consistência
        ksort($data);
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $timestamp . $secretKey;
        
        $hash = 0;
        for ($i = 0; $i < strlen($payload); $i++) {
            $char = ord($payload[$i]);
            $hash = (($hash << 5) - $hash) + $char;
            $hash = $hash & $hash; // Convert to 32bit integer
        }
        
        return dechex(abs($hash));
    }
}

