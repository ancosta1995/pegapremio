<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\SeedpayGateway;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registra o gateway padrão (Seedpay)
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return new SeedpayGateway();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

