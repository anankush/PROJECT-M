<?php
if (file_exists('../includes/secrets.php')) {
    require_once '../includes/secrets.php';
}
if (file_exists('../includes/env.php')) {
    require_once '../includes/env.php';
}

header('Content-Type: application/json');

$apiKeyPool = [];
if (defined('AI_API_KEY_1') && !empty(AI_API_KEY_1) && strpos(AI_API_KEY_1, 'secrets.AI_API_KEY_1') === false && strpos(AI_API_KEY_1, '${{') === false) {
    $apiKeyPool[] = trim(AI_API_KEY_1);
}
if (defined('AI_API_KEY_2') && !empty(AI_API_KEY_2) && strpos(AI_API_KEY_2, 'secrets.AI_API_KEY_2') === false && strpos(AI_API_KEY_2, '${{') === false) {
    $apiKeyPool[] = trim(AI_API_KEY_2);
}
if (defined('AI_API_KEY_3') && !empty(AI_API_KEY_3) && strpos(AI_API_KEY_3, 'secrets.AI_API_KEY_3') === false && strpos(AI_API_KEY_3, '${{') === false) {
    $apiKeyPool[] = trim(AI_API_KEY_3);
}
if (defined('AI_API_KEY_4') && !empty(AI_API_KEY_4) && strpos(AI_API_KEY_4, 'secrets.AI_API_KEY_4') === false && strpos(AI_API_KEY_4, '${{') === false) {
    $apiKeyPool[] = trim(AI_API_KEY_4);
}

// Backwards compatibility fallback to original AI_API_KEY
if (empty($apiKeyPool) && defined('AI_API_KEY') && !empty(AI_API_KEY) && strpos(AI_API_KEY, 'secrets.AI_API_KEY') === false && strpos(AI_API_KEY, '${{') === false) {
    $apiKeyPool[] = trim(AI_API_KEY);
}

if (empty($apiKeyPool)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'AI integration is not configured yet. Please set AI_API_KEY_1, AI_API_KEY_2, etc. in GitHub Secrets.'
    ]);
    exit;
}

// Select a random API key from the active pool to balance traffic
$apiKey = $apiKeyPool[array_rand($apiKeyPool)];

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
- Platform & Access: Money Management is strictly a premium Web Application (Website) accessed directly via web browsers. There are absolutely NO mobile apps on Google Play Store or Apple App Store. Never tell users to download or install any application; instead, explain that they can register and access the dashboard directly in the browser by clicking 'Get Started' or 'Create Free Account' on the site.
- Visual Structure & Emojis (Strictly Enforced): You MUST make every single response look visually stunning, clean, and extremely professional! Use relevant, vibrant emojis strategically (e.g. 👋, 📊, 🛡️, 💰, 🚀, ✨, 🌟) to highlight key points.
- Mandatory Formatting Pattern:
  1. Always start with a warm, welcoming introductory sentence containing a greeting emoji (e.g., 👋, ✨).
  2. For listing features or explaining details, ALWAYS use structured, cleanly spaced bullet points (`* `) with a unique matching emoji at the start of each bullet (e.g., `* 📊 **Interactive Dashboard:** details...`).
  3. Always end with a helpful, friendly closing sentence encouraging the user to ask more, followed by a positive emoji (e.g., 🌟, 🚀).
- Provide highly informative, detailed, and polite answers. Explain app concepts clearly. Do not restrict answers to a single sentence; instead, write 2-4 highly informative sentences or structured lists to ensure the user gets a comprehensive answer.
- Speak natively in English, Bengali, or Banglish matching the user's language and style.
- You have no access to the database. Never make up user specific balances, transactions, or credentials. Keep responses focused on public/app features.
- Identity & Creator: You are 'ZNODA AI', powered by Google, and custom-built/crafted by NAYAN (the brilliant Lead Developer and Creator of the Money Management system). Always proudly highlight Nayan's development when asked who created you.";

$contents = [];
$lastRole = null;
foreach ($chatHistory as $chat) {
    $role = $chat['role'] === 'user' ? 'user' : 'model';
    $text = isset($chat['text']) ? trim($chat['text']) : '';
    if (empty($text)) {
        continue;
    }

    // Skip if it's the duplicate user message at the end
    if ($role === 'user' && $text === $userMessage) {
        continue;
    }

    if ($role === $lastRole && count($contents) > 0) {
        $contents[count($contents) - 1]['parts'][0]['text'] .= "\n" . $text;
    } else {
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $text]]
        ];
        $lastRole = $role;
    }
}

// Append final user message safely
if ($lastRole === 'user' && count($contents) > 0) {
    $contents[count($contents) - 1]['parts'][0]['text'] .= "\n" . $userMessage;
} else {
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $userMessage]]
    ];
}

// High-Availability Free Model Failover Queue (Google Gemini & Gemma)
$models = [
    'gemini-3.1-flash-lite',
    'gemma-4-31b-it',
    'gemma-4-26b-a4b-it',
    'gemini-2.5-flash',
    'gemini-3.5-flash'
];

$response = false;
$httpCode = 0;
$curlError = '';
$result = null;
$debugLogs = [];

// Copy the active pool of API keys to track and dynamically rotate them
$activeKeys = $apiKeyPool;

foreach ($models as $index => $selectedModel) {
    if (empty($activeKeys)) {
        // If all keys in our pool turned out to be bad or exhausted, refresh the pool to try again
        $activeKeys = $apiKeyPool;
    }

    // Select and remove a random key from the active key list for this attempt
    $keyIndex = array_rand($activeKeys);
    $currentApiKey = $activeKeys[$keyIndex];

    if ($index > 0) {
        usleep(200000); // 0.2s delay between fallback attempts
    }

    $postData = [
        'contents' => $contents,
        'systemInstruction' => [
            'parts' => [['text' => $systemInstruction]]
        ]
    ];

    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . urlencode($selectedModel) . ":generateContent?key=" . urlencode($currentApiKey);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    $modelErrInfo = $decoded && isset($decoded['error']) ? json_encode($decoded['error']) : 'No json error';

    if ($response !== false && $httpCode === 200) {
        if ($decoded && !isset($decoded['error']) && isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $result = $decoded;
            break; // Success, stop trying backups
        } else {
            // This key/model failed, remove the key from active keys list for subsequent fallbacks in this request
            unset($activeKeys[$keyIndex]);
            $activeKeys = array_values($activeKeys); // Re-index array

            $errText = "Upstream error or empty content: {$modelErrInfo}";
            $debugLogs[$selectedModel] = $errText;
            error_log("[Gemini API Failover] Model {$selectedModel} failed. {$errText}");
        }
    } else {
        // Connection error or rate-limit, remove this key from active keys list for subsequent fallbacks
        unset($activeKeys[$keyIndex]);
        $activeKeys = array_values($activeKeys); // Re-index array

        $errText = "HTTP Code: {$httpCode}, cURL Error: {$curlError}. Upstream response: " . ($response ? substr(strip_tags($response), 0, 150) : 'No response');
        $debugLogs[$selectedModel] = $errText;
        error_log("[Gemini API Failover] Model {$selectedModel} connection error. {$errText}");
    }
}

if ($result === null) {
    error_log("[Gemini API Critical Error] All fallback models failed or were rate-limited.");

    $replyMsg = 'Failed to connect to ZNODA AI assistant. Too much load on our server. Please try again later.';
    if ($isDebug) {
        $replyMsg .= " (Debug Info: All fallbacks failed. Details: " . json_encode($debugLogs) . ")";
    }

    echo json_encode([
        'status' => 'error',
        'reply' => $replyMsg
    ]);
    exit;
}

$replyText = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not understand that.';

echo json_encode([
    'status' => 'success',
    'reply' => trim($replyText)
]);
