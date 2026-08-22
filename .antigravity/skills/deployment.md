# Deployment Reference

## Pre-Deployment Checklist
1. Run all tests.
2. Run security checks.
3. Check for hardcoded secrets.
4. Verify environment config.
5. Clear cache.
6. Backup database.
7. Backup files.

## Steps
1. Pull latest code.
2. Install dependencies: `composer install --no-dev`.
3. Run migrations: `php artisan migrate` or `./migrate.sh`.
4. Clear cache.
5. Set permissions.
6. Reload PHP-FPM / web server.
7. Verify deployment.

## Rollback Plan
- Keep previous version backup.
- Keep previous database backup.
- Document rollback steps.
- Test rollback procedure.

## Environment Files
- `.env` is never committed.
- `.env.example` provides template.
- Different configs per environment (dev, staging, production).
- Use environment variables for secrets.
