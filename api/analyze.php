<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Credentials: true");

// Force zero error display to keep JSON responses pristine
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../vendor/autoload.php') && !getenv('VERCEL')) {
    require __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

// -------------------------------------------------------------------------
// FREE WORKAROUND: STATELESS CRYPTOGRAPHIC RATE LIMITER
// -------------------------------------------------------------------------
$secretKey = getenv('LIMITER_SECRET') ?: 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6'; 
$cookieName = 'aura_rate_token';

$requestCount = 1;
$startTime = time();
$isThrottled = false;

if (isset($_COOKIE[$cookieName])) {
    $rawCookieData = $_COOKIE[$cookieName];
    $cookieParts = explode(':', $rawCookieData, 2);
    
    if (count($cookieParts) === 2) {
        $iv = hex2bin($cookieParts[0]);
        $encryptedData = base64_decode($cookieParts[1]);
        
        $decryptedRaw = openssl_decrypt($encryptedData, 'aes-256-cbc', $secretKey, 0, $iv);
        $data = json_decode($decryptedRaw, true);

        if ($data && isset($data['start_time']) && isset($data['count'])) {
            $startTime = $data['start_time'];
            $requestCount = $data['count'] + 1;
            $timeElapsed = time() - $startTime;

            if ($timeElapsed > 60) {
                $startTime = time();
                $requestCount = 1;
            } else if ($requestCount > 5) {
                $isThrottled = true;
            }
        }
    }
}

$newDataPayload = json_encode(['start_time' => $startTime, 'count' => $requestCount]);
$ivLength = openssl_cipher_iv_length('aes-256-cbc');
$newIv = openssl_random_pseudo_bytes($ivLength);
$encryptedPayload = openssl_encrypt($newDataPayload, 'aes-256-cbc', $secretKey, 0, $newIv);

$finalCookieValue = bin2hex($newIv) . ':' . base64_encode($encryptedPayload);

setcookie($cookieName, $finalCookieValue, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if ($isThrottled) {
    http_response_code(429);
    echo json_encode([
        'analysis' => '⚠️ CRYPTO-QUOTA LIMIT ENGAGED.',
        'refactored_code' => "// ERROR 429: Too Many Requests.\n// Your browser signature has logged more than 5 requests this minute.\n// Rate limiting enforced purely via stateless AES-256 client tokens (No DB)."
    ]);
    exit;
}

// -------------------------------------------------------------------------
// CORE AI RETRIEVAL ENGINE
// -------------------------------------------------------------------------
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    echo json_encode(['analysis' => 'System configuration error.', 'refactored_code' => '// ERROR: GEMINI_API_KEY is missing.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (empty($code)) {
    echo json_encode(['analysis' => 'No input code detected.', 'refactored_code' => '// ERROR: Please enter some code first.']);
    exit;
}

// Token-Optimized Prompt: Clean engineering constraints with zero filler words
$prompt = "Role: Senior PHP Architect. Task: Analyze the provided PHP code. Identify exactly 1 core vulnerability and rewrite securely using modern PHP 8.2+. Output Format: STRICT JSON object only. No markdown formatting outside the JSON structure. Keys required: 'analysis' (string summary) and 'refactored_code' (string block). Code to process:\n\n" . $code;

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

$textOutput = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $textOutput);

if (json_decode($textOutput) === null) {
    echo json_encode(['analysis' => 'AI output structure was invalid.', 'refactored_code' => $textOutput]);
    exit;
}

echo $textOutput;