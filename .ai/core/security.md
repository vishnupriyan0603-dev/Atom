# Security Checklist

## Input Validation
- Validate all input: type, length, format, range.
- Whitelist allowed values where possible.
- Never trust user input, even from authenticated users.

## Output Escaping
- Escape HTML output: `htmlspecialchars()` or template engine auto-escaping.
- Escape JavaScript context: JSON encoding.
- Escape SQL: Use prepared statements.

## Authentication
- Use strong hashing: `password_hash()` (bcrypt).
- Implement proper session management.
- Enforce password complexity and rotation.
- Rate-limit login attempts.

## Authorization
- Check permissions on every protected action.
- Never rely on client-side checks.
- Use principle of least privilege.

## Common Vulnerabilities
- **SQL Injection**: Always use prepared statements / query builder.
- **XSS**: Escape output, use CSP headers.
- **CSRF**: Use CSRF tokens for all state-changing requests.
- **File Upload**: Validate MIME type, limit size, store outside webroot.
- **IDOR**: Verify user owns the resource they're accessing.
- **Sensitive Data**: Never log passwords, tokens, secrets.

## Headers
- `Content-Security-Policy`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Strict-Transport-Security`
- `Referrer-Policy: same-origin`
