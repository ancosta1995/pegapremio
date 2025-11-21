<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=seu_banco;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM game_config ORDER BY config_key");
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = [];
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
                
                $result[$config['config_key']] = [
                    'value' => $value,
                    'type' => $config['config_type'],
                    'description' => $config['description']
                ];
            }
            
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao buscar configurações']);
        }
        break;
        
    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['configs'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados inválidos']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            foreach ($input['configs'] as $key => $data) {
                $value = $data['value'];
                $type = $data['type'];
                
                switch ($key) {
                    case 'game_cost':
                        if ($value <= 0) {
                            throw new Exception('Custo deve ser maior que zero');
                        }
                        break;
                    case 'win_chance':
                        if ($value < 0 || $value > 1) {
                            throw new Exception('Chance de vitória deve estar entre 0 e 1');
                        }
                        break;
                    case 'prize_frequency':
                        if (!is_array($value)) {
                            throw new Exception('Frequência de prêmios deve ser um objeto');
                        }
                        $total = array_sum($value);
                        if ($total != 100) {
                            throw new Exception('Frequências devem somar 100%');
                        }
                        break;
                }
                
                if ($type === 'json') {
                    $valueStr = json_encode($value);
                } else {
                    $valueStr = (string) $value;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE game_config 
                    SET config_value = ?, updated_at = NOW() 
                    WHERE config_key = ?
                ");
                $stmt->execute([$valueStr, $key]);
            }
            
            $pdo->commit();
            echo json_encode(['sucesso' => 'Configurações atualizadas']);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['erro' => $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido']);
}
?>