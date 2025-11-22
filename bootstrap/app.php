<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclui webhook do CSRF (webhooks externos não podem enviar token CSRF)
        $middleware->validateCsrfTokens(except: [
            'api/payments/webhook/*',
        ]);
        
        // Adiciona headers de segurança globalmente
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Valida assinatura de requisições (opcional - pode ser muito restritivo)
        // Descomente se quiser ativar validação de assinatura em todas as rotas
        // $middleware->append(\App\Http\Middleware\ValidateRequestSignature::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
