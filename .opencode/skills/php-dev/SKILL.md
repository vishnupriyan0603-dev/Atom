---
name: php-dev
description: Use when working with PHP, CodeIgniter, or Laravel code. Covers PSR-12, framework conventions, query builder, validation, and error handling.
---

# PHP Development

## Standards
- PSR-12 coding style (see `.ai/core/coding-standard.md`)
- Type hints on all parameters and return types
- Follow `.antigravity/coding-standard.md` for project-specific conventions

## CodeIgniter (primary backend)
- Framework: CodeIgniter 4.7+, PHP 8.2+ (`backend/`)
- Query Builder for all DB operations
- Controllers in `backend/app/Controllers/`, services in `backend/app/Services/`
- Models extend `CodeIgniter\Model`, services are plain classes
- JWT auth via `auth` filter (`.antigravity/api.md`)
- Run server: `cd backend && php spark serve` (port 8080)
- See `.ai/skills/codeigniter.md` for full reference

## Laravel (secondary)
- Eloquent ORM with eager loading
- Form Requests for validation
- Service Providers for binding
- See `.ai/skills/laravel.md` for full reference

## Security
- Validate all input, escape all output
- Use prepared statements / query builder
- Never hardcode secrets
- See `.ai/core/security.md` for checklist
