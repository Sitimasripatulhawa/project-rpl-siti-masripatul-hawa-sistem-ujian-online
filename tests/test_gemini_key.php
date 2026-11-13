<?php
require_once __DIR__ . '/../app/config/config.php';

echo "Testing Gemini API Key with config.php values...\n";
echo "Model: " . GEMINI_MODEL . "\n";
echo "API URL: " . GEMINI_API_URL . "\n";
echo "API Key (first 8 chars): " . substr(GEMINI_API_KEY, 0, 8) . "...\n\n";

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent';

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                ['text' => 'Halo! Apakah kamu aktif?']
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . GEMINI_API_KEY
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n";
if ($curlError) {
    echo "Curl Error: {$curlError}\n";
}
echo "Response Body:\n";
echo $response . "\n";
