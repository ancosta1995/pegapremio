<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GameMultiplier;
use App\Models\GameHistory;
use App\Models\SystemSetting;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Rotas da SPA - todas as rotas devem retornar a mesma view
Route::get('/', function () {
    return view('claw-game');
});

Route::get('/perfil', function () {
    return view('claw-game');
});

Route::get('/carteira', function () {
    return view('claw-game');
});

Route::get('/afiliados', function () {
    return view('claw-game');
});

Route::get('/presell', function () {
    return view('presell');
});

// Rota para obter novo CSRF token
Route::get('/api/csrf-token', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
});

// Rota pública para obter valor mínimo de depósito (sem autenticação)
Route::get('/api/min-deposit', function () {
    $minDeposit = SystemSetting::get('min_deposit_amount', 10.00);
    return response()->json([
        'success' => true,
        'min_deposit_amount' => (float) $minDeposit,
    ]);
});

// Rota para capturar kwai_click_id
Route::post('/kwai/click', function (Request $request) {
    try {
        $request->validate([
            'kwai_click_id' => 'required|string|max:255',
        ]);

        // Salva na sessão
        session(['kwai_click_id' => $request->kwai_click_id]);

        Log::info('Kwai click_id capturado', [
            'kwai_click_id' => $request->kwai_click_id,
        ]);

        return response()->json([
            'status' => 'ok',
        ]);
    } catch (\Exception $e) {
        Log::error('Erro ao salvar kwai_click_id', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Erro ao salvar click ID',
        ], 500);
    }
});

// Rotas de autenticação via API
Route::post('/login', function (Request $request) {
    try {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email ou senha incorretos.',
            ], 401);
        }

        Auth::login($user);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'balance' => (float) ($user->balance ?? 0),
                'balance_bonus' => (float) ($user->balance_bonus ?? 0),
                'referral_code' => $user->referral_code,
                'balance_ref' => (float) ($user->balance_ref ?? 0),
                'cpa' => (float) ($user->cpa ?? 0),
            ],
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erro no login', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erro ao fazer login. Tente novamente.',
        ], 500);
    }
});

Route::post('/register', function (Request $request) {
    try {
        $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'referral_code' => 'nullable|string|exists:users,referral_code',
        // Tracking fields
        'click_id' => 'nullable|string|max:255',
        'pixel_id' => 'nullable|string|max:255',
        'kwai_click_id' => 'nullable|string|max:255',
        'campaign_id' => 'nullable|string|max:255',
        'adset_id' => 'nullable|string|max:255',
        'creative_id' => 'nullable|string|max:255',
        'utm_source' => 'nullable|string|max:255',
        'utm_campaign' => 'nullable|string|max:255',
        'utm_medium' => 'nullable|string|max:255',
        'utm_content' => 'nullable|string|max:255',
        'utm_term' => 'nullable|string|max:255',
        'utm_id' => 'nullable|string|max:255',
        'fbclid' => 'nullable|string|max:255',
    ]);

    // Garante que o telefone tenha o código do país
    $phone = $request->phone;
    if ($phone && strpos($phone, '+') !== 0) {
        // Se não começar com +, adiciona +55
        $phoneDigits = preg_replace('/\D/', '', $phone);
        if ($phoneDigits && strpos($phoneDigits, '55') !== 0) {
            $phone = '+55' . $phoneDigits;
        } elseif ($phoneDigits) {
            $phone = '+' . $phoneDigits;
        }
    }

    // Busca o CPA padrão do sistema
    $defaultCpa = \App\Models\SystemSetting::get('default_cpa', 10.00);

    // Verifica se há código de referido válido
    $referredBy = null;
    if ($request->referral_code) {
        $referrer = User::where('referral_code', $request->referral_code)->first();
        if ($referrer) {
            $referredBy = $request->referral_code;
        }
    }

    // Captura kwai_click_id da sessão ou do request
    $kwaiClickId = $request->kwai_click_id ?? session('kwai_click_id');

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'phone' => $phone,
        'balance' => 0,
        'balance_bonus' => 0,
        'balance_ref' => 0,
        'cpa' => $defaultCpa,
        'referred_by' => $referredBy,
        // Tracking fields
        'click_id' => $request->click_id,
        'pixel_id' => $request->pixel_id,
        'kwai_click_id' => $kwaiClickId,
        'campaign_id' => $request->campaign_id,
        'adset_id' => $request->adset_id,
        'creative_id' => $request->creative_id,
        'utm_source' => $request->utm_source,
        'utm_campaign' => $request->utm_campaign,
        'utm_medium' => $request->utm_medium,
        'utm_content' => $request->utm_content,
        'utm_term' => $request->utm_term,
        'utm_id' => $request->utm_id,
        'fbclid' => $request->fbclid,
        // referral_code será gerado automaticamente pelo boot do modelo
    ]);
    
    // Envia evento de registro para Kwai Event API
    if ($user->kwai_click_id) {
        try {
            $kwaiService = new \App\Services\KwaiService();
            $kwaiService->sendEvent(
                clickId: $user->kwai_click_id,
                eventName: 'EVENT_COMPLETE_REGISTRATION',
                properties: [
                    'content_type' => 'user',
                    'content_name' => 'Registro de Usuário',
                    'event_timestamp' => time() * 1000,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao enviar evento de registro para Kwai', [
                'user_id' => $user->id,
                'kwai_click_id' => $user->kwai_click_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

        Auth::login($user);

        return response()->json([
            'success' => true,
            'message' => 'Conta criada com sucesso!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'balance' => (float) ($user->balance ?? 0),
                'balance_bonus' => (float) ($user->balance_bonus ?? 0),
                'referral_code' => $user->referral_code,
                'balance_ref' => (float) ($user->balance_ref ?? 0),
                'cpa' => (float) ($user->cpa ?? 0),
            ],
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erro no registro', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Erro ao criar conta. Tente novamente.',
        ], 500);
    }
});

// Rota para verificar autenticação e obter dados do usuário
Route::get('/api/user', function (Request $request) {
    if (!Auth::check()) {
        return response()->json([
            'success' => false,
            'authenticated' => false,
        ]);
    }

    $user = Auth::user();
    
    // Calcula informações de rollover
    $rolloverRequirement = SystemSetting::get('rollover_requirement', 1.0);
    $totalDeposited = (float) ($user->total_deposited ?? 0);
    $totalWagered = (float) ($user->total_wagered ?? 0);
    $requiredWager = $totalDeposited * $rolloverRequirement;
    $rolloverProgress = $requiredWager > 0 ? min(1.0, $totalWagered / $requiredWager) : 1.0;
    $priorityFeeAmount = SystemSetting::get('withdrawal_priority_fee', 0.00);
    $minDepositAmount = SystemSetting::get('min_deposit_amount', 10.00);
    
    return response()->json([
        'success' => true,
        'authenticated' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'document' => $user->document,
            'referral_code' => $user->referral_code,
            'balance' => (float) ($user->balance ?? 0),
            'balance_bonus' => (float) ($user->balance_bonus ?? 0),
            'balance_ref' => (float) ($user->balance_ref ?? 0),
            'cpa' => (float) ($user->cpa ?? 0),
            'total_deposited' => $totalDeposited,
            'total_wagered' => $totalWagered,
            'rollover_progress' => $rolloverProgress,
            'rollover_required' => $requiredWager,
            'priority_fee_amount' => (float) $priorityFeeAmount,
            'min_deposit_amount' => (float) $minDepositAmount,
        ],
    ]);
});

// Rota para atualizar dados do usuário
Route::put('/api/user', function (Request $request) {
    if (!Auth::check()) {
        return response()->json([
            'success' => false,
            'message' => 'Não autenticado',
        ], 401);
    }

    $user = Auth::user();
    
    $request->validate([
        'document' => 'nullable|string|max:255',
    ]);
    
    if ($request->has('document')) {
        $user->document = $request->document;
        $user->save();
    }
    
    return response()->json([
        'success' => true,
        'message' => 'Dados atualizados com sucesso',
    ]);
});

// Rota para logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
        'success' => true,
        'message' => 'Logout realizado com sucesso!',
    ]);
});

// Rotas de pagamento (requerem autenticação)
Route::middleware('auth')->group(function () {
    Route::post('/api/payments/create', [PaymentController::class, 'createTransaction']);
    Route::get('/api/payments/transactions', [PaymentController::class, 'getTransactions']);
    Route::get('/api/payments/transaction/{id}', [PaymentController::class, 'getTransactionStatus']);
    
    // Rotas de saque
    Route::post('/api/withdrawals/create', [\App\Http\Controllers\WithdrawalController::class, 'create']);
    Route::get('/api/withdrawals', [\App\Http\Controllers\WithdrawalController::class, 'getWithdrawals']);
    Route::post('/api/withdrawals/fee/payment', [\App\Http\Controllers\WithdrawalController::class, 'createFeePayment']);
    Route::post('/api/withdrawals/priority-fee/payment', [\App\Http\Controllers\WithdrawalController::class, 'createPriorityFeePayment']);
    Route::get('/api/withdrawals/{id}/fee/status', [\App\Http\Controllers\WithdrawalController::class, 'getFeeStatus']);
    Route::get('/api/withdrawals/{id}/info', [\App\Http\Controllers\WithdrawalController::class, 'getWithdrawalInfo']);
});

// Webhook do Seedpay (não requer autenticação, usa secret)
Route::post('/api/payments/webhook/seedpay/{secret?}', [PaymentController::class, 'webhookSeedpay']);

// Rota para trackear Content View do Kwai (não requer autenticação)
Route::post('/api/kwai/track-content-view', function (Request $request) {
    try {
        $request->validate([
            'click_id' => 'required|string|max:255',
            'page' => 'nullable|string|max:255',
        ]);

        $clickId = $request->click_id;
        $page = $request->page ?? 'home';

        // Mapeia nomes de páginas para nomes amigáveis
        $pageNames = [
            'game' => 'Jogo',
            'wallet' => 'Carteira',
            'affiliate' => 'Afiliados',
            'profile' => 'Perfil',
            'home' => 'Página Principal',
        ];

        $pageName = $pageNames[$page] ?? ucfirst($page);

        // Envia evento EVENT_CONTENT_VIEW para Kwai
        $kwaiService = new \App\Services\KwaiService();
        $result = $kwaiService->sendEvent(
            clickId: $clickId,
            eventName: 'EVENT_CONTENT_VIEW',
            properties: [
                'content_type' => 'page',
                'content_name' => $pageName,
                'content_category' => 'site',
                'content_id' => $page,
                'event_timestamp' => time() * 1000,
            ]
        );

        if ($result['success']) {
            Log::info('Kwai Content View tracked', [
                'click_id' => $clickId,
            ]);
        } else {
            Log::warning('Kwai Content View failed', [
                'click_id' => $clickId,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }

        return response()->json([
            'status' => $result['success'] ? 'ok' : 'error',
            'message' => $result['success'] ? 'Content View tracked' : ($result['error'] ?? 'Error'),
        ]);
    } catch (\Exception $e) {
        Log::error('Erro ao trackear Content View do Kwai', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Erro ao processar evento',
        ], 500);
    }
});


// Rota para obter configuração do jogo (fallback se não existir)
Route::post('/', function (Request $request) {
    $action = $request->input('action');
    
    if ($action === 'get_claw_config') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
            ]);
        }
        
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'balance' => $user->balance ?? 0,
            'bet_values' => [0.50, 1.00, 2.00, 5.00, 10.00],
        ]);
    }
    
    if ($action === 'create_deposit') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }
        
        $request->validate([
        'amount' => 'required|numeric|min:' . SystemSetting::get('min_deposit_amount', 10.00),
            'cpf' => 'required|string',
        ]);
        
        $user = Auth::user();
        $cpf = preg_replace('/\D/', '', $request->cpf);
        
        // Salva o CPF no documento do usuário
        $user->document = $cpf;
        $user->save();
        
        // Esta lógica foi movida para PaymentService::approveTransaction()
        // para processar o CPA quando o depósito for aprovado via webhook
        
        // TODO: Implementar lógica de geração de QR Code PIX
        // Por enquanto, retorna um mock
        return response()->json([
            'success' => true,
            'qr_base64' => '', // Base64 do QR Code
            'qr_code' => '00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-426655440000520400005303986540510.005802BR5925PEGA PREMIO LTDA6009SAO PAULO62070503***6304ABCD',
            'message' => 'Depósito criado com sucesso',
        ]);
    }
    
    if ($action === 'play_claw_game_demo') {
        // Modo demo - não precisa autenticação e não debita saldo
        $request->validate([
            'bet_amount' => 'required|numeric|min:0.01',
            'collision_type' => 'required|string|in:bomb,prize,none',
        ]);
        
        $betAmount = (float) $request->bet_amount;
        $collisionType = $request->collision_type;
        
        $isWin = false;
        $winAmount = 0;
        $multiplier = 0;
        
        // Se colidiu com bomba, sempre perde
        if ($collisionType === 'bomb') {
            $isWin = false;
            $winAmount = 0;
        }
        // Se colidiu com prêmio, sorteia um multiplicador (modo demo)
        elseif ($collisionType === 'prize') {
            try {
                $multiplier = GameMultiplier::getRandomMultiplier(true); // true = modo demo
                $winAmount = $betAmount * $multiplier;
                $isWin = true;
            } catch (\Exception $e) {
                Log::error('Erro ao obter multiplicador (demo): ' . $e->getMessage());
                $isWin = false;
                $winAmount = 0;
                $multiplier = 0;
            }
        }
        // Se não colidiu com nada, perde
        else {
            $isWin = false;
            $winAmount = 0;
        }
        
        return response()->json([
            'success' => true,
            'is_win' => $isWin,
            'win_amount' => $winAmount,
            'multiplier' => $multiplier,
            'bet_amount' => $betAmount,
            'is_demo' => true,
        ]);
    }
    
    if ($action === 'play_claw_game') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }
        
        $request->validate([
            'bet_amount' => 'required|numeric|min:0.01',
            'collision_type' => 'required|string|in:bomb,prize,none',
        ]);
        
        $user = Auth::user();
        $betAmount = (float) $request->bet_amount;
        $collisionType = $request->collision_type;
        
        // Verifica se o usuário tem saldo suficiente
        if ($user->balance < $betAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente',
            ], 400);
        }
        
        // Salva o saldo antes da jogada
        $balanceBefore = (float) $user->balance;
        
        // Deduz a aposta do saldo
        $user->balance -= $betAmount;
        
        // Incrementa total apostado para cálculo de rollover
        $user->total_wagered += $betAmount;
        
        $isWin = false;
        $winAmount = 0;
        $multiplier = 0;
        
        // Se colidiu com bomba, sempre perde
        if ($collisionType === 'bomb') {
            $isWin = false;
            $winAmount = 0;
        }
        // Se colidiu com prêmio, sorteia um multiplicador baseado no tipo de usuário
        elseif ($collisionType === 'prize') {
            try {
                $multiplier = GameMultiplier::getRandomMultiplier($user->is_demo ?? false);
                $winAmount = $betAmount * $multiplier;
                $isWin = true;
                
                // Adiciona o ganho ao saldo
                $user->balance += $winAmount;
            } catch (\Exception $e) {
                Log::error('Erro ao obter multiplicador: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                    'is_demo' => $user->is_demo ?? null,
                ]);
                // Em caso de erro, não ganha nada
                $isWin = false;
                $winAmount = 0;
                $multiplier = 0;
            }
        }
        // Se não colidiu com nada, perde
        else {
            $isWin = false;
            $winAmount = 0;
        }
        
        // Salva o saldo atualizado
        $user->save();
        
        // Salva o histórico da jogada
        $balanceAfter = (float) $user->balance;
        GameHistory::create([
            'user_id' => $user->id,
            'bet_amount' => $betAmount,
            'collision_type' => $collisionType,
            'is_win' => $isWin,
            'win_amount' => $winAmount,
            'multiplier' => $multiplier,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'is_demo' => $user->is_demo ?? false,
        ]);
        
        return response()->json([
            'success' => true,
            'is_win' => $isWin,
            'win_amount' => $winAmount,
            'multiplier' => $multiplier,
            'new_balance' => $balanceAfter,
            'bet_amount' => $betAmount,
        ]);
    }
    
    if ($action === 'get_affiliate_data') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }
        
        $user = Auth::user();
        
        // Conta quantos usuários foram referidos por este usuário
        $referralsCount = User::where('referred_by', $user->referral_code)->count();
        
        return response()->json([
            'success' => true,
            'stats' => [
                'referrals' => $referralsCount,
                'total_earned' => (float) ($user->balance_ref ?? 0),
            ],
        ]);
    }
    
    if ($action === 'get_commission_history') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }
        
        $user = Auth::user();
        
        // Busca usuários referidos que já tiveram CPA pago
        $referrals = User::where('referred_by', $user->referral_code)
            ->where('cpa_paid', true)
            ->orderBy('updated_at', 'desc')
            ->get();
        
        $history = $referrals->map(function ($referral) {
            // Busca quando o CPA foi pago (primeiro depósito aprovado)
            $firstPayment = \App\Models\PaymentTransaction::where('user_id', $referral->id)
                ->where('status', 'approved')
                ->orderBy('updated_at', 'asc')
                ->first();
            
            return [
                'usuario' => $referral->name,
                'comissao' => (float) $referral->cpa,
                'data' => $firstPayment ? $firstPayment->updated_at->toISOString() : $referral->updated_at->toISOString(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }
    
    if ($action === 'get_presell_multipliers') {
        // Retorna os 2 maiores multiplicadores para modo demo
        $multipliers = GameMultiplier::where('active', true)
            ->where('is_demo', true)
            ->orderBy('multiplier', 'desc')
            ->limit(2)
            ->get()
            ->map(function ($mult) {
                return (float) $mult->multiplier;
            })
            ->values();
        
        // Se não tiver 2, retorna os disponíveis ou valores padrão
        if ($multipliers->count() < 2) {
            $multipliers = collect([50.00, 100.00]); // Fallback
        }
        
        return response()->json([
            'success' => true,
            'multipliers' => $multipliers->toArray(),
        ]);
    }
    
    if ($action === 'get_presell_config') {
        // Retorna configuração da presell (valor da aposta e quantidade de rodadas)
        $betAmount = SystemSetting::get('presell_bet_amount', 0.50);
        $freeRounds = (int) SystemSetting::get('presell_free_rounds', 3);
        
        return response()->json([
            'success' => true,
            'bet_amount' => (float) $betAmount,
            'free_rounds' => $freeRounds,
        ]);
    }
    
    if ($action === 'get_play_history') {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }
        
        $user = Auth::user();
        $limit = (int) ($request->input('limit', 50)); // Limite padrão de 50 registros
        $limit = min($limit, 100); // Máximo de 100 registros
        
        $history = GameHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'bet_amount' => (float) $item->bet_amount,
                    'collision_type' => $item->collision_type,
                    'is_win' => $item->is_win,
                    'win_amount' => (float) $item->win_amount,
                    'multiplier' => (float) $item->multiplier,
                    'balance_before' => (float) $item->balance_before,
                    'balance_after' => (float) $item->balance_after,
                    'is_demo' => $item->is_demo,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });
        
        return response()->json([
            'success' => true,
            'history' => $history,
            'total' => $history->count(),
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Ação não reconhecida',
    ], 404);
});
