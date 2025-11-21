<?php
require_once 'config.php';
require_once 'functions.php';


class CommissionHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function setCommissionByEmail($email, $commission) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE afiliados 
                SET comissao_percentual = ? 
                WHERE email = ?
            ");
            $stmt->execute([$commission, $email]);
            
            if ($stmt->rowCount() > 0) {
                echo "✅ Comissão de {$commission}% definida para {$email}\n";
                return true;
            } else {
                echo "❌ Afiliado com email {$email} não encontrado\n";
                return false;
            }
        } catch(PDOException $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function setCommissionByCode($codigo, $commission) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE afiliados 
                SET comissao_percentual = ? 
                WHERE codigo_afiliado = ?
            ");
            $stmt->execute([$commission, $codigo]);
            
            if ($stmt->rowCount() > 0) {
                echo "✅ Comissão de {$commission}% definida para código {$codigo}\n";
                return true;
            } else {
                echo "❌ Afiliado com código {$codigo} não encontrado\n";
                return false;
            }
        } catch(PDOException $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function setBulkCommission($commission, $condition = '') {
        try {
            $sql = "UPDATE afiliados SET comissao_percentual = ?";
            $params = [$commission];
            
            if ($condition) {
                $sql .= " WHERE " . $condition;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $affected = $stmt->rowCount();
            echo "✅ Comissão de {$commission}% definida para {$affected} afiliados\n";
            return $affected;
            
        } catch(PDOException $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function listAffiliatesCommissions() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    id,
                    nome_completo,
                    email,
                    codigo_afiliado,
                    comissao_percentual,
                    criado_em
                FROM afiliados 
                ORDER BY criado_em DESC
            ");
            
            $afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n📋 Lista de Afiliados e Comissões:\n";
            echo str_repeat("-", 80) . "\n";
            printf("%-4s %-25s %-25s %-15s %-8s\n", 
                "ID", "Nome", "Email", "Código", "Comissão");
            echo str_repeat("-", 80) . "\n";
            
            foreach ($afiliados as $afiliado) {
                printf("%-4d %-25s %-25s %-15s %-8s%%\n",
                    $afiliado['id'],
                    substr($afiliado['nome_completo'], 0, 24),
                    substr($afiliado['email'], 0, 24),
                    $afiliado['codigo_afiliado'],
                    number_format($afiliado['comissao_percentual'], 1)
                );
            }
            echo str_repeat("-", 80) . "\n";
            echo "Total: " . count($afiliados) . " afiliados\n\n";
            
            return $afiliados;
            
        } catch(PDOException $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function createCommissionTiers() {
        $tiers = [
            'Bronze' => 30,
            'Prata' => 40, 
            'Ouro' => 50,
            'Platina' => 60,
            'Diamante' => 70
        ];
        
        echo "\n💎 Grupos de Comissão Sugeridos:\n";
        foreach ($tiers as $tier => $commission) {
            echo "  {$tier}: {$commission}%\n";
        }
        echo "\nPara aplicar um grupo, use: setCommissionByEmail('email@exemplo.com', {$commission})\n\n";
        
        return $tiers;
    }
    
    public function performanceByCommission() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    a.comissao_percentual,
                    COUNT(a.id) as total_afiliados,
                    COUNT(u.id) as total_indicados,
                    COALESCE(SUM(t.amount), 0) as total_depositos,
                    COALESCE(AVG(t.amount), 0) as media_deposito
                FROM afiliados a
                LEFT JOIN users u ON u.referral_id = a.codigo_afiliado
                LEFT JOIN transactions t ON t.username = u.username AND t.status = 'pago'
                GROUP BY a.comissao_percentual
                ORDER BY a.comissao_percentual DESC
            ");
            
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n📊 Performance por Nível de Comissão:\n";
            echo str_repeat("-", 70) . "\n";
            printf("%-10s %-12s %-10s %-15s %-15s\n", 
                "Comissão", "Afiliados", "Indicados", "Tot.Depósitos", "Média Dep.");
            echo str_repeat("-", 70) . "\n";
            
            foreach ($dados as $linha) {
                printf("%-10s%% %-12d %-10d %-15s %-15s\n",
                    number_format($linha['comissao_percentual'], 1),
                    $linha['total_afiliados'],
                    $linha['total_indicados'],
                    formatarDinheiro($linha['total_depositos']),
                    formatarDinheiro($linha['media_deposito'])
                );
            }
            echo str_repeat("-", 70) . "\n\n";
            
            return $dados;
            
        } catch(PDOException $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

if (php_sapi_name() === 'cli' || basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $helper = new CommissionHelper($pdo);
    
    echo "🎯 Sistema de Comissões Individuais - Helper\n";
    echo "==========================================\n\n";
    
    if ($argc > 1) {
        switch ($argv[1]) {
            case 'list':
                $helper->listAffiliatesCommissions();
                break;
                
            case 'set':
                if ($argc >= 4) {
                    $email = $argv[2];
                    $commission = floatval($argv[3]);
                    $helper->setCommissionByEmail($email, $commission);
                } else {
                    echo "Uso: php commission_helper.php set email@exemplo.com 60\n";
                }
                break;
                
            case 'bulk':
                if ($argc >= 3) {
                    $commission = floatval($argv[2]);
                    $condition = $argv[3] ?? '';
                    $helper->setBulkCommission($commission, $condition);
                } else {
                    echo "Uso: php commission_helper.php bulk 45 [condição]\n";
                }
                break;
                
            case 'tiers':
                $helper->createCommissionTiers();
                break;
                
            case 'performance':
                $helper->performanceByCommission();
                break;
                
            default:
                echo "Comandos disponíveis:\n";
                echo "  list - Listar afiliados e comissões\n";
                echo "  set email comissao - Definir comissão para um afiliado\n";
                echo "  bulk comissao - Definir comissão para todos\n";
                echo "  tiers - Mostrar grupos de comissão\n";
                echo "  performance - Relatório de performance\n";
        }
    } else {
        $helper->listAffiliatesCommissions();
        $helper->createCommissionTiers();
        $helper->performanceByCommission();
    }
}
?>