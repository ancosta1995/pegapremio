<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once 'db.php';

class AdminGameConfig {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getFullConfig($gameId = 1) {
        $config = [];
        try {
            $stmt = $this->pdo->prepare("SELECT config_key, config_value FROM game_config WHERE game_id = ?");
            $stmt->execute([$gameId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $config[$row['config_key']] = $row['config_value'];
            }
        } catch (PDOException $e) {
        }
        
        $defaults = $this->getDefaultConfig($gameId);
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($config[$key])) {
                $config[$key] = $defaultValue;
            }
        }
        
        $symbols = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, symbol_value, symbol_image, is_active, game_id
                FROM game_symbols 
                WHERE game_id = ?
                ORDER BY symbol_value
            ");
            $stmt->execute([$gameId]);
            $symbols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $symbols = $this->getDefaultSymbols($gameId);
        }
        
        $frequencies = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT gs.symbol_value, pf.frequency_percent
                FROM prize_frequency pf
                JOIN game_symbols gs ON pf.symbol_id = gs.id
                WHERE pf.is_active = 1 AND gs.game_id = ?
            ");
            $stmt->execute([$gameId]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $frequencies[$row['symbol_value']] = $row['frequency_percent'];
            }
        } catch (PDOException $e) {
            $frequencies = $this->getDefaultFrequency($gameId);
        }
        
        return [
            'config' => $config,
            'symbols' => $symbols,
            'frequencies' => $frequencies
        ];
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
    
    private function getDefaultSymbols($gameId) {
        $symbolSets = [
            1 => [
                ['id' => 1, 'symbol_value' => 0.50, 'symbol_image' => '/images/50c.webp', 'is_active' => 1, 'game_id' => 1],
                ['id' => 2, 'symbol_value' => 1.00, 'symbol_image' => '/images/1-real.webp', 'is_active' => 1, 'game_id' => 1],
                ['id' => 3, 'symbol_value' => 2.00, 'symbol_image' => '/images/2-reais.webp', 'is_active' => 1, 'game_id' => 1],
                ['id' => 4, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp', 'is_active' => 1, 'game_id' => 1],
                ['id' => 5, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp', 'is_active' => 1, 'game_id' => 1]
            ],
            2 => [
                ['id' => 10, 'symbol_value' => 2.50, 'symbol_image' => '/images/2-50-reais.webp', 'is_active' => 1, 'game_id' => 2],
                ['id' => 11, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp', 'is_active' => 1, 'game_id' => 2],
                ['id' => 12, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp', 'is_active' => 1, 'game_id' => 2],
                ['id' => 13, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp', 'is_active' => 1, 'game_id' => 2],
                ['id' => 14, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp', 'is_active' => 1, 'game_id' => 2]
            ],
            3 => [
                ['id' => 20, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp', 'is_active' => 1, 'game_id' => 3],
                ['id' => 21, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp', 'is_active' => 1, 'game_id' => 3],
                ['id' => 22, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp', 'is_active' => 1, 'game_id' => 3],
                ['id' => 23, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp', 'is_active' => 1, 'game_id' => 3],
                ['id' => 24, 'symbol_value' => 100.00, 'symbol_image' => '/images/100-reais.webp', 'is_active' => 1, 'game_id' => 3]
            ],
            4 => [
                ['id' => 30, 'symbol_value' => 1.00, 'symbol_image' => '/images/1-real.webp', 'is_active' => 1, 'game_id' => 4],
                ['id' => 31, 'symbol_value' => 2.00, 'symbol_image' => '/images/2-reais.webp', 'is_active' => 1, 'game_id' => 4],
                ['id' => 32, 'symbol_value' => 5.00, 'symbol_image' => '/images/5-reais.webp', 'is_active' => 1, 'game_id' => 4],
                ['id' => 33, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp', 'is_active' => 1, 'game_id' => 4],
                ['id' => 34, 'symbol_value' => 20.00, 'symbol_image' => '/images/20-reais.webp', 'is_active' => 1, 'game_id' => 4]
            ],
            5 => [
                ['id' => 40, 'symbol_value' => 10.00, 'symbol_image' => '/images/10-reais.webp', 'is_active' => 1, 'game_id' => 5],
                ['id' => 41, 'symbol_value' => 25.00, 'symbol_image' => '/images/25-reais.webp', 'is_active' => 1, 'game_id' => 5],
                ['id' => 42, 'symbol_value' => 50.00, 'symbol_image' => '/images/50-reais.webp', 'is_active' => 1, 'game_id' => 5],
                ['id' => 43, 'symbol_value' => 100.00, 'symbol_image' => '/images/100-reais.webp', 'is_active' => 1, 'game_id' => 5],
                ['id' => 44, 'symbol_value' => 200.00, 'symbol_image' => '/images/200-reais.webp', 'is_active' => 1, 'game_id' => 5]
            ]
        ];
        
        return $symbolSets[$gameId] ?? $symbolSets[1];
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
    
    public function updateConfig($newConfig, $gameId = 1) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($newConfig as $key => $value) {
                try {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO game_config (config_key, config_value, game_id, updated_at) 
                        VALUES (?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        config_value = VALUES(config_value),
                        updated_at = NOW()
                    ");
                    $stmt->execute([$key, $value, $gameId]);
                } catch (PDOException $e) {
                    throw new Exception("Erro ao salvar configuração: " . $e->getMessage());
                }
            }
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    public function addSymbol($symbolData, $gameId = 1) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO game_symbols (symbol_value, symbol_image, is_active, game_id, created_at) 
                VALUES (?, ?, 1, ?, NOW())
            ");
            $stmt->execute([$symbolData['value'], $symbolData['image'], $gameId]);
            
            $symbolId = $this->pdo->lastInsertId();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO prize_frequency (symbol_id, frequency_percent, is_active, updated_at) 
                VALUES (?, 1.0, 1, NOW())
            ");
            $stmt->execute([$symbolId]);
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    public function deleteSymbol($symbolId) {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("DELETE FROM prize_frequency WHERE symbol_id = ?");
            $stmt->execute([$symbolId]);
            
            $stmt = $this->pdo->prepare("DELETE FROM game_symbols WHERE id = ?");
            $stmt->execute([$symbolId]);
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    public function updateFrequencies($frequencies, $gameId = 1) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($frequencies as $symbolValue => $frequency) {
                $stmt = $this->pdo->prepare("
                    UPDATE prize_frequency pf
                    JOIN game_symbols gs ON pf.symbol_id = gs.id
                    SET pf.frequency_percent = ?, pf.updated_at = NOW()
                    WHERE gs.symbol_value = ? AND gs.game_id = ?
                ");
                $stmt->execute([$frequency, $symbolValue, $gameId]);
            }
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            throw $e;
        }
    }
    
    public function getStats($gameId = 1) {
        $stats = [];
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total_games,
                       SUM(valor_aposta) as total_bet,
                       SUM(valor_ganho) as total_prize,
                       SUM(CASE WHEN valor_ganho > 0 THEN 1 ELSE 0 END) as wins
                FROM jogadas 
                WHERE game_id = ? 
                AND data_hora >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$gameId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats = [
                'total_games' => $result['total_games'] ?? 0,
                'total_bet' => number_format($result['total_bet'] ?? 0, 2),
                'total_prize' => number_format($result['total_prize'] ?? 0, 2),
                'wins' => $result['wins'] ?? 0,
                'win_rate' => $result['total_games'] > 0 ? 
                    round(($result['wins'] / $result['total_games']) * 100, 1) : 0,
                'house_edge' => number_format(($result['total_bet'] ?? 0) - ($result['total_prize'] ?? 0), 2)
            ];
            
        } catch (PDOException $e) {
            $stats = [
                'total_games' => 0,
                'total_bet' => '0.00',
                'total_prize' => '0.00',
                'wins' => 0,
                'win_rate' => 0,
                'house_edge' => '0.00'
            ];
        }
        
        return $stats;
    }
    
    public function getRecentGames($gameId = 1) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT j.id, j.user_id, j.game_id, j.valor_aposta, j.valor_ganho, j.data_hora, u.username 
                FROM jogadas j
                LEFT JOIN users u ON j.user_id = u.id
                WHERE j.game_id = ?
                ORDER BY j.data_hora DESC 
                LIMIT 50
            ");
            $stmt->execute([$gameId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function toggleSymbolStatus($symbolId, $isActive) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE game_symbols 
                SET is_active = ? 
                WHERE id = ?
            ");
            $stmt->execute([$isActive ? 1 : 0, $symbolId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function updateSymbol($symbolId, $symbolData) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE game_symbols 
                SET symbol_value = ?, symbol_image = ?
                WHERE id = ?
            ");
            $stmt->execute([$symbolData['value'], $symbolData['image'], $symbolId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}

try {
    $admin = new AdminGameConfig($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    $gameId = $_GET['game_id'] ?? $_POST['game_id'] ?? 1;
    
    if (!is_numeric($gameId)) {
        $gameId = 1;
    }
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'config';
        
        switch ($action) {
            case 'stats':
                echo json_encode([
                    'success' => true,
                    'stats' => $admin->getStats($gameId),
                    'recent_games' => $admin->getRecentGames($gameId)
                ]);
                break;
                
            default:
                $config = $admin->getFullConfig($gameId);
                echo json_encode([
                    'success' => true,
                    'config' => $config['config'],
                    'symbols' => $config['symbols'],
                    'frequencies' => $config['frequencies']
                ]);
                break;
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $gameId = $input['game_id'] ?? $gameId;
        
        switch ($action) {
            case 'update_config':
                $result = $admin->updateConfig($input['config'], $gameId);
                echo json_encode($result);
                break;
                
            case 'add_symbol':
                $result = $admin->addSymbol($input['symbol'], $gameId);
                echo json_encode($result);
                break;
                
            case 'delete_symbol':
                $result = $admin->deleteSymbol($input['id']);
                echo json_encode($result);
                break;
                
            case 'update_symbol':
                $result = $admin->updateSymbol($input['id'], $input['symbol']);
                echo json_encode($result);
                break;
                
            case 'toggle_symbol':
                $result = $admin->toggleSymbolStatus($input['id'], $input['is_active']);
                echo json_encode($result);
                break;
                
            case 'update_frequencies':
                $result = $admin->updateFrequencies($input['frequencies'], $gameId);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Ação inválida');
        }
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>