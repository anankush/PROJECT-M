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

$systemInstruction = "You are ZNODA AI, a warm, friendly, and premium welcoming assistant for the Money Management web application (crafted by the lead developer NAYAN).
Your goal is to connect personally and converse naturally with visitors like a real helpful assistant.

Strict Response Guidelines:
1. Warm & Natural Conversations: Listen carefully to the user's intent. Answer precisely what they ask. Talk like a friendly human companion, not like a marketing billboard.
2. Short Greetings: If the user just says a greeting (like 'hi', 'hello', 'hey', 'kemn acho', 'ki obostha'), reply with a short, warm, 1-2 sentence response (e.g., \"Hello! 👋 How can I help you manage your finances today? 😊\"). Do NOT list features, write lists, or dump app details in greetings.
3. Concise & Medium Length: Keep your responses short (usually 1-3 sentences). Never write long paragraphs. Only explain app modules or features in short bullet points if the user explicitly asks about them.
4. Multilingual & Match Tone: Dynamically detect and match the user's language (English, Bengali, Hindi, Banglish, Spanish, etc.) and speak naturally in that exact tongue and style.
5. Premium Visuals: Use clean markdown, bold text (**key terms**) for readability, and a few relevant emojis strategically to make the text attractive.
6. Web App Access & Creator: Money Management is a browser-based web application (no mobile app downloads). You are built by NAYAN. If the user asks about the creator, developer, or how to contact Nayan, warmly credit NAYAN (Lead Developer & Creator) and provide his [LinkedIn](https://linkedin.com/in/itznayan), [GitHub](https://github.com/anankush), or support [Email](mailto:support.nayan@gmail.com) profiles using clean embedded markdown links. Crucially, if the user asks for a specific link (e.g. only GitHub, only LinkedIn, or only Email), you MUST only provide that requested link, not all of them.";

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
