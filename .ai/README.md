# AI Knowledge Base

Reusable engineering knowledge for consistent, high-quality development.

## Structure

| Directory | Purpose |
|-----------|---------|
| `core/` | Engineering principles, architecture, workflow, standards |
| `agents/` | Specialized agent definitions and responsibilities |
| `skills/` | Technology-specific references (PHP, MySQL, etc.) |
| `templates/` | Reusable code templates and checklists |

## Usage

- Load only the files needed for the current task.
- Reference these documents instead of repeating knowledge in prompts.
- Never duplicate `.ai` information in `.antigravity/`.

## Loading Strategy

| Task | Required Files |
|------|---------------|
| PHP Bug | `core/workflow.md`, `core/debugging.md`, `skills/php.md`, `.antigravity/personal_metadata.md` |
| UI Task | `skills/bootstrap.md`, `skills/javascript.md`, `skills/html.md`, `.antigravity/personal_metadata.md` |
| Database | `skills/mysql.md`, `core/performance.md`, `.antigravity/personal_metadata.md` |
| Deployment | `skills/ubuntu.md`, `skills/apache.md`, `skills/cron.md`, `.antigravity/personal_metadata.md` |
| Security | `core/security.md`, `.antigravity/personal_metadata.md` |
| API | `skills/api.md`, `.antigravity/personal_metadata.md` |

## Future Projects

Copy this `.ai/` folder to every new project.
