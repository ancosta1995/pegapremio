<?php

date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/db.php';

if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

function sha256(string $v): string {
    $cleaned = trim($v);

    if (function_exists('mb_strtolower')) {
        $cleaned = mb_strtolower($cleaned, 'UTF-8');
    } else {
        $cleaned = strtolower($cleaned);
    }

    return hash('sha256', $cleaned);
}

function sendKwaiAddToCart(string $click_id, string $pixel_id, string $access_token): bool {
    $url = 'https://www.adsnebula.com/log/common/api';
    $payload = [
        'access_token'    => $access_token,
        'clickid'         => $click_id,
        'event_name'      => 'EVENT_ADD_TO_CART',
        'is_attributed'   => 1,
        'mmpcode'         => 'PL',
        'pixelId'         => $pixel_id,
        'pixelSdkVersion' => '9.9.9',
        'properties'      => json_encode([
            'content_id'   => '198568281',
            'content_type' => 'product',
            'content_name' => 'Produto Automatizado',
            'value'        => 0,
            'currency'     => 'BRL',
        ]),
        'testFlag'    => false,
        'third_party' => 'shopline',
        'trackFlag'   => false
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json;charset=utf-8',
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "    [Kwai ADD_TO_CART] code={$code}, err='{$err}', resp={$res}\n";

    if ($err) {
        echo "    [Kwai ERROR] cURL error: {$err}\n";
        return false;
    }

    return (!$err && $code < 400 && strpos($res, '"result":1') !== false);
}

function sendKwaiPurchase(string $click_id, float $amount, string $pixel_id, string $access_token): bool {
    $url = 'https://www.adsnebula.com/log/common/api';
    $payload = [
        'access_token'    => $access_token,
        'clickid'         => $click_id,
        'event_name'      => 'EVENT_PURCHASE',
        'is_attributed'   => 1,
        'mmpcode'         => 'PL',
        'pixelId'         => $pixel_id,
        'pixelSdkVersion' => '9.9.9',
        'properties'      => json_encode([
            'content_id'   => '198568281',
            'content_type' => 'product',
            'content_name' => 'Produto Automatizado',
            'value'        => $amount,
            'currency'     => 'BRL',
        ]),
        'testFlag'    => false,
        'third_party' => 'shopline',
        'trackFlag'   => false
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json;charset=utf-8',
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "    [Kwai PURCHASE] code={$code}, err='{$err}', resp={$res}\n";

    if ($err) {
        echo "    [Kwai ERROR] cURL error: {$err}\n";
        return false;
    }

    return (!$err && $code < 400 && strpos($res, '"result":1') !== false);
}

function sendFacebook(string $fbclid, float $amount, string $pixel_id, string $access_token, string $email, string $phone, string $event_name): bool {
    echo "    [Facebook DEBUG] Sending {$event_name} - pixel_id: {$pixel_id}, fbclid: " . (!empty($fbclid) ? 'YES' : 'NO') . ", email: " . (!empty($email) ? 'YES' : 'NO') . ", phone: " . (!empty($phone) ? 'YES' : 'NO') . "\n";

    $url = "https://graph.facebook.com/v19.0/{$pixel_id}/events";

    $user_data = [];

    if (!empty($email)) {
        $user_data['em'] = sha256($email);
    }

    if (!empty($phone)) {
        $user_data['ph'] = sha256($phone);
    }

    if (!empty($fbclid)) {
        $user_data['fbc'] = $fbclid;
    }

    $user_data['client_user_agent'] = 'FacebookPixelCronJob/1.0';

    if (empty($user_data['em']) && empty($user_data['ph']) && empty($user_data['fbc'])) {
        echo "    [Facebook ERROR] No user data available (email, phone, or fbclid required)\n";
        return false;
    }

    $payload = [
        'data' => [[
            'event_name'       => $event_name,
            'event_time'       => time(),
            'action_source'    => 'website',
            'event_id'         => uniqid('evt_', true),
            'event_source_url' => 'https://raspoupremios.top',
            'user_data'        => $user_data,
            'custom_data'      => [
                'value'    => $amount,
                'currency' => 'BRL'
            ]
        ]],
        'access_token' => $access_token
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: FacebookPixelCronJob/1.0'
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "    [Facebook {$event_name}] pixel_id={$pixel_id} HTTP={$code} err={$err} resp={$res}\n";

    if ($err) {
        echo "    [Facebook ERROR] cURL error: {$err}\n";
        return false;
    }

    if ($code >= 400) {
        echo "    [Facebook ERROR] HTTP {$code}: {$res}\n";
        return false;
    }

    $response_data = json_decode($res, true);
    if (isset($response_data['error'])) {
        echo "    [Facebook ERROR] API error: " . json_encode($response_data['error']) . "\n";
        return false;
    }

    if (isset($response_data['events_received']) && $response_data['events_received'] > 0) {
        echo "    [Facebook SUCCESS] Events received: " . $response_data['events_received'] . "\n";
    }

    return true;
}

echo "=== Cron start: ".date('Y-m-d H:i:s')." ===\n";

$sql = <<<SQL
SELECT
    t.id           AS tx_id,
    t.user_id,
    t.amount,
    t.addcart_sent,
    t.purchase_sent,
    u.click_id,
    u.fbclid,
    u.pixel_id,
    u.email,
    u.phone
  FROM transactions t
  JOIN users        u ON u.id = t.user_id
 WHERE t.status = 'pago'
   AND (
       t.addcart_sent  = 0
    OR t.purchase_sent = 0
   )
ORDER BY t.created_at ASC
SQL;

try {
    $stmt = $pdo->query($sql);
    $txs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found ".count($txs)." transactions pending events.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

foreach ($txs as $t) {
    echo "-- Transaction {$t['tx_id']} (User {$t['user_id']}) -- addcart_sent={$t['addcart_sent']}, purchase_sent={$t['purchase_sent']}\n";

    $txId     = $t['tx_id'];
    $uid      = $t['user_id'];
    $click_id = $t['click_id'] ?? '';
    $fbclid   = $t['fbclid'] ?? '';
    $pixel_id = $t['pixel_id'] ?? '';
    $email    = $t['email'] ?? '';
    $phone    = $t['phone'] ?? '';
    $amount   = (float)$t['amount'];

    echo "    -> click_id: " . ($click_id ?: 'VAZIO') . "\n";
    echo "    -> fbclid: " . ($fbclid ?: 'VAZIO') . "\n";
    echo "    -> pixel_id: " . ($pixel_id ?: 'VAZIO') . "\n";
    echo "    -> email: " . ($email ?: 'VAZIO') . "\n";
    echo "    -> phone: " . ($phone ?: 'VAZIO') . "\n";

    if (empty($click_id) && empty($fbclid)) {
        echo "  ❌ Skipping: No click_id or fbclid found for user {$uid}\n";
        continue;
    }

    $platform = '';
    $final_pixel_id = $pixel_id;

    if (!empty($click_id)) {
        $platform = 'Kwai';
        if (empty($pixel_id)) {
            echo "  ❌ Skipping Kwai: No pixel_id found for user {$uid}\n";
            continue;
        }
    } elseif (!empty($fbclid)) {
        $platform = 'Facebook';

        if (empty($pixel_id)) {
            echo "  🔍 User has no pixel_id, searching for Facebook pixel in trackings...\n";
            try {
                $cfg = $pdo->prepare("SELECT pixel_id FROM trackings WHERE source='facebook' LIMIT 1");
                $cfg->execute();
                $fallback_pixel = $cfg->fetchColumn();

                if ($fallback_pixel) {
                    $final_pixel_id = $fallback_pixel;
                    echo "  ✅ Using fallback Facebook pixel_id: {$final_pixel_id}\n";
                } else {
                    echo "  ❌ No Facebook pixels found in trackings table\n";
                    continue;
                }
            } catch (PDOException $e) {
                echo "  ❌ Error searching for Facebook pixel: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }

    echo "  📊 Platform: {$platform} | Using pixel_id: {$final_pixel_id}\n";

    if ($t['addcart_sent'] == 0) {
        $sent = false;

        if (!empty($click_id)) {
            echo "  Sending Kwai ADD_TO_CART...\n";
            try {
                $cfg = $pdo->prepare("SELECT access_token FROM trackings WHERE source='kwai' AND pixel_id = ?");
                $cfg->execute([$final_pixel_id]);
                $token = $cfg->fetchColumn();

                if (!$token) {
                    echo "    -> ERROR: No access_token found for Kwai pixel_id: {$final_pixel_id}\n";
                } else {
                    echo "    -> Access token found: " . substr($token, 0, 10) . "...\n";
                    if (sendKwaiAddToCart($click_id, $final_pixel_id, $token)) {
                        $sent = true;
                    }
                }
            } catch (PDOException $e) {
                echo "    -> Database error getting Kwai token: " . $e->getMessage() . "\n";
            }
        } elseif (!empty($fbclid)) {
            echo "  Sending Facebook AddToCart...\n";
            try {
                $cfg = $pdo->prepare("SELECT access_token FROM trackings WHERE source='facebook' AND pixel_id = ?");
                $cfg->execute([$final_pixel_id]);
                $token = $cfg->fetchColumn();

                if (!$token) {
                    echo "    -> ERROR: No access_token found for Facebook pixel_id: {$final_pixel_id}\n";
                } else {
                    echo "    -> Access token found: " . substr($token, 0, 10) . "...\n";
                    if (sendFacebook($fbclid, 0, $final_pixel_id, $token, $email, $phone, 'AddToCart')) {
                        $sent = true;
                    }
                }
            } catch (PDOException $e) {
                echo "    -> Database error getting Facebook token: " . $e->getMessage() . "\n";
            }
        }

        if ($sent) {
            try {
                $pdo->prepare("UPDATE transactions SET addcart_sent = 1 WHERE id = ?")
                    ->execute([$txId]);
                echo "    -> transactions.addcart_sent updated to 1\n";
            } catch (PDOException $e) {
                echo "    -> Error updating addcart_sent: " . $e->getMessage() . "\n";
            }
        } else {
            echo "    -> ADD_TO_CART not sent (sem token ou erro)\n";
        }
    } else {
        echo "  Skipping ADD_TO_CART (já enviado)\n";
    }

    if ($t['purchase_sent'] == 0) {
        $sent = false;

        if (!empty($click_id)) {
            echo "  Sending Kwai PURCHASE...\n";
            try {
                $cfg = $pdo->prepare("SELECT access_token FROM trackings WHERE source='kwai' AND pixel_id = ?");
                $cfg->execute([$final_pixel_id]);
                $token = $cfg->fetchColumn();

                if (!$token) {
                    echo "    -> ERROR: No access_token found for Kwai pixel_id: {$final_pixel_id}\n";
                } else {
                    echo "    -> Access token found: " . substr($token, 0, 10) . "...\n";
                    if (sendKwaiPurchase($click_id, $amount, $final_pixel_id, $token)) {
                        $sent = true;
                    }
                }
            } catch (PDOException $e) {
                echo "    -> Database error getting Kwai token: " . $e->getMessage() . "\n";
            }
        } elseif (!empty($fbclid)) {
            echo "  Sending Facebook Purchase...\n";
            try {
                $cfg = $pdo->prepare("SELECT access_token FROM trackings WHERE source='facebook' AND pixel_id = ?");
                $cfg->execute([$final_pixel_id]);
                $token = $cfg->fetchColumn();

                if (!$token) {
                    echo "    -> ERROR: No access_token found for Facebook pixel_id: {$final_pixel_id}\n";
                } else {
                    echo "    -> Access token found: " . substr($token, 0, 10) . "...\n";
                    if (sendFacebook($fbclid, $amount, $final_pixel_id, $token, $email, $phone, 'Purchase')) {
                        $sent = true;
                    }
                }
            } catch (PDOException $e) {
                echo "    -> Database error getting Facebook token: " . $e->getMessage() . "\n";
            }
        }

        if ($sent) {
            try {
                $pdo->prepare("UPDATE transactions SET purchase_sent = 1 WHERE id = ?")
                    ->execute([$txId]);
                echo "    -> transactions.purchase_sent updated to 1\n";
            } catch (PDOException $e) {
                echo "    -> Error updating purchase_sent: " . $e->getMessage() . "\n";
            }
        } else {
            echo "    -> PURCHASE not sent (sem token ou erro)\n";
        }
    } else {
        echo "  Skipping PURCHASE (já enviado)\n";
    }

    echo "\n";
}

echo "=== Cron end: ".date('Y-m-d H:i:s')." ===\n";
