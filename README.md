# Atom AI Assistant

Atom is a modular personal AI assistant project scaffolded for future desktop automation, web automation, coding assistance, document understanding, and study support.

This repository currently contains architecture only. No application features, business logic, UI, or dependencies have been added.

## Architecture Principles

- Clean Architecture
- SOLID principles
- Modular boundaries
- Reusable components
- Testable design
- Production-ready organization

## Top-Level Structure

- `frontend/` - User-facing clients for desktop, web, mobile, and shared frontend modules.
- `backend/` - Server-side application layers, APIs, domain coordination, persistence access, and utilities.
- `ai/` - AI-specific assets, agent definitions, prompts, workflows, memory, and model integrations.
- `automation/` - Future automation adapters for desktop, browser, files, email, and scheduling.
- `knowledge/` - Document, note, book, and vector knowledge storage boundaries.
- `database/` - Database schema, migration, seeding, and backup organization.
- `storage/` - Runtime-generated files such as uploads, cache, logs, and temporary artifacts.
- `config/` - Environment-aware configuration templates and settings.
- `docs/` - Project documentation and architecture notes.
- `scripts/` - Operational and developer helper scripts.
- `tests/` - Test suites organized by layer and module.
- `tools/` - Internal developer tooling and maintenance utilities.
- `security/` - Security policies, checklists, and future hardening resources.
- `deployment/` - Deployment, infrastructure, and release configuration.
- `assets/` - Shared static assets.
- `.github/` - GitHub workflows, issue templates, and repository automation.
- `.vscode/` - Editor workspace recommendations and settings.

