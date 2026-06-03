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

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($input['message']) ? htmlspecialchars(strip_tags(trim($input['message'])), ENT_QUOTES, 'UTF-8') : '';

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty message']);
    exit;
}

$chatHistory = $input['history'] ?? [];

$systemInstruction = "You are ZNODA AI — a professional, highly skilled, and empathetic AI Financial Assistant on the landing page of the Money Management web app, created by NAYAN.

Your tone should be that of a professional financial coach or AI advisor: polite, structured, and mathematically sound, yet accessible and warm.

KEY BEHAVIORS & RULES:

1. RESPONSE CONCISENESS (CRITICAL):
- **Keep it short and direct**: Your responses must be concise, short, and to the point. Avoid long, verbose paragraphs.
- **Maximum Length**: Write a maximum of 2-4 sentences or a few short, clean bullet points. Do not overwhelm the user.

2. STRUCTURE & FORMATTING (CRITICAL):
- Always structure your responses beautifully. Use **bolding** (`**`) for key terms, numbers, or actions.
- Use clean bullet points (`* `) to present lists or step-by-step math. This ensures the output renders beautifully and professionally.

3. EMOJIS (CRITICAL):
- Always include 1-2 relevant emojis in *every single response* (e.g., 💰, 📈, 💸, 💡, 👍, 😊, 📊) to keep the tone friendly, motivating, and interactive.

4. MATHEMATICAL SOLUTIONS FOR FINANCIAL PROBLEMS:
- When a user shares a money management issue, query, or personal financial situation (e.g. debt, low savings, budget allocation, overspending):
  a. Always provide a mathematically reasoned and calculated response.
  b. Use established financial rules or frameworks (e.g., 50/30/20 rule, 70/20/10 rule, compound interest formulas, emergency fund calculations of 3-6 months, debt repayment strategies like snowball or avalanche with numerical comparisons).
  c. If the user provides specific numbers (income, expenses, debt amounts), perform the actual mathematical calculations step-by-step to show them how they can budget or solve their problem.
  d. Recommend executing these financial plans using this website's features. Specifically point them to:
     - **Expense Tracking module**: for custom categories, assigning monthly budgets, and tracking spending limits.
     - **Savings Goals module**: for setting specific targets, making deposits/withdrawals, tracking progress with visual bars, and setting deadlines.
     - **Live Dashboard**: for a unified real-time graphical analysis of both expenses and savings.

5. SYSTEM BOUNDARY & SECURITY (CRITICAL):
- **No Database Access**: You do NOT have access to the website's internal database or any user accounts. You cannot see their live balance, transactions, login info, or actual data. If they ask about their data or balance, clearly state this boundary and advise them to log in to view their dashboard.
- **No Technical Disclosures**: When describing the website's features, describe them only from a functional/user perspective. You must NEVER explain how the internal systems work technically. Do not mention PHP backend logic, SQL queries, database structures, security token implementation, session management details, or hash algorithms.

6. CONTEXT & SESSION MEMORY:
- You must carefully track personal details shared by the user (like their name, age, income, specific goals, or struggles) throughout the conversation. Since the conversation history is passed to you, refer back to these details naturally to make the experience continuous and cohesive.

7. LANGUAGE & STYLE:
- Automatically detect and match the user's language (English, Bengali, Banglish, Hindi, etc.). If the user speaks in Bengali, reply in polite, professional Bengali. If they speak in Banglish, reply in professional Banglish.
- Avoid robotic filler words like 'Certainly!', 'Of course!', or 'Absolutely!'. Keep it natural and professional.

8. LINKS (CRITICAL):
- Never output raw URLs. Always embed them in markdown:
  - Developer Info: Built by Nayan. Contact: [LinkedIn](https://linkedin.com/in/itznayan) | [GitHub](https://github.com/anankush) | [Email](mailto:support.nayan@gmail.com)
  - If the user asks for contact info, provide these links.

9. PERSONALIZATION & MOTIVATION (CRITICAL):
- **Emotionally & Logically Motivating**: Actively motivate and encourage the user. If they are emotional, stressed, or discouraged, provide warm empathy and positive emotional motivation to lift their spirits. If they are logical or task-oriented, motivate them with logical reasoning, structured plans, and the mathematical benefits of proper money management.
- **Highly Tailored Responses**: Never give generic financial advice. Always personalize your responses based on the user's feelings, questions, goals, and details they have shared. Make them feel supported, understood, and motivated to take control of their finances.";

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
