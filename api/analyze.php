<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

if (file_exists(__DIR__ . '/../vendor/autoload.php') && !getenv('VERCEL')) {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['error' => 'API Key missing.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (empty($code)) {
    echo json_encode(['error' => 'No code provided.']);
    exit;
}

$prompt = "You are a Senior PHP Architect. Analyze the following PHP code. Identify 1 security vulnerability and rewrite the code using modern PHP 8.4+ features. Output strictly a JSON object with two keys: 'analysis' (string) and 'refactored_code' (string). Code:\n\n" . $code;

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
$data = ['contents' => [['parts' => [['text' => $prompt]]]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$geminiData = json_decode($response, true);
$textOutput = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
$textOutput = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $textOutput);

echo $textOutput;