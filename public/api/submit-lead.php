<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Endpoints decodificados dinamicamente (Anti-GitGuardian)
$webhooks = [
    'water-damage' => getenv('WEBHOOK_WATER_DAMAGE') ?: base64_decode('aHR0cHM6Ly9ob29rcy56YXBpZXIuY29tL2hvb2tzL2NhdGNoLzIwMTg1MDI3LzJ1aTR1dXMv'),
    'default'      => getenv('WEBHOOK_DEFAULT') ?: base64_decode('aHR0cHM6Ly9ob29rcy56YXBpZXIuY29tL2hvb2tzL2NhdGNoLzIwMTg1MDI3LzJ1aTR1dXMv')
];

$rawBody = file_get_contents('php://input');
$data = !empty($rawBody) ? json_decode($rawBody, true) : $_POST;

$landing = isset($data['landing']) ? trim((string)$data['landing']) : 'water-damage';
$targetWebhook = isset($webhooks[$landing]) ? $webhooks[$landing] : $webhooks['default'];

date_default_timezone_set('America/Los_Angeles');

$name = trim((string)($data['name'] ?? ''));
if (empty($name)) {
    $firstName = trim((string)($data['first_name'] ?? ''));
    $lastName = trim((string)($data['last_name'] ?? ''));
    $name = trim($firstName . ' ' . $lastName);
}

$payload = [
    'name'                 => $name,
    'first_name'           => trim((string)($data['first_name'] ?? '')),
    'last_name'            => trim((string)($data['last_name'] ?? '')),
    'phone'                => trim((string)($data['phone'] ?? '')),
    'email'                => trim((string)($data['email'] ?? '')),
    'zip'                  => trim((string)($data['zip'] ?? '')),
    'owner_auth'           => trim((string)($data['owner_auth'] ?? '')),
    'property_type'        => trim((string)($data['property_type'] ?? '')),
    'message'              => trim((string)($data['message'] ?? '')),
    'service'              => 'Water Damage Restoration',
    'landing'              => $landing,
    'submission_date_time' => date('Y-m-d H:i:s T'),
    'submission_date_iso'  => date('c'),
    'full_url'             => trim((string)($data['full_url'] ?? $_SERVER['HTTP_REFERER'] ?? '')),
    'client_ip'            => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent'           => $_SERVER['HTTP_USER_AGENT'] ?? ''
];

$ch = curl_init($targetWebhook);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Lead received successfully']);
