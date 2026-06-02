<?php
header('Content-Type: application/json');

$url = 'https://openrouter.ai/api/v1/models';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
$freeModels = [];

if (isset($result['data'])) {
    foreach ($result['data'] as $model) {
        // Filter for models where prompt and completion pricing are both 0 (free)
        if (
            (isset($model['pricing']['prompt']) && floatval($model['pricing']['prompt']) == 0)
            && (isset($model['pricing']['completion']) && floatval($model['pricing']['completion']) == 0)
        ) {
            $freeModels[] = [
                'id' => $model['id'],
                'name' => $model['name'],
                'context_length' => $model['context_length'] ?? 0
            ];
        }
    }
}

echo json_encode([
    'http_code' => $httpCode,
    'total_free_models' => count($freeModels),
    'free_models' => $freeModels
]);
