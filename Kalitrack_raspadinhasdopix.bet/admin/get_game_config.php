<?php
header('Content-Type: application/json');

require_once '/includes/config.php';

try {
    $stmt = $pdo->query("SELECT * FROM game_config ORDER BY config_key");
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gameConfig = [
        'cost' => 1.00,
        'winChance' => 0.27,
        'symbols' => ['2', '5', '10', '50', '100'],
        'prizeFrequency' => [
            '2' => 60,
            '5' => 30,
            '10' => 7,
            '50' => 2,
            '100' => 1
        ]
    ];
    
    foreach ($configs as $config) {
        $value = $config['config_value'];
        
        switch ($config['config_type']) {
            case 'float':
                $value = (float) $value;
                break;
            case 'int':
                $value = (int) $value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }
        
        switch ($config['config_key']) {
            case 'game_cost':
                $gameConfig['cost'] = $value;
                break;
            case 'win_chance':
                $gameConfig['winChance'] = $value;
                break;
            case 'symbols':
                $gameConfig['symbols'] = $value;
                break;
            case 'prize_frequency':
                $gameConfig['prizeFrequency'] = $value;
                break;
        }
    }
    
    echo json_encode($gameConfig);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao buscar configurações',
        'cost' => 1.00,
        'winChance' => 0.27,
        'symbols' => ['2', '5', '10', '50', '100'],
        'prizeFrequency' => [
            '2' => 60,
            '5' => 30,
            '10' => 7,
            '50' => 2,
            '100' => 1
        ]
    ]);
}
?>