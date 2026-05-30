<?php
// ============================================================
// PROJECT S: API Router
// Handles all AJAX requests for the savings module
// ============================================================

require_once __DIR__ . '/../includes/db.php';

$central_security = dirname(__DIR__, 3) . '/includes/security.php';
if (file_exists($central_security)) {
    require_once $central_security;
} else {
    require_once __DIR__ . '/../includes/config.php';
}

session_start_secure();
header('Content-Type: application/json');

// Auth guard
if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Verify CSRF for all mutating actions ──────────────────────
$mutating = ['add_bucket', 'update_bucket', 'delete_bucket', 'add_transaction'];
if (in_array($action, $mutating)) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    verify_csrf_token($token);
}

// ── check_session ─────────────────────────────────────────────
if ($action === 'check_session') {
    echo json_encode([
        'is_user'  => true,
        'email'    => $_SESSION['user_email'] ?? '',
        'currency' => $_SESSION['user_currency'] ?? '₹',
    ]);
    exit;
}

// ── get_buckets ───────────────────────────────────────────────
if ($action === 'get_buckets') {
    $stmt = $pdo->prepare("
        SELECT sb.id, sb.bucket_name, sb.target_amount, sb.deadline, sb.created_at,
               COALESCE(SUM(CASE WHEN sl.transaction_type='deposit'  THEN sl.amount ELSE 0 END), 0) AS total_deposited,
               COALESCE(SUM(CASE WHEN sl.transaction_type='withdraw' THEN sl.amount ELSE 0 END), 0) AS total_withdrawn
        FROM savings_buckets sb
        LEFT JOIN savings_logs sl ON sl.bucket_id = sb.id
        WHERE sb.user_id = ?
        GROUP BY sb.id
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $buckets = $stmt->fetchAll();
    foreach ($buckets as &$b) {
        $b['current_amount'] = (float)$b['total_deposited'] - (float)$b['total_withdrawn'];
        $b['target_amount']  = (float)$b['target_amount'];
        $b['progress']       = $b['target_amount'] > 0
            ? min(100, round(($b['current_amount'] / $b['target_amount']) * 100, 1))
            : 0;
    }
    echo json_encode(['status' => 'success', 'data' => $buckets]);
    exit;
}

// ── add_bucket ────────────────────────────────────────────────
if ($action === 'add_bucket') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name   = trim($body['bucket_name'] ?? '');
    $target = (float)($body['target_amount'] ?? 0);
    $deadline = $body['deadline'] ?? null;

    if (empty($name) || $target <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Name and a positive target amount are required.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO savings_buckets (user_id, bucket_name, target_amount, deadline) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $target, $deadline ?: null]);
    echo json_encode(['status' => 'success', 'bucket_id' => (int)$pdo->lastInsertId()]);
    exit;
}

// ── update_bucket ─────────────────────────────────────────────
if ($action === 'update_bucket') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($body['id'] ?? 0);
    $name   = trim($body['bucket_name'] ?? '');
    $target = (float)($body['target_amount'] ?? 0);
    $deadline = $body['deadline'] ?? null;

    if (!$id || empty($name) || $target <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE savings_buckets SET bucket_name = ?, target_amount = ?, deadline = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$name, $target, $deadline ?: null, $id, $user_id]);
    echo json_encode(['status' => 'success']);
    exit;
}

// ── delete_bucket ─────────────────────────────────────────────
if ($action === 'delete_bucket') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'Invalid bucket ID.']); exit; }
    $pdo->prepare("DELETE FROM savings_buckets WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    echo json_encode(['status' => 'success']);
    exit;
}

// ── get_transactions ──────────────────────────────────────────
if ($action === 'get_transactions') {
    $bucket_id = (int)($_GET['bucket_id'] ?? 0);
    if (!$bucket_id) { echo json_encode(['status' => 'error', 'message' => 'Bucket ID required.']); exit; }

    // Verify bucket belongs to user
    $check = $pdo->prepare("SELECT id FROM savings_buckets WHERE id = ? AND user_id = ?");
    $check->execute([$bucket_id, $user_id]);
    if (!$check->fetch()) { echo json_encode(['status' => 'error', 'message' => 'Access denied.']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM savings_logs WHERE bucket_id = ? ORDER BY transaction_date DESC, transaction_time DESC");
    $stmt->execute([$bucket_id]);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
    exit;
}

// ── add_transaction ───────────────────────────────────────────
if ($action === 'add_transaction') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $bucket_id = (int)($body['bucket_id'] ?? 0);
    $type      = $body['transaction_type'] ?? '';
    $amount    = (float)($body['amount'] ?? 0);
    $date      = $body['transaction_date'] ?? date('Y-m-d');
    $time      = $body['transaction_time'] ?? date('H:i:s');
    $desc      = trim($body['description'] ?? '');

    if (!$bucket_id || !in_array($type, ['deposit', 'withdraw']) || $amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transaction data.']);
        exit;
    }

    // Verify bucket belongs to user
    $check = $pdo->prepare("SELECT id FROM savings_buckets WHERE id = ? AND user_id = ?");
    $check->execute([$bucket_id, $user_id]);
    if (!$check->fetch()) { echo json_encode(['status' => 'error', 'message' => 'Access denied.']); exit; }

    // For withdrawals: check sufficient balance using transaction
    $pdo->beginTransaction();
    try {
        if ($type === 'withdraw') {
            $bal = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN transaction_type='deposit' THEN amount ELSE -amount END), 0) FROM savings_logs WHERE bucket_id = ?");
            $bal->execute([$bucket_id]);
            $balance = (float)$bal->fetchColumn();
            if ($amount > $balance) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Insufficient balance. Current balance: ' . number_format($balance, 2)]);
                exit;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO savings_logs (user_id, bucket_id, transaction_type, amount, transaction_date, transaction_time, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $bucket_id, $type, $amount, $date, $time, $desc]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'log_id' => (int)$pdo->lastInsertId()]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed. Please try again.']);
    }
    exit;
}

// ── get_summary ───────────────────────────────────────────────
if ($action === 'get_summary') {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT sb.id) AS bucket_count,
            COALESCE(SUM(CASE WHEN sl.transaction_type='deposit'  THEN sl.amount ELSE 0 END), 0) AS total_deposited,
            COALESCE(SUM(CASE WHEN sl.transaction_type='withdraw' THEN sl.amount ELSE 0 END), 0) AS total_withdrawn
        FROM savings_buckets sb
        LEFT JOIN savings_logs sl ON sl.bucket_id = sb.id
        WHERE sb.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    echo json_encode([
        'status'          => 'success',
        'bucket_count'    => (int)$row['bucket_count'],
        'total_deposited' => (float)$row['total_deposited'],
        'total_withdrawn' => (float)$row['total_withdrawn'],
        'net_savings'     => (float)$row['total_deposited'] - (float)$row['total_withdrawn'],
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
