<?php

declare(strict_types=1);

require __DIR__ . '/../src/Bootstrap.php';

use Infrastructure\Database\Connection;
use Infrastructure\Repositories\ApiClientRepository;
use Infrastructure\Repositories\SmsdRepository;
use Infrastructure\Repositories\SmsBatchRepository;
use Infrastructure\Repositories\SmsMessageRepository;
use Domain\Sms\Ucs2Encoder;
use Domain\Sms\SmsMessage;
use Application\SendSmsService;

/**
 * =========================================
 * BASIC HTTP SETUP
 * =========================================
 */
header('Content-Type: application/json');

/**
 * =========================================
 * METHOD CHECK
 * =========================================
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

/**
 * =========================================
 * AUTHORIZATION (API KEY)
 * =========================================
 */
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing API key']);
    exit;
}

$apiKey = $matches[1];

/**
 * =========================================
 * INPUT JSON
 * =========================================
 */
$payload = json_decode(file_get_contents('php://input'), true);

if (
    !is_array($payload) ||
    empty($payload['to']) ||
    empty($payload['message'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$phone   = trim($payload['to']);
$message = trim($payload['message']);

/**
 * =========================================
 * CONFIG + DB
 * =========================================
 */
$config = require __DIR__ . '/../config/app.php';

$pdo = Connection::get($config['db']);

/**
 * =========================================
 * API CLIENT VALIDATION
 * =========================================
 */
$apiRepo = new ApiClientRepository($pdo);
$client  = $apiRepo->findActiveByApiKey($apiKey);

if ($client === null) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

/**
 * =========================================
 * APPLICATION SERVICE
 * =========================================
 */
$service = new SendSmsService(
    new Ucs2Encoder(),
    new SmsdRepository($pdo),
    new SmsBatchRepository($pdo),
    new SmsMessageRepository($pdo),
    $config['sms']['creator_id']
);

/**
 * =========================================
 * SEND SMS
 * =========================================
 */
try {
    $sms = new SmsMessage($phone, $message);

    $messageId = $service->send(
        $sms,
        (int)$client['id']
    );

    echo json_encode([
        'status'     => 'queued',
        'message_id'=> $messageId
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal error',
        'detail'=> $e->getMessage()
    ]);
}
