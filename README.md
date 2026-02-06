# Hospitable Webhook Handler

A lightweight, memory-optimized PHP webhook handler that sends Hospitable property notifications to Slack.

## Features

- **Low Memory Footprint** - 32MB memory limit
- **Fast Processing** - Minimal dependencies
- **Multiple Event Types** - Handles property.created, property.changed, property.deleted, and property.merged
- **Slack Integration** - Posts directly to Slack via Web API
- **Smart Logging** - Logs errors only, capped at 10KB

## Supported Webhooks

- `property.created` → Posts to C03RV3V94AY
- `property.changed` → Posts to C08R24HBK7F
- `property.deleted` → Posts to C03RV3V94AY
- `property.merged` → Posts to C03RV3V94AY

## Setup

### 1. Environment Variables

Copy `.env.example` to `.env` and add your Slack bot token:

```bash
cp .env.example .env
```

Edit `.env` and add your token:

```
SLACK_BOT_TOKEN=xoxb-your-actual-token-here
```

### 2. Get Slack Bot Token

1. Go to https://api.slack.com/apps
2. Select or create an app
3. Go to **OAuth & Permissions**
4. Copy the **Bot User OAuth Token** (starts with `xoxb-`)
5. Add it to your `.env` file

### 3. Upload to cPanel

1. Rename `webhook-handler.php` to `index.php`
2. Upload `index.php` and `.env` to your webhook directory
3. Webhook URL: `https://your-domain.com/webhook-path/`
   - Replace `your-domain.com` and `webhook-path` with your actual domain and path

## Usage

Hospitable will send POST requests to your webhook URL with JSON payloads. The handler automatically:

- Parses the webhook data
- Routes to the appropriate Slack channel
- Formats the message
- Sends to Slack

## Testing

Use the included Node.js test script:

```bash
node test-webhook.js
```

This tests all four webhook types and displays responses.

## Security

See [SECURITY.md](SECURITY.md) for security guidelines.

## Error Logging

Errors are logged to `webhook.log` (max 10KB to prevent storage bloat).

## Requirements

- PHP 7.4+
- cURL extension enabled
- Slack Bot Token (with chat:write scope)

## License

MIT - See [LICENSE](LICENSE)
