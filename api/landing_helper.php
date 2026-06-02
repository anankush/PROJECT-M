<?php
if (file_exists('../includes/secrets.php')) {
    require_once '../includes/secrets.php';
}
if (file_exists('../includes/env.php')) {
    require_once '../includes/env.php';
}

header('Content-Type: application/json');

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
if (empty($apiKey)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'AI integration is not configured yet. Please set GEMINI_API_KEY in GitHub Secrets.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? htmlspecialchars(strip_tags(trim($input['message'])), ENT_QUOTES, 'UTF-8') : '';
if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty message']);
    exit;
}

$chatHistory = $input['history'] ?? [];

$systemInstruction = "You are the public welcoming assistant for PROJECT M (Money Management System).
PROJECT M features:
- Elegant Expense Tracking: custom categories, target budgets, data backup portability.
- Savings Goals Module: progress bars, ledger deposit/withdrawal tracking, milestones, deadlines.
- Real-Time Interactive Dashboard: combined monthly cash flows, expense breakdown donuts (Chart.js), health score tracker.
- Enterprise Security Shield: prepared statements (PDO), Bcrypt hashing, CSRF protection, session timeouts, and real-time disposable burner email blocker validation.

Rules:
1. Provide extremely friendly, helpful, and polite responses about the app.
2. Keep responses brief (under 3 sentences).
3. Speak in English, Bengali, or Banglish matching the user's queries.
4. You have no database access. Never mention user specific accounts or make up user stats.";

$contents = [];
foreach ($chatHistory as $chat) {
    $contents[] = [
        'role' => $chat['role'] === 'user' ? 'user' : 'model',
        'parts' => [['text' => $chat['text']]]
    ];
}
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $userMessage]]
];

$postData = [
    'contents' => $contents,
    'systemInstruction' => [
        'parts' => [['text' => $systemInstruction]]
    ]
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    error_log("[Gemini Public API Error] HTTP Code: {$httpCode}, cURL Error: {$curlError}");
    $apiRespSnippet = $response ? substr(strip_tags($response), 0, 150) : '';
    echo json_encode([
        'status' => 'error',
        'reply' => "Failed to connect to AI server. HTTP Code: {$httpCode}, cURL Error: {$curlError}. API Response: {$apiRespSnippet}"
    ]);
    exit;
}

$result = json_decode($response, true);
$replyText = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not understand that.';

echo json_encode([
    'status' => 'success',
    'reply' => trim($replyText)
]);
