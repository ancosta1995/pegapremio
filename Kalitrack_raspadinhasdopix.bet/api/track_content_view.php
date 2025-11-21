<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require '../dino/track.php';

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    // Validar se click_id foi fornecido
    if (empty($data['click_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'click_id is required']);
        exit;
    }

    $clickId = trim($data['click_id']);

    // Enviar evento ContentView
    $result = sendTrackingEvent('ContentView', $clickId);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'ContentView event sent successfully',
            'click_id' => $clickId
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'click_id' => $clickId
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>