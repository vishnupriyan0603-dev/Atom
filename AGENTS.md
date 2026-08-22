# opencode Agent Instructions

## Knowledge Base
- `.ai/` - Reusable engineering knowledge (principles, architecture, standards, security, etc.)
- `.antigravity/` - Project-specific knowledge (rules, UI, database, API, deployment, etc.)
- `.opencode/agents/` - Subagent definitions (backend, frontend, database, debugger, reviewer, web, architect, devops, tester)
- `.opencode/skills/` - Domain skills (php-dev, database, deployment, frontend)

## Workflow
Always follow this workflow for every task:
1. Analyze - Read all relevant files before making changes
2. Plan - Determine files to modify, identify side effects
3. Backup - Backup affected files
4. Implement - Make changes following project standards
5. Test - Verify the change works
6. Verify UI - Check visual consistency
7. Verify Database - Confirm queries/schema are correct
8. Verify API - Test endpoints
9. Review Performance - Check for N+1, missing indexes
10. Security Review - Validate input, escape output, check auth
11. Regression Test - Verify existing functionality
12. Final Review - Confirm nothing is broken

See `.ai/core/workflow.md` and `.antigravity/workflow.md` for detailed descriptions.

## Loading Strategy
- PHP tasks: `.ai/core/workflow.md`, `.ai/core/debugging.md`, `.ai/skills/php.md`, `.ai/skills/codeigniter.md`, `.antigravity/project.md`, `.antigravity/rules.md`, `.antigravity/personal_metadata.md`
- UI tasks: `.ai/skills/bootstrap.md`, `.ai/skills/javascript.md`, `.antigravity/ui.md`, `.antigravity/personal_metadata.md`
- Database tasks: `.ai/skills/mysql.md`, `.ai/core/performance.md`, `.antigravity/database.md`, `.antigravity/personal_metadata.md`
- Deployment: `.ai/skills/ubuntu.md`, `.ai/skills/apache.md`, `.ai/skills/cron.md`, `.antigravity/deployment.md`, `.antigravity/personal_metadata.md`
- Security: `.ai/core/security.md`, `.antigravity/security.md`, `.antigravity/personal_metadata.md`
- API: `.ai/skills/api.md`, `.antigravity/api.md`, `.antigravity/personal_metadata.md`

## Agent Selection
- Backend work: use `backend` agent
- Frontend/web UI: use `frontend` or `web` agent
- Database: use `database` agent
- Bug fixing: use `debugger` agent
- Code review: use `reviewer` agent
- Architecture: use `architect` agent
- Infrastructure: use `devops` agent
- Testing: use `tester` agent

## Project Rules
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
- Backend tests: `cd backend && vendor/bin/phpunit`
- Backend server: `cd backend && php spark serve` (port 8080)
