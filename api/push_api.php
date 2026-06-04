<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

check_session_timeout();

$uid    = (int) $_SESSION['user_id'];
$action = sanitize_input($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
}

switch ($action) {

    case 'subscribe':
        $input = json_decode(file_get_contents('php://input'), true);

        $endpoint = trim($input['endpoint'] ?? '');
        $p256dh   = trim($input['keys']['p256dh'] ?? '');
        $auth     = trim($input['keys']['auth']   ?? '');

        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid subscription data']);
            exit;
        }

        if (!filter_var($endpoint, FILTER_VALIDATE_URL) || !str_starts_with($endpoint, 'https://')) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid endpoint']);
            exit;
        }

        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, user_agent)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), user_agent = VALUES(user_agent)
            ");
            $stmt->execute([$uid, $endpoint, $p256dh, $auth, $ua]);

            $pdo->prepare("
                INSERT IGNORE INTO push_preferences (user_id) VALUES (?)
            ")->execute([$uid]);

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save subscription']);
        }
        break;

    case 'unsubscribe':
        $input    = json_decode(file_get_contents('php://input'), true);
        $endpoint = trim($input['endpoint'] ?? '');

        if (empty($endpoint)) {
            echo json_encode(['status' => 'error', 'message' => 'Endpoint required']);
            exit;
        }

        try {
            $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?")
                ->execute([$uid, $endpoint]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove subscription']);
        }
        break;

    case 'get_prefs':
        try {
            $stmt = $pdo->prepare("SELECT * FROM push_preferences WHERE user_id = ?");
            $stmt->execute([$uid]);
            $prefs = $stmt->fetch();

            if (!$prefs) {
                $prefs = [
                    'budget_alert'    => 1,
                    'budget_exceeded' => 1,
                    'savings_goal'    => 1,
                    'monthly_summary' => 1,
                    'login_alert'     => 1,
                ];
            }

            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?");
            $stmt2->execute([$uid]);
            $hasSubscription = (int)$stmt2->fetchColumn() > 0;

            echo json_encode([
                'status'          => 'success',
                'prefs'           => $prefs,
                'has_subscription' => $hasSubscription,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch preferences']);
        }
        break;

    case 'update_prefs':
        $input = json_decode(file_get_contents('php://input'), true);

        $allowed = ['budget_alert', 'budget_exceeded', 'savings_goal', 'monthly_summary', 'login_alert'];
        $prefs   = [];
        foreach ($allowed as $key) {
            $prefs[$key] = isset($input[$key]) ? (int)(bool)$input[$key] : 1;
        }

        try {
            $pdo->prepare("
                INSERT INTO push_preferences (user_id, budget_alert, budget_exceeded, savings_goal, monthly_summary, login_alert)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    budget_alert    = VALUES(budget_alert),
                    budget_exceeded = VALUES(budget_exceeded),
                    savings_goal    = VALUES(savings_goal),
                    monthly_summary = VALUES(monthly_summary),
                    login_alert     = VALUES(login_alert)
            ")->execute([
                $uid,
                $prefs['budget_alert'],
                $prefs['budget_exceeded'],
                $prefs['savings_goal'],
                $prefs['monthly_summary'],
                $prefs['login_alert'],
            ]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update preferences']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
}
