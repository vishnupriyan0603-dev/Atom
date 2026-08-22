# ATOM Agent Instructions

## Knowledge Base
- `.antigravity/` - Unified AI Engineering Knowledge Base (principles, rules, UI, database, API, deployment, security)
- `.antigravity/skills/` - Technology-specific skills (php, codeigniter, mysql, javascript, bootstrap, api, security)

## Workflow
Always follow this workflow for every task (see `docs/svgAtom_strict_rules.md`):
1. Analyze - Read all relevant files before making changes
2. Plan - Determine files to modify, identify side effects
3. Branch - Create task branch (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `test/*`)
4. Backup - Backup affected files
5. Implement - Make targeted changes following project standards
6. Test - Verify PHPUnit, C# build, and feature acceptance criteria
7. Verify UI & DB - Check visual consistency and query correctness
8. Security Review - Validate input, escape output, check auth
9. Final Review - Confirm backwards compatibility

See `.antigravity/workflow.md` and `docs/svgAtom_strict_rules.md` for detailed descriptions.

## Loading Strategy
- PHP tasks: `.antigravity/workflow.md`, `.antigravity/skills/php.md`, `.antigravity/skills/codeigniter.md`, `.antigravity/project.md`, `.antigravity/rules.md`, `.antigravity/personal_metadata.md`
- UI tasks: `.antigravity/skills/bootstrap.md`, `.antigravity/skills/javascript.md`, `.antigravity/ui.md`, `.antigravity/personal_metadata.md`
- Database tasks: `.antigravity/skills/mysql.md`, `.antigravity/database.md`, `.antigravity/personal_metadata.md`
- Deployment: `.antigravity/skills/ubuntu.md`, `.antigravity/skills/apache.md`, `.antigravity/skills/cron.md`, `.antigravity/deployment.md`, `.antigravity/personal_metadata.md`
- Security: `.antigravity/security.md`, `.antigravity/personal_metadata.md`
- API: `.antigravity/skills/api.md`, `.antigravity/api.md`, `.antigravity/personal_metadata.md`

## Project Rules
- Follow `docs/svgAtom_strict_rules.md` strictly (Understand first. Change second. Verify third. Merge last.)
- Never change UI unless requested
- Never rename database columns or tables
- Never change existing APIs or remove functionality
- Always preserve backward compatibility
- Modify only requested modules
- Keep code modular, avoid duplicate logic
- Reuse existing functions whenever possible
- Validate every input, escape every output
- Use prepared statements / query builder
- Handle errors gracefully
- Never hardcode secrets

## Test Commands
- Backend tests: `cd backend && php -d extension=intl -d extension=sqlite3 -d extension=pdo_sqlite vendor/bin/phpunit --testdox`
- Desktop solution build: `dotnet build PersonalAIAssistant.sln`
