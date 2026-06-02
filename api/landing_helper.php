<?php
if (file_exists('../includes/secrets.php')) {
    require_once '../includes/secrets.php';
}
if (file_exists('../includes/env.php')) {
    require_once '../includes/env.php';
}

header('Content-Type: application/json');

$apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
if (empty($apiKey)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'AI integration is not configured yet. Please set AI_API_KEY in GitHub Secrets.'
    ]);
    exit;
}

$isDebug = isset($_GET['debug']) && $_GET['debug'] === 'nayan';

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? htmlspecialchars(strip_tags(trim($input['message'])), ENT_QUOTES, 'UTF-8') : '';

if (empty($userMessage)) {
    if ($isDebug) {
        $userMessage = 'Hello'; // Bypasses empty check for secure debug testing
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }
}

$chatHistory = $input['history'] ?? [];

$systemInstruction = "You are ZNODA AI, the premium public welcoming assistant and expert guide for the Money Management system (Personal Finance Management System).
Your goal is to showcase the premium qualities of the Money Management app and explain its functions in a highly detailed, professional, and engaging manner.

Detailed App Modules & Features to Explain:
1. Interactive Real-Time Dashboard:
   - Dynamic charts powered by Chart.js displaying monthly cash flows (income vs expenses) and expense category breakdowns (donut charts).
   - Dynamic Financial Health Score indicator that dynamically monitors and calculates user financial wellness based on income-to-expense ratios.
   - Quick widgets showing total income, total expenses, and net balance instantly.

2. Premium Expense Tracking:
   - Seamless logging of expenses with custom category labels, remarks, and dates.
   - Easy filtering, sorting, and pagination of transactional ledgers.
   - Smart monthly budgets where users set limit alerts to prevent overspending.
   - Data backup portability: export financial logs anytime.

3. Advanced Savings Goals Ledger:
   - Dedicated goals tracking with dynamic visual progress bars, remaining target values, and custom milestones.
   - Interactive transaction ledger for goals, allowing users to log deposits or withdrawals from goals with full history.
   - Smart deadline alert indicators highlighting active, completed, or approaching target dates.

4. Enterprise-Grade Security Shield:
   - Prepared statements (PDO) for absolute SQL injection immunity.
   - Secure Bcrypt password hashing and robust CSRF token defenses.
   - Automatic security session timeouts (15 minutes) protecting inactive sessions.
   - Real-time Disposable/Burner Email Blocker (block list validation) during signup to filter fake registrations.

Tone and Interaction Rules:
- Provide highly informative, detailed, and polite answers. Explain app concepts clearly.
- Keep responses well-structured and engaging (use bullet points or emojis where appropriate). Do not restrict answers to a single sentence; instead, write 2-4 highly informative sentences or structured lists to ensure the user gets a comprehensive answer.
- Speak natively in English, Bengali, or Banglish matching the user's language and style.
- You have no access to the database. Never make up user specific balances, transactions, or credentials. Keep responses focused on public/app features.
- Identity & Creator: You are 'ZNODA AI', powered by Google, and custom-built/crafted by NAYAN (the brilliant Lead Developer and Creator of the Money Management system). Always proudly highlight Nayan's development when asked who created you.";

$messages = [];
$messages[] = [
    'role' => 'system',
    'content' => $systemInstruction
];

foreach ($chatHistory as $chat) {
    $messages[] = [
        'role' => $chat['role'] === 'user' ? 'user' : 'assistant',
        'content' => $chat['text']
    ];
}

$messages[] = [
    'role' => 'user',
    'content' => $userMessage
];

// High-Availability Free Model Failover Queue
$models = [
    'openrouter/free',
    'meta-llama/llama-3.1-8b-instruct:free',
    'meta-llama/llama-3.3-70b-instruct:free',
    'meta-llama/llama-3.2-3b-instruct:free'
];

$response = false;
$httpCode = 0;
$curlError = '';
$result = null;
$debugLogs = [];

foreach ($models as $index => $selectedModel) {
    if ($index > 0) {
        usleep(600000); // 0.6s delay between attempts to bypass 1 request-per-second limit
    }

    $postData = [
        'model' => $selectedModel,
        'messages' => $messages
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://openrouter.ai/api/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'HTTP-Referer: http://moneymgmt.is-best.net',
        'X-Title: Money Management'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    $modelErrInfo = $decoded && isset($decoded['error']) ? json_encode($decoded['error']) : 'No json error';

    if ($response !== false && $httpCode === 200) {
        if ($decoded && !isset($decoded['error'])) {
            $result = $decoded;
            break; // Success, stop trying backups
        } else {
            $errText = "Upstream error: {$modelErrInfo}";
            $debugLogs[$selectedModel] = $errText;
            error_log("[OpenRouter Failover] Model {$selectedModel} failed. {$errText}");
        }
    } else {
        $errText = "HTTP Code: {$httpCode}, cURL Error: {$curlError}. Upstream response: " . ($response ? substr(strip_tags($response), 0, 100) : 'No response');
        $debugLogs[$selectedModel] = $errText;
        error_log("[OpenRouter Failover] Model {$selectedModel} connection error. {$errText}");
    }
}

if ($result === null) {
    error_log("[OpenRouter Critical Error] All fallback models failed or were rate-limited.");
    
    $replyMsg = 'Failed to connect to AI assistant. Please try again later.';
    if ($isDebug) {
        $replyMsg .= " (Debug Info: All fallbacks failed. Details: " . json_encode($debugLogs) . ")";
    }
    
    echo json_encode([
        'status' => 'error',
        'reply' => $replyMsg
    ]);
    exit;
}

$replyText = $result['choices'][0]['message']['content'] ?? 'Sorry, I could not understand that.';

echo json_encode([
    'status' => 'success',
    'reply' => trim($replyText)
]);
