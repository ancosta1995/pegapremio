<?php

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $u = parse_url($_SERVER['HTTP_ORIGIN']);
    $origin = $u['scheme'].'://'.$u['host'] . (!empty($u['port'])?':'.$u['port']:'');
    if ($origin === ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'?'https':'http')
                     .'://'.$_SERVER['HTTP_HOST'])) {
        header("Access-Control-Allow-Origin: $origin");
    }
}
header("Content-Type: application/json; charset=utf-8");

$hdr = getallheaders();
if (!isset($hdr['Authorization']) || $hdr['Authorization']!=='Bearer track123456') {
    http_response_code(401);
    exit(json_encode(['error'=>'Unauthorized']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    exit(json_encode(['error'=>'Invalid JSON']));
}

$isFB   = !empty($input['fbclid']);
$isKwai = !$isFB && !empty($input['click_id']);
if (!$isFB && !$isKwai) {
    http_response_code(400);
    exit(json_encode(['error'=>'Missing click_id or fbclid']));
}

if ($isKwai) {
    foreach (['click_id','pixel_id','event_name'] as $f) {
        if (empty($input[$f])) {
            http_response_code(400);
            exit(json_encode(['error'=>"Missing field: $f"]));
        }
    }
    $click_id   = $input['click_id'];
    $pixel_id   = $input['pixel_id'];
    $event_name = strtoupper($input['event_name']);
    $validK = ['EVENT_ADD_TO_CART','EVENT_CONTENT_VIEW','EVENT_COMPLETE_REGISTRATION','EVENT_PURCHASE'];
    if (!in_array($event_name, $validK, true)) {
        http_response_code(400);
        exit(json_encode(['error'=>'Invalid event_name for Kwai']));
    }
} else {
    foreach (['fbclid','pixel_id','event_name'] as $f) {
        if (empty($input[$f])) {
            http_response_code(400);
            exit(json_encode(['error'=>"Missing FB field: $f"]));
        }
    }
    $fbclid     = $input['fbclid'];
    $pixel_id   = $input['pixel_id'];
    $event_name = strtoupper($input['event_name']);
    $mapFB = [
        'EVENT_CONTENT_VIEW'  => 'ViewContent',
        'EVENT_COMPLETE_REGISTRATION'  => 'CompleteRegistration',
        'EVENT_PURCHASE'      => 'Purchase'
    ];
    if (!isset($mapFB[$event_name])) {
        http_response_code(400);
        exit(json_encode(['error'=>'Invalid event_name for FB']));
    }
    $fbEventName = $mapFB[$event_name];
    $email  = $input['email'] ?? '';
    $phone  = $input['phone'] ?? '';
}

$source = $isFB ? 'facebook' : 'kwai';
$stmt = $pdo->prepare("SELECT access_token FROM trackings WHERE source = ? AND pixel_id = ?");
$stmt->execute([$source, $pixel_id]);
$access_token = $stmt->fetchColumn();
if (!$access_token) {
    http_response_code(400);
    exit(json_encode(['error'=>'Unknown pixel_id']));
}

if ($isKwai) {
    $payload = [
        'access_token'    => $access_token,
        'clickid'         => $click_id,
        'event_name'      => $event_name,
        'is_attributed'   => 1,
        'mmpcode'         => 'PL',
        'pixelId'         => $pixel_id,
        'pixelSdkVersion' => '9.9.9',
        'properties'      => json_encode([
            'content_id'   => $input['content_id']   ?? '',
            'content_type' => $input['content_type'] ?? '',
            'content_name' => $input['content_name'] ?? '',
            'value'        => isset($input['value'])?(float)$input['value']:0,
            'currency'     => $input['currency'] ?? 'BRL',
        ]),
        'testFlag'    => false,
        'third_party' => 'shopline',
        'trackFlag'   => false,
    ];
    $url = 'https://www.adsnebula.com/log/common/api';

} else {
    function sha256(string $v): string {
        return hash('sha256', mb_strtolower(trim($v), 'UTF-8'));
    }
    $payload = [
        'data' => [[
            'event_name'       => $fbEventName,
            'event_time'       => time(),
            'action_source'    => 'website',
            'event_id'         => uniqid('evt_', true),
            'event_source_url' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_data'        => [
                'fbc'               => $fbclid,
                'em'                => $email  ? [sha256($email)] : [],
                'ph'                => $phone  ? [sha256($phone)] : [],
                'client_ip_address'=> $_SERVER['REMOTE_ADDR'],
                'client_user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? ''
            ],
            'custom_data'      => [
                'value'    => isset($input['value'])?(float)$input['value']:0,
                'currency' => $input['currency'] ?? 'BRL'
            ]
        ]]
    ];
    $url = "https://graph.facebook.com/v19.0/{$pixel_id}/events?access_token={$access_token}";
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$err      = curl_error($ch);
$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err || $code >= 400) {
    http_response_code(500);
    echo json_encode([
        'success'=>false,
        'error'  => $err ?: "HTTP $code",
        'resp'   => $response
    ]);
} else {
    echo $response;
}
