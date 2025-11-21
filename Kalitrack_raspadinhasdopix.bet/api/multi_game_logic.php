<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once 'db.php';

class MultiGameLogic {
    private $pdo;
    private $gameId;
    private $config = [];
    private $symbols = [];
    private $prizeFrequency = [];
    
    public function __construct($pdo, $gameId = 1) {
        $this->pdo = $pdo;
        $this->gameId = $gameId;
        $this->loadGameConfig();
        $this->loadSymbols();
        $this->loadPrizeFrequency();
    }
    
    public function getAvailableGames() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, game_name, game_type, description, is_active
                FROM games 
                WHERE is_active = 1 
                ORDER BY id
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [
                ['id' => 1, 'game_name' => 'Raspadinha Clássica', 'game_type' => 'raspadinha', 'description' => 'Jogo tradicional', 'is_active' => 1],
                ['id' => 2, 'game_name' => 'Raspadinha Premium', 'game_type' => 'raspadinha', 'description' => 'Versão premium', 'is_active' => 1],
                ['id' => 3, 'game_name' => 'Raspadinha Mega', 'game_type' => 'raspadinha', 'description' => 'Grandes prêmios', 'is_active' => 1],
                ['id' => 4, 'game_name' => 'Raspadinha Turbo', 'game_type' => 'raspadinha', 'description' => 'Jogo rápido', 'is_active' => 1],
                ['id' => 5, 'game_name' => 'Raspadinha Especial', 'game_type' => 'raspadinha', 'description' => 'Edição especial', 'is_active' => 1]
            ];
        }
    }
    
    private function loadGameConfig() {
        try {
            $stmt = $this->pdo->prepare("SELECT config_key, config_value FROM game_config WHERE game_id = ?");
            $stmt->execute([$this->gameId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
        } catch (PDOException $e) {
            $defaults = $this->getDefaultConfig($this->gameId);
            $this->config = $defaults;
        }
        
        $defaults = $this->getDefaultConfig($this->gameId);
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $defaultValue;
            }
        }
    }
    
    private function getDefaultConfig($gameId) {
        $configs = [
            1 => ['bet_cost' => '1.00', 'win_chance' => '0.20', 'min_scratch_percent' => '51', 'game_active' => '1'],
            2 => ['bet_cost' => '5.00', 'win_chance' => '0.25', 'min_scratch_percent' => '51', 'game_active' => '1'],
            3 => ['bet_cost' => '10.00', 'win_chance' => '0.30', 'min_scratch_percent' => '51', 'game_active' => '1'],
            4 => ['bet_cost' => '2.00', 'win_chance' => '0.35', 'min_scratch_percent' => '30', 'game_active' => '1'],
            5 => ['bet_cost' => '20.00', 'win_chance' => '0.40', 'min_scratch_percent' => '51', 'game_active' => '1']
        ];
        
        return $configs[$gameId] ?? $configs[1];
    }
    
    private function loadSymbols() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, symbol_value, symbol_image 
                FROM game_symbols 
                WHERE game_id = ? AND is_active = 1 
                ORDER BY symbol_value
            ");
            $stmt->execute([$this->gameId]);
            $this->symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $this->symbols = $this->getDefaultSymbols($this->gameId);
        }
    }
    
    private function getDefaultSymbols($gameId) {
        $symbolSets = [
            1 => [ 
                ['id' => 1, 'symbol_value' => 0.50, 'symbol_image' => '/images/50c.webp'],
                ['id' => 2, 'symbol_value' => 1.00, 'symbol_image' => '/images/1-real.webp'],
                ['id' => 3, 'symbol_value' => 2.00, 'symbol_image' => '/images/2-reais.webp'],
                ['id' => 4, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp'],
                ['id' => 5, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp']
            ],
            2 => [ 
                ['id' => 10, 'symbol_value' => 2.50, 'symbol_image' => '/images/2-50-reais.webp'],
                ['id' => 11, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp'],
                ['id' => 12, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp'],
                ['id' => 13, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp'],
                ['id' => 14, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp']
            ],
            3 => [ 
                ['id' => 20, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp'],
                ['id' => 21, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp'],
                ['id' => 22, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp'],
                ['id' => 23, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp'],
                ['id' => 24, 'symbol_value' => 100.00, 'symbol_image' => '/images/100-reais.webp']
            ],
            4 => [ 
                ['id' => 30, 'symbol_value' => 1.00, 'symbol_image' => '/images/1-real.webp'],
                ['id' => 31, 'symbol_value' => 2.00, 'symbol_image' => '/images/2-reais.webp'],
                ['id' => 32, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp'],
                ['id' => 33, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp'],
                ['id' => 34, 'symbol_value' => 20.00, 'symbol_image' => '/images/20-reais.webp']
            ],
            5 => [ 
                ['id' => 40, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp'],
                ['id' => 41, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp'],
                ['id' => 42, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp'],
                ['id' => 43, 'symbol_value' => 100.00, 'symbol_image' => '/images/100-reais.webp'],
                ['id' => 44, 'symbol_value' => 200.00, 'symbol_image' => '/images/200-reais.webp']
            ]
        ];
        
        return $symbolSets[$gameId] ?? $symbolSets[1];
    }
    
    private function loadPrizeFrequency() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT gs.symbol_value, pf.frequency_percent
                FROM prize_frequency pf
                JOIN game_symbols gs ON pf.symbol_id = gs.id
                WHERE gs.game_id = ? AND pf.is_active = 1 AND gs.is_active = 1
            ");
            $stmt->execute([$this->gameId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->prizeFrequency[strval($row['symbol_value'])] = $row['frequency_percent'];
            }
        } catch (PDOException $e) {
            $this->prizeFrequency = $this->getDefaultFrequency($this->gameId);
        }
    }
    
    private function getDefaultFrequency($gameId) {
        $frequencies = [
            1 => ['0.5' => 65.0, '1' => 25.0, '2' => 5.0, '5' => 3.0, '10' => 1.5],
            2 => ['2.5' => 40.0, '5' => 30.0, '10' => 15.0, '25' => 8.0, '50' => 7.0],
            3 => ['5' => 35.0, '10' => 25.0, '25' => 20.0, '50' => 15.0, '100' => 5.0],
            4 => ['1' => 50.0, '2' => 25.0, '5' => 15.0, '10' => 7.0, '20' => 3.0],
            5 => ['10' => 30.0, '25' => 25.0, '50' => 20.0, '100' => 15.0, '200' => 10.0]
        ];
        
        return $frequencies[$gameId] ?? $frequencies[1];
    }
    
    public function getGameConfig() {
        $symbolImages = [];
        
        foreach ($this->symbols as $item) {
            $value = floatval($item['symbol_value']);
            $image = $item['symbol_image'];
            
            $symbolImages[strval($value)] = $image;
            $symbolImages[number_format($value, 2)] = $image;
            $symbolImages[number_format($value, 1)] = $image;
            
            if ($value == intval($value)) {
                $symbolImages[strval(intval($value))] = $image;
            }
        }
        
        return [
            'gameId' => $this->gameId,
            'cost' => floatval($this->config['bet_cost'] ?? 1.00),
            'winChance' => floatval($this->config['win_chance'] ?? 0.20),
            'minScratchPercent' => intval($this->config['min_scratch_percent'] ?? 51),
            'gameActive' => boolval($this->config['game_active'] ?? 1),
            'symbols' => array_map(function($s) { 
                return [
                    'value' => floatval($s['symbol_value']),
                    'image' => $s['symbol_image']
                ];
            }, $this->symbols),
            'symbolImages' => $symbolImages
        ];
    }
    
    private function selectWinningSymbol() {
        $random = mt_rand(0, 10000) / 100;
        $accumulator = 0;
        
        $availableSymbols = array_column($this->symbols, 'symbol_value');
        
        foreach ($this->prizeFrequency as $symbolValue => $frequency) {
            $symbolExists = false;
            foreach ($availableSymbols as $availableSymbol) {
                if (strval($availableSymbol) === strval($symbolValue)) {
                    $symbolExists = true;
                    break;
                }
            }
            
            if ($symbolExists) {
                $accumulator += $frequency;
                if ($random <= $accumulator) {
                    return floatval($symbolValue);
                }
            }
        }
        
        return floatval($availableSymbols[0]);
    }
    
    private function generateGrid($winningSymbol, $isWin) {
        if (empty($this->symbols)) {
            throw new Exception('Nenhum símbolo disponível para este jogo');
        }
        
        $grid = array_fill(0, 9, null);
        $winPositions = [];
        
        if ($isWin) {
            $positions = range(0, 8);
            shuffle($positions);
            $winPositions = array_slice($positions, 0, 3);
            
            foreach ($winPositions as $pos) {
                $grid[$pos] = floatval($winningSymbol);
            }
        }
        
        $symbolCount = [];
        
        foreach ($grid as $value) {
            if ($value !== null) {
                $symbolCount[strval($value)] = ($symbolCount[strval($value)] ?? 0) + 1;
            }
        }
        
        for ($i = 0; $i < 9; $i++) {
            if ($grid[$i] === null) {
                $attempts = 0;
                $symbolValue = null;
                
                do {
                    $randomSymbol = $this->symbols[array_rand($this->symbols)];
                    $symbolValue = floatval($randomSymbol['symbol_value']);
                    $symbolKey = strval($symbolValue);
                    
                    $currentCount = $symbolCount[$symbolKey] ?? 0;
                    
                    $maxAllowed = ($isWin && $symbolValue == $winningSymbol) ? 3 : 2;
                    
                    $attempts++;
                } while ($currentCount >= $maxAllowed && $attempts < 50);
                
                if ($attempts >= 50) {
                    foreach ($this->symbols as $symbol) {
                        $testValue = floatval($symbol['symbol_value']);
                        $testKey = strval($testValue);
                        $testCount = $symbolCount[$testKey] ?? 0;
                        $testMaxAllowed = ($isWin && $testValue == $winningSymbol) ? 3 : 2;
                        
                        if ($testCount < $testMaxAllowed) {
                            $symbolValue = $testValue;
                            break;
                        }
                    }
                }
                
                $grid[$i] = $symbolValue;
                $symbolCount[strval($symbolValue)] = ($symbolCount[strval($symbolValue)] ?? 0) + 1;
            }
        }
        
        $grid = array_values($grid);
        
        return [
            'grid' => $grid,
            'winPositions' => array_values($winPositions)
        ];
    }
    
    private function countSymbols($grid, $symbol) {
        $count = 0;
        foreach ($grid as $value) {
            if ($value !== null && floatval($value) === floatval($symbol)) {
                $count++;
            }
        }
        return $count;
    }
    
    private function validateGrid($grid, $isWin, $winningSymbol) {
        $symbolCount = [];
        
        foreach ($grid as $value) {
            if ($value !== null) {
                $key = strval($value);
                $symbolCount[$key] = ($symbolCount[$key] ?? 0) + 1;
            }
        }
        
        foreach ($symbolCount as $symbol => $count) {
            if ($isWin && floatval($symbol) == $winningSymbol) {
                if ($count != 3) {
                    return false;
                }
            } else {
                if ($count > 2) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    public function generateGame($userId) {
        $betAmount = floatval($this->config['bet_cost'] ?? 1.00);
        $winChance = floatval($this->config['win_chance'] ?? 0.20);
        
        if (empty($this->symbols)) {
            throw new Exception('Nenhum símbolo configurado para este jogo');
        }
        
        $stmt = $this->pdo->prepare("SELECT saldo FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || floatval($user['saldo']) < $betAmount) {
            throw new Exception('Saldo insuficiente');
        }
        
        $random = mt_rand(1, 10000) / 10000;
        $isWin = $random <= $winChance;
        
        $winningSymbol = 0;
        $prize = 0;
        
        if ($isWin) {
            $winningSymbol = $this->selectWinningSymbol();
            $prize = $winningSymbol;
        } else {
            $randomSymbol = $this->symbols[array_rand($this->symbols)];
            $winningSymbol = floatval($randomSymbol['symbol_value']);
        }
        
        $gridData = $this->generateGrid($winningSymbol, $isWin);
        
        if (!$this->validateGrid($gridData['grid'], $isWin, $winningSymbol)) {
            error_log("AVISO: Grid inválido gerado - Game ID: {$this->gameId}, Win: " . ($isWin ? 'true' : 'false'));
        }
        
        return [
            'isWin' => $isWin,
            'prize' => $prize,
            'winSymbol' => $winningSymbol,
            'grid' => $gridData['grid'],
            'winPositions' => $gridData['winPositions'],
            'betAmount' => $betAmount
        ];
    }
    
    public function processGameResult($userId, $gameResult) {
        try {
            $this->pdo->beginTransaction();
            
            $betAmount = $gameResult['betAmount'];
            
            $stmt = $this->pdo->prepare("UPDATE users SET saldo = saldo - ? WHERE id = ? AND saldo >= ?");
            $stmt->execute([$betAmount, $userId, $betAmount]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception('Saldo insuficiente para aposta');
            }
            
            $stmt = $this->pdo->prepare("SELECT saldo FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $newBalance = floatval($user['saldo']);
            
            if ($gameResult['isWin'] && $gameResult['prize'] > 0) {
                $stmt = $this->pdo->prepare("UPDATE users SET saldo = saldo + ? WHERE id = ?");
                $stmt->execute([$gameResult['prize'], $userId]);
                $newBalance += $gameResult['prize'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO jogadas (user_id, game_id, valor_aposta, valor_ganho, data_hora) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $this->gameId,
                $betAmount,
                $gameResult['isWin'] ? $gameResult['prize'] : 0
            ]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'newBalance' => $newBalance,
                'gameResult' => $gameResult
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $gameId = $_GET['game_id'] ?? 1;
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $gameId = $input['game_id'] ?? $gameId;
    }
    
    $multiGame = new MultiGameLogic($pdo, $gameId);
    
    if ($method === 'POST') {
        switch ($action) {
            case 'get_games':
                echo json_encode([
                    'success' => true,
                    'games' => $multiGame->getAvailableGames()
                ]);
                break;
                
            case 'get_config':
                echo json_encode([
                    'success' => true,
                    'config' => $multiGame->getGameConfig()
                ]);
                break;
                
            case 'start_game':
                $config = $multiGame->getGameConfig();
                if (!$config['gameActive']) {
                    throw new Exception('Jogo temporariamente indisponível');
                }
                
                $userId = $_SESSION['user_id'];
                
                $gameResult = $multiGame->generateGame($userId);
                
                $result = $multiGame->processGameResult($userId, $gameResult);
                
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Ação inválida');
        }
    } else {
        $action = $_GET['action'] ?? 'get_games';
        
        if ($action === 'get_games') {
            echo json_encode([
                'success' => true,
                'games' => $multiGame->getAvailableGames()
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'config' => $multiGame->getGameConfig()
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ]);
}