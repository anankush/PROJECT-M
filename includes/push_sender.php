<?php
/**
 * Pure PHP Web Push Sender — No Composer Required
 * Uses PHP's built-in OpenSSL for ECDH + HKDF + AES-128-GCM encryption
 * and VAPID JWT signing.
 *
 * Security: VAPID private key is read from secrets.php (server-side only).
 * Never exposed to client or JS files.
 */

function push_base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function push_base64url_decode(string $data): string {
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

function push_create_vapid_jwt(string $audience, string $subject, string $privateKeyB64): string {
    $header  = push_base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = push_base64url_encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => $subject,
    ]));

    $privRaw = push_base64url_decode($privateKeyB64);
    $privDer  = "\x30\x77\x02\x01\x01\x04\x20" . $privRaw
              . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
              . "\xa1\x44\x03\x42\x00";

    $privPem = "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($privDer), 64, "\n")
             . "-----END EC PRIVATE KEY-----";

    $privKey = openssl_pkey_get_private($privPem);
    if (!$privKey) {
        return '';
    }
    $der = '';
    openssl_sign("$header.$payload", $der, $privKey, OPENSSL_ALGO_SHA256);

    if (empty($der) || strlen($der) < 4) {
        return '';
    }

    $r = substr($der, 4, ord($der[3]));
    $s = substr($der, 4 + ord($der[3]) + 2);
    if (strlen($r) === 33) $r = substr($r, 1);
    if (strlen($s) === 33) $s = substr($s, 1);
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);

    $sig = push_base64url_encode($r . $s);
    return "$header.$payload.$sig";
}

function push_hkdf(string $salt, string $ikm, string $info, int $length): string {
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $t   = '';
    $okm = '';
    for ($i = 1; strlen($okm) < $length; $i++) {
        $t    = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
        $okm .= $t;
    }
    return substr($okm, 0, $length);
}

function push_encrypt_payload(string $endpoint, string $p256dhB64, string $authB64, string $plaintext): array {
    $userAgentPublicKey = push_base64url_decode($p256dhB64);
    $authSecret         = push_base64url_decode($authB64);

    // Generate ephemeral ECDH key pair
    $config = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $paths = [
            'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
            'C:\\xampp\\apache\\conf\\openssl.cnf',
            'C:\\xampp\\php\\extras\\openssl\\openssl.cnf'
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $config['config'] = $path;
                break;
            }
        }
    }
    $ephemeral = openssl_pkey_new($config);
    if (!$ephemeral) {
        return [];
    }
    $ephDetails = openssl_pkey_get_details($ephemeral);
    if (!$ephDetails) {
        return [];
    }

    $senderPublicKey = "\x04" . $ephDetails['ec']['x'] . $ephDetails['ec']['y'];

    // Compute shared secret via ECDH
    $recipientKey = openssl_pkey_get_public(
        "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode(
            "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
            . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00"
            . $userAgentPublicKey
        ), 64, "\n")
        . "-----END PUBLIC KEY-----"
    );
    if (!$recipientKey) {
        return [];
    }

    $privPem = '';
    openssl_pkey_export($ephemeral, $privPem, null, $config);
    $privKey = openssl_pkey_get_private($privPem);
    if (!$privKey) {
        return [];
    }
    $sharedSecret = '';
    $computed = openssl_dh_compute_key($sharedSecret, $recipientKey, $privKey);
    if ($computed === false || empty($sharedSecret)) {
        return [];
    }

    // HKDF key derivation
    $salt    = random_bytes(16);
    $context = "\x00" . pack('n', strlen($userAgentPublicKey)) . $userAgentPublicKey
             . pack('n', strlen($senderPublicKey)) . $senderPublicKey;

    $prk        = push_hkdf($authSecret, $sharedSecret, "Content-Encoding: auth\x00", 32);
    $contentEnc = push_hkdf($salt, $prk, "Content-Encoding: aesgcm\x00" . $context, 16);
    $nonce      = push_hkdf($salt, $prk, "Content-Encoding: nonce\x00"  . $context, 12);

    // AES-128-GCM encrypt
    $padded     = "\x00\x00" . $plaintext;
    $tag        = '';
    $ciphertext = openssl_encrypt($padded, 'aes-128-gcm', $contentEnc, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) {
        return [];
    }

    return [
        'ciphertext'      => $ciphertext . $tag,
        'salt'            => $salt,
        'serverPublicKey' => $senderPublicKey,
    ];
}

function sendPushToSubscription(string $endpoint, string $p256dh, string $auth, array $payload): bool {
    if (!defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY') || !defined('VAPID_SUBJECT')) {
        error_log('Push: VAPID keys not configured');
        return false;
    }

    $message   = json_encode($payload);
    $encrypted = push_encrypt_payload($endpoint, $p256dh, $auth, $message);
    if (empty($encrypted)) {
        return false;
    }

    $origin   = preg_replace('/^(https?:\/\/[^\/]+).*/', '$1', $endpoint);
    $jwt      = push_create_vapid_jwt($origin, VAPID_SUBJECT, VAPID_PRIVATE_KEY);
    $vapidPub = VAPID_PUBLIC_KEY;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => $encrypted['ciphertext'],
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'Encryption: salt=' . push_base64url_encode($encrypted['salt']),
            'Crypto-Key: dh='   . push_base64url_encode($encrypted['serverPublicKey']) . ';p256ecdsa=' . $vapidPub,
            'Authorization: WebPush ' . $jwt,
            'TTL: 86400',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $result   = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 410 || $httpCode === 404) {
        return false;
    }

    return ($httpCode >= 200 && $httpCode < 300);
}

function sendPush(PDO $pdo, int $userId, string $title, string $body, string $url = '', string $tag = 'mm'): void {
    try {
        $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subs = $stmt->fetchAll();

        if (empty($subs)) return;

        $base = defined('BASE_URL') ? str_replace(' ', '%20', BASE_URL) : '/';
        $resolvedUrl = $base . 'dashboard/index.php';
        if (!empty($url)) {
            if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                $resolvedUrl = $url;
            } elseif (strpos($url, '/PROJECT%20M/') === 0) {
                $resolvedUrl = str_replace('/PROJECT%20M/', $base, $url);
            } elseif (strpos($url, '/') === 0) {
                $resolvedUrl = $url;
            } else {
                $resolvedUrl = $base . $url;
            }
        }

        $payload = ['title' => $title, 'body' => $body, 'url' => $resolvedUrl, 'tag' => $tag];

        $expired = [];
        foreach ($subs as $sub) {
            $ok = sendPushToSubscription($sub['endpoint'], $sub['p256dh'], $sub['auth'], $payload);
            if ($ok === false) {
                $expired[] = $sub['endpoint'];
            }
        }

        if (!empty($expired)) {
            $del = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?");
            foreach ($expired as $ep) {
                $del->execute([$userId, $ep]);
            }
        }
    } catch (Exception $e) {
        error_log('sendPush error: ' . $e->getMessage());
    }
}

function getUserPushPref(PDO $pdo, int $userId, string $pref): bool {
    try {
        $stmt = $pdo->prepare("SELECT {$pref} FROM push_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (bool)$row[$pref] : true;
    } catch (Exception $e) {
        return true;
    }
}

function sendBudgetAlert(PDO $pdo, int $userId, string $section, float $percent, bool $exceeded = false): void {
    $pref = $exceeded ? 'budget_exceeded' : 'budget_alert';
    if (!getUserPushPref($pdo, $userId, $pref)) return;

    $currency = $_SESSION['currency'] ?? '₹';
    if ($exceeded) {
        $title = "🚨 Budget Exceeded!";
        $body  = "You've exceeded your budget in \"{$section}\". Consider reviewing your expenses.";
        $tag   = 'budget-exceeded-' . md5($section);
    } else {
        $p = round($percent);
        $title = "⚠️ Budget Alert — {$section}";
        $body  = "{$p}% of your budget for \"{$section}\" has been used this month.";
        $tag   = 'budget-alert-' . md5($section);
    }

    sendPush($pdo, $userId, $title, $body, 'Exp/index.php', $tag);
}

function sendGoalAchieved(PDO $pdo, int $userId, string $goalName, float $amount): void {
    if (!getUserPushPref($pdo, $userId, 'savings_goal')) return;

    $currency = $_SESSION['currency'] ?? '₹';
    $title = "🎉 Goal Achieved!";
    $body  = "Congratulations! You've reached your savings goal \"{$goalName}\" ({$currency}" . number_format($amount, 2) . ")!";

    sendPush($pdo, $userId, $title, $body, 'Sav/index.php', 'goal-achieved-' . md5($goalName));
}

function sendLoginAlert(PDO $pdo, int $userId, string $browser, string $ip): void {
    if (!getUserPushPref($pdo, $userId, 'login_alert')) return;

    $title = "🔐 New Login Detected";
    $body  = "Your account was accessed from {$browser} ({$ip}). If this wasn't you, secure your account immediately.";

    sendPush($pdo, $userId, $title, $body, 'dashboard/index.php', 'login-alert');
}

function sendMonthlySummary(PDO $pdo, int $userId, float $totalSpent, float $totalSaved, string $month): void {
    if (!getUserPushPref($pdo, $userId, 'monthly_summary')) return;

    $currency = $_SESSION['currency'] ?? '₹';
    $title = "📊 Monthly Summary — {$month}";
    $body  = "Spent: {$currency}" . number_format($totalSpent, 2) . " | Saved: {$currency}" . number_format($totalSaved, 2);

    sendPush($pdo, $userId, $title, $body, 'dashboard/index.php', 'monthly-summary');
}

function checkAndTriggerBudgetAlert(PDO $pdo, int $userId, int $categoryId, float $addedAmount, string $entryDate): void {
    try {
        $month = substr($entryDate, 0, 7);
        if (strlen($month) !== 7) return;

        // 1. Get Category Name and Budget
        $stmt = $pdo->prepare("SELECT budget FROM category_monthly_budgets WHERE user_id = ? AND category_id = ? AND budget_month = ?");
        $stmt->execute([$userId, $categoryId, $month]);
        $budget = $stmt->fetchColumn();

        if ($budget === false || $budget === null) {
            $stmt = $pdo->prepare("SELECT category_name, budget FROM user_categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$categoryId, $userId]);
            $cat = $stmt->fetch();
            if (!$cat) return;
            $categoryName = $cat['category_name'];
            $budget = $cat['budget'];
        } else {
            $stmt = $pdo->prepare("SELECT category_name FROM user_categories WHERE id = ? AND user_id = ?");
            $stmt->execute([$categoryId, $userId]);
            $categoryName = $stmt->fetchColumn() ?: 'Category';
        }

        $budget = floatval($budget);
        if ($budget <= 0) return;

        // 2. Get new total spent
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE category_id = ? AND entry_date LIKE ?");
        $stmt->execute([$categoryId, $month . '-%']);
        $newTotal = floatval($stmt->fetchColumn());

        $prevTotal = $newTotal - $addedAmount;

        $prevPercent = ($prevTotal / $budget) * 100;
        $newPercent = ($newTotal / $budget) * 100;

        if ($prevPercent < 80 && $newPercent >= 80 && $newPercent < 100) {
            sendBudgetAlert($pdo, $userId, $categoryName, $newPercent, false);
        } elseif ($prevPercent < 100 && $newPercent >= 100) {
            sendBudgetAlert($pdo, $userId, $categoryName, $newPercent, true);
        }
    } catch (Exception $e) {
        error_log('checkAndTriggerBudgetAlert error: ' . $e->getMessage());
    }
}
