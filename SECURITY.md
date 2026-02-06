# Security Guidelines

## Never Commit Sensitive Data

**DO NOT** commit these files:
- `.env` - Contains your Slack bot token
- `webhook.log` - May contain sensitive error information
- Any API keys or tokens

These are in `.gitignore` for protection.

## .env File Safety

1. **Always use `.env.example`** - Share this as a template
2. **Never hardcode tokens** in PHP files
3. **Use environment variables** for all secrets
4. **Restrict file permissions** - `.env` should be readable by PHP only

```bash
# On cPanel/Linux servers
chmod 600 .env
```

## Slack Token Security

1. **Use Bot Tokens** - Never use user tokens
2. **Scopes** - Only enable `chat:write` scope
3. **Rotate Regularly** - Change token every 90 days
4. **Revoke if Leaked** - Immediately regenerate if exposed

## Webhook Security

1. **Use HTTPS** - Always use secure connections
2. **Validate Requests** - Consider adding request signatures
3. **Rate Limiting** - Implement if needed
4. **Monitor Logs** - Check `webhook.log` regularly

## Deployment Checklist

- [ ] `.env` file created with real token (not committed)
- [ ] `.gitignore` prevents committing sensitive files
- [ ] File permissions set correctly (600 for .env)
- [ ] HTTPS enabled on webhook URL
- [ ] cURL and PHP JSON extensions verified
- [ ] Error logging configured

## Reporting Security Issues

Do NOT open public issues for security vulnerabilities.

Please email security concerns privately.
