<?php
/**
 * Hospitable → Slack Webhook Handler
 * Lightweight + memory efficient
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('memory_limit', '32M');


// --------------------------------------------------
// Load .env
// --------------------------------------------------
$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}


// --------------------------------------------------
// Main handler
// --------------------------------------------------
function handleHospitableWebhook()
{
    $input = file_get_contents('php://input');

    if (!$input) {
        respond(400, ['error' => 'No data']);
        return;
    }

    $json = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        respond(400, ['error' => 'Invalid JSON']);
        return;
    }

    /**
     * Support BOTH formats:
     * 1) { body: { action, data } }
     * 2) { action, data }
     */
    $payload = $json['body'] ?? $json;

    $action = $payload['action'] ?? null;
    $data   = $payload['data'] ?? [];

    if (!$action) {
        respond(200, ['status' => 'ignored']);
        return;
    }

    $blocks = buildSlackBlocks($action, $data);

    if (!$blocks) {
        respond(200, ['status' => 'ignored']);
        return;
    }

    $token = getenv('SLACK_BOT_TOKEN');

    if (!$token) {
        logError('Missing SLACK_BOT_TOKEN');
        respond(500, ['error' => 'Configuration error']);
        return;
    }

    $channel = getChannelForAction($action);

    $success = sendToSlack($token, $channel, $blocks);

    unset($input, $json, $payload, $blocks);

    respond($success ? 200 : 500, [
        'status' => $success ? 'sent' : 'failed'
    ]);
}


// --------------------------------------------------
// Slack message builder
// --------------------------------------------------
function buildSlackBlocks($action, $data)
{
    $id   = $data['id']   ?? 'unknown';
    $name = $data['name'] ?? 'unnamed';

    switch ($action) {

        case 'property.created':
            $url = "https://my.hospitable.com/properties/property/{$id}/overview";

            return [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' =>
                            "<!subteam^S07MKA258KW> A property was added to Hospitable.\n" .
                            "Please verify it should be managed."
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' =>
                            "*Property:* <{$url}|{$name}>\n" .
                            "*ID:* {$id}"
                    ]
                ]
            ];


        case 'property.deleted':
            return [[
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "🗑️ *Property Deleted*\nID: {$id}"
                ]
            ]];


        case 'property.merged':
            $old = $data['previous_id'] ?? 'unknown';
            $new = $data['new_id'] ?? 'unknown';

            return [[
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "🔗 *Property Merged*\nOld: {$old}\nNew: {$new}"
                ]
            ]];


        case 'property.changed':
            return [[
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "📝 *Property Updated*\n{$name}\nID: {$id}"
                ]
            ]];


        default:
            return null;
    }
}


// --------------------------------------------------
// Choose Slack channel
// --------------------------------------------------
function getChannelForAction($action)
{
    switch ($action) {
        case 'property.changed':
            return 'C08R24HBK7F';

        default:
            return 'C03RV3V94AY';
    }
}


// --------------------------------------------------
// Send to Slack (modern API)
// --------------------------------------------------
function sendToSlack($token, $channel, $blocks)
{
    $ch = curl_init('https://slack.com/api/chat.postMessage');

    $payload = json_encode([
        'channel' => $channel,
        'blocks'  => $blocks
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $httpCode === 200;
}


// --------------------------------------------------
// Helpers
// --------------------------------------------------
function respond($code, $data)
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function logError($message)
{
    $file = __DIR__ . '/webhook.log';

    if (!file_exists($file) || filesize($file) < 10240) {
        error_log(date('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL, 3, $file);
    }
}


// --------------------------------------------------
// Run
// --------------------------------------------------
handleHospitableWebhook();
