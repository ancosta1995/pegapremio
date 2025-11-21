<?php
define('SITE_NAME', 'RaspouPremios');
define('SITE_URL', 'https://raspoupremios.top');
define('ADMIN_EMAIL', 'admin@raspoupremios.top');

define('AFFILIATE_COMMISSION_RATE', 50);
define('MIN_WITHDRAWAL_AMOUNT', 20.00);
define('REFERRAL_URL_PARAM', 'ref');

$host = 'localhost';
$db   = ' ';
$user = ' ';
$pass = ' ';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Erro de conexão: ' . $e->getMessage());
    die('Erro de conexão com o banco de dados.');
}

date_default_timezone_set('America/Sao_Paulo');
$pdo->exec("SET time_zone = '-03:00'");

function formatarDataBrasil($data) {
    if (!$data) return '-';
    return date('d/m/Y H:i:s', strtotime($data));
}

function gerarCodigoAfiliado($nome, $pdo) {
    $codigoBase = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($nome));
    $codigoBase = substr($codigoBase, 0, 15);

    $tentativa = 0;
    do {
        $numero = ($tentativa == 0) ? rand(10, 99) : rand(100, 999);
        $codigo = $codigoBase . $numero;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM afiliados WHERE codigo_afiliado = ?");
        $stmt->execute([$codigo]);
        $existe = $stmt->fetchColumn() > 0;

        $tentativa++;
    } while ($existe && $tentativa < 10);

    return $codigo;
}

function formatarDinheiro($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData($data) {
    if (!$data) return '-';
    $datetime = new DateTime($data);
    return $datetime->format('d/m/Y H:i');
}

function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    return strlen($telefone) >= 10 && strlen($telefone) <= 11;
}

function gerarUrlIndicacao($codigoAfiliado) {
    return SITE_URL . '?' . REFERRAL_URL_PARAM . '=' . $codigoAfiliado;
}
?>
