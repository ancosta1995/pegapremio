<?php
require_once 'config.php';

function getAffiliateStats($afiliadoId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT codigo_afiliado, comissao_percentual FROM afiliados WHERE id = ?");
        $stmt->execute([$afiliadoId]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$afiliado) {
            return [
                'total_indicados' => 0,
                'total_depositos' => 0.00,
                'total_saques_indicados' => 0.00,
                'comissao_disponivel' => 0.00,
                'comissao_total' => 0.00,
                'comissao_paga' => 0.00,
                'comissao_pendente' => 0.00,
                'percentual_comissao' => 50
            ];
        }

        $codigo = $afiliado['codigo_afiliado'];
        $percentualComissao = $afiliado['comissao_percentual'] ?? 50.00;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE referral_id = ?");
        $stmt->execute([$codigo]);
        $totalIndicados = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_id = ?");
        $stmt->execute([$codigo]);
        $usuariosIndicados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $totalDepositos = 0;
        $totalSaquesIndicados = 0;

        if (!empty($usuariosIndicados)) {
            $placeholders = str_repeat('?,', count($usuariosIndicados) - 1) . '?';

            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(t.amount), 0)
                FROM transactions t
                INNER JOIN users u ON t.username = u.username
                WHERE u.id IN ($placeholders) AND t.status = 'pago'
            ");
            $stmt->execute($usuariosIndicados);
            $totalDepositos = $stmt->fetchColumn() ?: 0;

            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(s.valor), 0)
                FROM saques s
                WHERE s.user_id IN ($placeholders) AND s.status IN ('aprovado', 'pago')
            ");
            $stmt->execute($usuariosIndicados);
            $totalSaquesIndicados = $stmt->fetchColumn() ?: 0;
        }

        $excedente = max(0, $totalDepositos - $totalSaquesIndicados);
        $comissaoTotal = $excedente * ($percentualComissao / 100);

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(valor), 0)
            FROM saques_afiliados
            WHERE afiliado_id = ? AND status IN ('aprovado', 'pago')
        ");
        $stmt->execute([$afiliadoId]);
        $comissaoPaga = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(valor), 0)
            FROM saques_afiliados
            WHERE afiliado_id = ? AND status = 'pendente'
        ");
        $stmt->execute([$afiliadoId]);
        $comissaoPendente = $stmt->fetchColumn() ?: 0;

        $comissaoDisponivel = max(0, $comissaoTotal - $comissaoPaga - $comissaoPendente);

        return [
            'total_indicados' => (int)$totalIndicados,
            'total_depositos' => (float)$totalDepositos,
            'total_saques_indicados' => (float)$totalSaquesIndicados,
            'comissao_disponivel' => (float)$comissaoDisponivel,
            'comissao_total' => (float)$comissaoTotal,
            'comissao_paga' => (float)$comissaoPaga,
            'comissao_pendente' => (float)$comissaoPendente,
            'excedente' => (float)$excedente,
            'percentual_comissao' => (float)$percentualComissao
        ];

    } catch(PDOException $e) {
        error_log("Erro ao buscar estatísticas: " . $e->getMessage());
        return [
            'total_indicados' => 0,
            'total_depositos' => 0.00,
            'total_saques_indicados' => 0.00,
            'comissao_disponivel' => 0.00,
            'comissao_total' => 0.00,
            'comissao_paga' => 0.00,
            'comissao_pendente' => 0.00,
            'percentual_comissao' => 50
        ];
    }
}


function atualizarComissaoAfiliado($afiliadoId, $novoPercentual) {
    global $pdo;

    try {
        if ($novoPercentual < 0 || $novoPercentual > 100) {
            return ['success' => false, 'message' => 'Percentual deve estar entre 0% e 100%'];
        }

        $stmt = $pdo->prepare("UPDATE afiliados SET comissao_percentual = ? WHERE id = ?");
        $stmt->execute([$novoPercentual, $afiliadoId]);

        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Comissão atualizada com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Afiliado não encontrado'];
        }

    } catch(PDOException $e) {
        error_log("Erro ao atualizar comissão: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
    }
}


function getAfiliadoComComissao($afiliadoId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, nome_completo, email, telefone, codigo_afiliado,
                   comissao_percentual, criado_em, ultimo_login
            FROM afiliados
            WHERE id = ?
        ");
        $stmt->execute([$afiliadoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar dados do afiliado: " . $e->getMessage());
        return false;
    }
}


function listarAfiliadosComComissoes($limite = 50) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.nome_completo,
                a.email,
                a.codigo_afiliado,
                a.comissao_percentual,
                a.criado_em,
                a.ultimo_login,
                COALESCE(COUNT(u.id), 0) as total_indicados,
                COALESCE(SUM(t.amount), 0) as total_depositos
            FROM afiliados a
            LEFT JOIN users u ON u.referral_id = a.codigo_afiliado
            LEFT JOIN transactions t ON t.username = u.username AND t.status = 'pago'
            GROUP BY a.id
            ORDER BY a.criado_em DESC
            LIMIT ?
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao listar afiliados: " . $e->getMessage());
        return [];
    }
}


function cadastrarAfiliado($dados, $comissaoPersonalizada = null) {
    global $pdo;

    try {
        if (empty($dados['nome']) || empty($dados['email']) || empty($dados['senha'])) {
            return ['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos'];
        }

        if (!validarEmail($dados['email'])) {
            return ['success' => false, 'message' => 'Email inválido'];
        }

        if (strlen($dados['senha']) < 6) {
            return ['success' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres'];
        }

        $comissao = $comissaoPersonalizada ?? 50.00;
        if ($comissao < 0 || $comissao > 100) {
            return ['success' => false, 'message' => 'Comissão deve estar entre 0% e 100%'];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM afiliados WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }

        $pdo->beginTransaction();

        $codigoAfiliado = gerarCodigoAfiliado($dados['nome'], $pdo);

        $stmt = $pdo->prepare("
            INSERT INTO afiliados (nome_completo, email, telefone, senha_hash, codigo_afiliado, comissao_percentual)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        $stmt->execute([
            $dados['nome'],
            $dados['email'],
            $dados['telefone'] ?? null,
            $senhaHash,
            $codigoAfiliado,
            $comissao
        ]);

        $afiliadoId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO estatisticas_afiliados (afiliado_id) VALUES (?)
        ");
        $stmt->execute([$afiliadoId]);

        $pdo->commit();

        return [
            'success' => true,
            'afiliado_id' => $afiliadoId,
            'codigo_afiliado' => $codigoAfiliado,
            'comissao_percentual' => $comissao,
            'message' => 'Cadastro realizado com sucesso'
        ];

    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao cadastrar afiliado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
    }
}

function getIndicatedUsers($afiliadoId, $limit = 50) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT codigo_afiliado FROM afiliados WHERE id = ?");
        $stmt->execute([$afiliadoId]);
        $codigo = $stmt->fetchColumn();

        if (!$codigo) return [];

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.username,
                u.email,
                u.saldo,
                u.created_at,
                u.referral_id,
                COALESCE(depositos.total, 0) as total_depositos,
                COALESCE(saques.total, 0) as total_saques,
                COALESCE(jogadas.total_jogadas, 0) as total_jogadas,
                COALESCE(jogadas.total_apostado, 0) as total_apostado,
                COALESCE(jogadas.total_ganho, 0) as total_ganho
            FROM users u
            LEFT JOIN (
                SELECT u2.id, SUM(t.amount) as total
                FROM users u2
                INNER JOIN transactions t ON u2.username = t.username
                WHERE t.status = 'pago'
                GROUP BY u2.id
            ) depositos ON u.id = depositos.id
            LEFT JOIN (
                SELECT user_id, SUM(valor) as total
                FROM saques
                WHERE status IN ('aprovado', 'pago')
                GROUP BY user_id
            ) saques ON u.id = saques.user_id
            LEFT JOIN (
                SELECT user_id,
                       COUNT(*) as total_jogadas,
                       SUM(valor_aposta) as total_apostado,
                       SUM(valor_ganho) as total_ganho
                FROM jogadas
                GROUP BY user_id
            ) jogadas ON u.id = jogadas.user_id
            WHERE u.referral_id = ?
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$codigo, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar usuários indicados: " . $e->getMessage());
        return [];
    }
}

function getAffiliateSaques($afiliadoId, $limit = 50) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, valor, chave_pix, tipo_chave, nome_titular, status,
                   observacoes, criado_em, processado_em
            FROM saques_afiliados
            WHERE afiliado_id = ?
            ORDER BY criado_em DESC
            LIMIT ?
        ");
        $stmt->execute([$afiliadoId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar saques: " . $e->getMessage());
        return [];
    }
}

function getIndicatedUsersTransactions($afiliadoId, $limit = 20) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT codigo_afiliado FROM afiliados WHERE id = ?");
        $stmt->execute([$afiliadoId]);
        $codigo = $stmt->fetchColumn();

        if (!$codigo) return [];

        $stmt = $pdo->prepare("
            SELECT
                t.id,
                t.transaction_id,
                t.username,
                t.amount as valor,
                t.status,
                t.created_at,
                u.email
            FROM transactions t
            INNER JOIN users u ON t.username = u.username
            WHERE u.referral_id = ?
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$codigo, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar transações: " . $e->getMessage());
        return [];
    }
}

function getIndicatedUsersGames($afiliadoId, $limit = 20) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT codigo_afiliado FROM afiliados WHERE id = ?");
        $stmt->execute([$afiliadoId]);
        $codigo = $stmt->fetchColumn();

        if (!$codigo) return [];

        $stmt = $pdo->prepare("
            SELECT
                j.id,
                j.user_id,
                j.valor_aposta,
                j.valor_ganho,
                j.data_hora,
                u.username,
                u.email
            FROM jogadas j
            INNER JOIN users u ON j.user_id = u.id
            WHERE u.referral_id = ?
            ORDER BY j.data_hora DESC
            LIMIT ?
        ");
        $stmt->execute([$codigo, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar jogadas: " . $e->getMessage());
        return [];
    }
}

function updateAffiliateStats($afiliadoId) {
    global $pdo;

    try {
        $stats = getAffiliateStats($afiliadoId);

        $stmt = $pdo->prepare("
            INSERT INTO estatisticas_afiliados
            (afiliado_id, total_indicados, total_depositos, total_saques_indicados,
             total_comissoes, comissoes_pagas, comissoes_pendentes, ultima_atualizacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            total_indicados = VALUES(total_indicados),
            total_depositos = VALUES(total_depositos),
            total_saques_indicados = VALUES(total_saques_indicados),
            total_comissoes = VALUES(total_comissoes),
            comissoes_pagas = VALUES(comissoes_pagas),
            comissoes_pendentes = VALUES(comissoes_pendentes),
            ultima_atualizacao = NOW()
        ");

        $stmt->execute([
            $afiliadoId,
            $stats['total_indicados'],
            $stats['total_depositos'],
            $stats['total_saques_indicados'],
            $stats['comissao_total'],
            $stats['comissao_paga'],
            $stats['comissao_pendente']
        ]);

        return true;

    } catch(PDOException $e) {
        error_log("Erro ao atualizar estatísticas: " . $e->getMessage());
        return false;
    }
}

function solicitarSaque($afiliadoId, $valor, $chavePix, $tipoChave, $nomeTitular) {
    global $pdo;

    try {
        $stats = getAffiliateStats($afiliadoId);

        if ($valor > $stats['comissao_disponivel']) {
            return ['success' => false, 'message' => 'Valor solicitado maior que o saldo disponível'];
        }

        $minWithdraw = defined('MIN_WITHDRAWAL_AMOUNT') ? MIN_WITHDRAWAL_AMOUNT : 20.00;
        if ($valor < $minWithdraw) {
            return ['success' => false, 'message' => 'Valor mínimo para saque é ' . formatarDinheiro($minWithdraw)];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO saques_afiliados (afiliado_id, valor, chave_pix, tipo_chave, nome_titular, status)
            VALUES (?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([$afiliadoId, $valor, $chavePix, $tipoChave, $nomeTitular]);

        $pdo->commit();

        return ['success' => true, 'message' => 'Solicitação de saque enviada com sucesso'];

    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao solicitar saque: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
    }
}

function validarChavePix($chave, $tipo) {
    $chave = trim($chave);

    switch($tipo) {
        case 'cpf':
            return validarCPF($chave);
        case 'email':
            return validarEmail($chave);
        case 'telefone':
            return validarTelefone($chave);
        case 'cnpj':
            return validarCNPJ($chave);
        case 'aleatoria':
            return strlen($chave) >= 10 && strlen($chave) <= 50;
        default:
            return false;
    }
}

function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }

    $b = [6,7,8,9,2,3,4,5,6,7,8,9];

    for ($i = 0, $n = 0; $i < 12; $n += $cnpj[$i] * $b[++$i]);

    if ($cnpj[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
        return false;
    }

    for ($i = 0, $n = 0; $i <= 12; $n += $cnpj[$i] * $b[$i++]);

    if ($cnpj[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
        return false;
    }

    return true;
}

function autenticarAfiliado($email, $senha) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, nome_completo, email, senha_hash, codigo_afiliado, comissao_percentual
            FROM afiliados
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $afiliado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$afiliado) {
            return ['success' => false, 'message' => 'Email não encontrado'];
        }

        if (!password_verify($senha, $afiliado['senha_hash'])) {
            return ['success' => false, 'message' => 'Senha incorreta'];
        }

        // Atualizar último login
        $stmt = $pdo->prepare("UPDATE afiliados SET ultimo_login = NOW() WHERE id = ?");
        $stmt->execute([$afiliado['id']]);

        return [
            'success' => true,
            'afiliado' => [
                'id' => $afiliado['id'],
                'nome' => $afiliado['nome_completo'],
                'email' => $afiliado['email'],
                'codigo_afiliado' => $afiliado['codigo_afiliado'],
                'comissao_percentual' => $afiliado['comissao_percentual']
            ]
        ];

    } catch(PDOException $e) {
        error_log("Erro ao autenticar afiliado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
    }
}

function getDadosAfiliado($afiliadoId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT id, nome_completo, email, telefone, codigo_afiliado,
                   comissao_percentual, criado_em, ultimo_login
            FROM afiliados
            WHERE id = ?
        ");
        $stmt->execute([$afiliadoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar dados do afiliado: " . $e->getMessage());
        return false;
    }
}

function getAffiliateDetailedReport($afiliadoId) {
    global $pdo;

    try {
        $stats = getAffiliateStats($afiliadoId);
        $indicados = getIndicatedUsers($afiliadoId, 100);
        $transactions = getIndicatedUsersTransactions($afiliadoId, 50);
        $jogadas = getIndicatedUsersGames($afiliadoId, 50);
        $saques = getAffiliateSaques($afiliadoId, 20);

        return [
            'estatisticas' => $stats,
            'usuarios_indicados' => $indicados,
            'transactions_indicados' => $transactions,
            'jogadas_indicados' => $jogadas,
            'saques_afiliado' => $saques,
            'gerado_em' => date('Y-m-d H:i:s')
        ];

    } catch(Exception $e) {
        error_log("Erro ao gerar relatório: " . $e->getMessage());
        return false;
    }
}


function listarAfiliadosComControles($filtros = []) {
    global $pdo;

    try {
        $where = ['1=1'];
        $params = [];

        if (!empty($filtros['busca'])) {
            $where[] = "(a.nome_completo LIKE ? OR a.email LIKE ? OR a.codigo_afiliado LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $params = array_merge($params, [$busca, $busca, $busca]);
        }

        if (!empty($filtros['status'])) {
            $where[] = "COALESCE(a.status, 'ativo') = ?";
            $params[] = $filtros['status'];
        }

        $sql = "
            SELECT
                a.id,
                a.nome_completo,
                a.email,
                a.codigo_afiliado,
                COALESCE(a.comissao_percentual, 50.00) as comissao_percentual,
                COALESCE(a.status, 'ativo') as status,
                a.criado_em,
                a.ultimo_login,
                COUNT(DISTINCT u.id) as total_indicados,
                COALESCE(SUM(DISTINCT t.amount), 0) as total_depositos,
                COALESCE(SUM(DISTINCT CASE WHEN s.status IN ('aprovado', 'pago') THEN s.valor ELSE 0 END), 0) as total_saques,
                -- Campos de controle visual (para compatibilidade)
                100.00 as porcentagem_indicacoes,
                100.00 as porcentagem_depositos,
                100.00 as porcentagem_comissao_visual,
                COALESCE(a.comissao_percentual, 50.00) as porcentagem_comissao
            FROM afiliados a
            LEFT JOIN users u ON u.referral_id = a.codigo_afiliado
            LEFT JOIN transactions t ON t.username = u.username AND t.status = 'pago'
            LEFT JOIN saques s ON s.user_id = u.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY a.id, a.nome_completo, a.email, a.codigo_afiliado, a.comissao_percentual, a.status, a.criado_em, a.ultimo_login
            ORDER BY a.criado_em DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao listar afiliados com controles: " . $e->getMessage());
        return [];
    }
}


function atualizarControlesAfiliado($afiliadoId, $dados, $adminId = null) {
    global $pdo;

    try {
        $comissao = $dados['porcentagem_comissao'] ?? 50;
        if ($comissao < 0 || $comissao > 100) {
            return ['success' => false, 'message' => 'Comissão deve estar entre 0% e 100%'];
        }

        $stmt = $pdo->prepare("
            UPDATE afiliados
            SET comissao_percentual = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$comissao, $afiliadoId]);

        if ($stmt->rowCount() > 0) {
            if ($adminId) {
                error_log("Admin {$adminId} alterou comissão do afiliado {$afiliadoId} para {$comissao}%");
            }

            return [
                'success' => true,
                'message' => "Comissão atualizada para {$comissao}% com sucesso"
            ];
        } else {
            return ['success' => false, 'message' => 'Afiliado não encontrado'];
        }

    } catch(PDOException $e) {
        error_log("Erro ao atualizar controles do afiliado: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
    }
}
?>
