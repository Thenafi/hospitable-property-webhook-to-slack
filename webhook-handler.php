<?php
/**
 * Hospitable Webhook Handler
 * Lightweight, memory-optimized webhook processor
 * Sends property notifications to Slack
 */

// Minimal error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set memory limit to minimum
ini_set('memory_limit', '32M');

// Load environment variables from .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Process webhook and send to Slack
 */
function handleHospitableWebhook() {
    // Get raw JSON input
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'No data']);
        return;
    }
    
    // Parse JSON
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    // Get action type
    $action = $data['body']['action'] ?? null;
    
    if (empty($action)) {
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
        return;
    }
    
    // Route to appropriate handler
    $slackPayload = null;
    
    if ($action === 'property.created') {
        $propertyId = $data['body']['data']['id'] ?? 'unknown';
        $propertyName = $data['body']['data']['name'] ?? 'unnamed';
        $propertyUrl = "https://my.hospitable.com/properties/property/{$propertyId}/overview";
        
        $slackPayload = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "<!subteam^S07MKA258KW> A property just got added to Hospitable. Please ensure it was supposed to be added."
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "For example, some clients onboard properties that we should not be managing; in those cases, it needs to be deleted. But if you were the one who onboarded the property and you are aware of it, please continue with your life, or either mute it, or ask the client if we need to have it in Hospitable, or mention <@U03S5GQ2CDP>. <@U081VRC48Q2> Please disable houfy."
                    ]
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Property:* <{$propertyUrl}|{$propertyName}>\n*ID:* {$propertyId}"
                    ]
                ]
            ]
        ];
    } elseif ($action === 'property.deleted') {
        $propertyId = $data['body']['data']['id'] ?? 'unknown';
        
        $slackPayload = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "🗑️ Property Deleted\nID: {$propertyId}"
                    ]
                ]
            ]
        ];
    } elseif ($action === 'property.merged') {
        $previousId = $data['body']['data']['previous_id'] ?? 'unknown';
        $newId = $data['body']['data']['new_id'] ?? 'unknown';
        
        $slackPayload = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "🔗 Property Merged\nOld ID: {$previousId}\nNew ID: {$newId}"
                    ]
                ]
            ]
        ];
    } elseif ($action === 'property.changed') {
        $propertyId = $data['body']['data']['id'] ?? 'unknown';
        $propertyName = $data['body']['data']['name'] ?? 'unnamed';
        
        $slackPayload = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "📝 Property Updated\nID: {$propertyId}\nName: {$propertyName}"
                    ]
                ]
            ]
        ];
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
        return;
    }
    
    if (empty($slackPayload)) {
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
        return;
    }
    
    // Send to Slack Web API
    $slackToken = getenv('SLACK_BOT_TOKEN');
    
    if (empty($slackToken)) {
        http_response_code(500);
        logError('Missing SLACK_BOT_TOKEN');
        echo json_encode(['error' => 'Configuration error']);
        return;
    }
    
    // Route to appropriate channel based on action
    $channel = 'C03RV3V94AY'; // Default channel for property.created
    if ($action === 'property.changed') {
        $channel = 'C08R24HBK7F';
    }
    
    $success = sendToSlack($slackToken, $slackPayload, $channel);
    
    // Clear variables to free memory
    unset($input, $data, $slackPayload);
    
    if ($success) {
        http_response_code(200);
        echo json_encode(['status' => 'sent']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'failed']);
    }
}

/**
 * Send payload to Slack using Web API
 */
function sendToSlack($slackToken, $payload, $channel = 'C03RV3V94AY') {
    $ch = curl_init('https://slack.com/api/chat.postMessage');
    
    // Prepare Slack API request
    $postData = http_build_query([
        'token' => $slackToken,
        'channel' => $channel,
        'blocks' => json_encode($payload['blocks'])
    ]);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * Minimal error logging (single line only)
 */
function logError($message) {
    $logFile = __DIR__ . '/webhook.log';
    
    // Only log if file doesn't exist or is small
    if (!file_exists($logFile) || filesize($logFile) < 10240) {
        error_log(date('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL, 3, $logFile);
    }
}

// Execute
handleHospitableWebhook();
