# AI Prompt Optimization

## Loading Strategy
Always load only the files needed for the current task:

| Task Type | Files to Load |
|-----------|--------------|
| PHP Bug | `workflow.md`, `debugging.md`, `php.md`, `project.md`, `rules.md` |
| UI Task | `bootstrap.md`, `javascript.md`, `html.md`, `css.md`, `ui.md` |
| Database | `mysql.md`, `performance.md`, `database.md` |
| Deployment | `ubuntu.md`, `apache.md`, `cron.md`, `deployment.md` |
| API | `api.md`, `backend.md`, `api.md` (project) |
| Security | `security.md`, `coding-standard.md`, `rules.md` |

## Short Prompt Examples
- Fix attendance issue.
- Optimize dashboard query.
- Add salary export.
- Fix cron execution.
- Add employee API.
- Improve login security.
- Generate monthly report.
- Review before deployment.

## Rules for AI
- Reference `.ai/` for reusable knowledge.
- Reference `.antigravity/` for project-specific knowledge.
- Never repeat information already in these files.
- Follow workflow steps for every task.
- Apply security checklist on every change.
- Keep code style consistent with existing code.
