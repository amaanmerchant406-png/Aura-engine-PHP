<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

// Enable error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../vendor/autoload.php') && !getenv('VERCEL')) {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['analysis' => 'System configuration error.', 'refactored_code' => '// ERROR: GEMINI_API_KEY is completely missing from Vercel Environment Variables.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (empty($code)) {
    echo json_encode(['analysis' => 'No input code detected.', 'refactored_code' => '// ERROR: Please enter some code first.']);
    exit;
}

$prompt = "You are a Senior PHP Architect. Analyze the following PHP code. Identify 1 security vulnerability and rewrite the code using modern PHP features. Output strictly a JSON object with two keys: 'analysis' (string) and 'refactored_code' (string). Code:\n\n" . $code;

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
$data = ['contents' => [['parts' => [['text' => $prompt]]]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['analysis' => 'Network Connection Error.', 'refactored_code' => '// cURL Error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['analysis' => 'Google API rejected the request. HTTP Code: ' . $httpCode, 'refactored_code' => '// Google Response: ' . $response]);
    exit;
}

$geminiData = json_decode($response, true);
$textOutput = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($textOutput)) {
    echo json_encode(['analysis' => 'AI returned an empty response.', 'refactored_code' => '// Raw API Payload: ' . json_encode($geminiData)]);
    exit;
}

// Strip markdown code block wrappers if present
$textOutput = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $textOutput);

// Ensure it is valid JSON before sending it to the frontend
if (json_decode($textOutput) === null) {
    echo json_encode(['analysis' => 'AI output structure was invalid.', 'refactored_code' => $textOutput]);
    exit;
}

echo $textOutput;