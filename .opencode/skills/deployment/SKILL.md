---
name: deployment
description: Use when deploying, configuring servers, setting up cron, or managing infrastructure. Covers Apache, Nginx, Ubuntu, Linux, and CI/CD.
---

# Deployment

## Pre-Deploy Checklist
1. Run all tests (`cd backend && vendor/bin/phpunit`)
2. Security scan
3. Check for hardcoded secrets
4. Verify environment config (`backend/.env`, config/env.php)
5. Clear cache (`backend/writable/cache`)
6. Backup database and files

## Infrastructure
- Ubuntu/Linux server management (`.ai/skills/ubuntu.md`, `.ai/skills/linux.md`)
- Apache virtual host config (`.ai/skills/apache.md`)
- Nginx server blocks (`.ai/skills/nginx.md`)
- Cron job scheduling (`.ai/skills/cron.md`)
- Docker containers (`.ai/skills/docker.md`)

## Project Deployment
- Backend: CodeIgniter 4 at `backend/`, document root `backend/public/`
- Database: MySQL `atom_assistant`
- See `.antigravity/deployment.md` for project-specific steps
- See `.ai/skills/deployment.md` for general process
- Rollback plan required for every deploy
