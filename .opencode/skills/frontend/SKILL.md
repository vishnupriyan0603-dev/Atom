---
name: frontend
description: Use when building or modifying UI. Covers Bootstrap, HTML, CSS, JavaScript, jQuery, responsive design, and Atom UI conventions.
---

# Frontend Development

## UI Rules
- Do NOT change UI unless explicitly requested
- Do NOT modify layouts without permission
- Keep CSS unchanged unless requested
- See `.antigravity/ui.md` for project-specific rules

## Stack
- Bootstrap for layout and components (`.ai/skills/bootstrap.md`)
- JavaScript ES6+ for interactivity (`.ai/skills/javascript.md`)
- jQuery for AJAX and DOM manipulation (`.ai/skills/jquery.md`)
- HTML5 semantic elements (`.ai/skills/html.md`)
- CSS with utility-first approach (`.ai/skills/css.md`)

## Web Frontend (Atom chat)
- Chat UI at `frontend/web/index.html` — sidebar, chat panel, manual panel
- Atom dark theme via CSS variables in `frontend/web/css/style.css`
- API connectivity in `frontend/web/js/chat.js` (JWT auth, base `http://localhost:8080/api`)
- See `.opencode/skills/frontend/web.md` for full architecture

## Best Practices
- Handle loading, empty, and error states
- Responsive design (mobile-first)
- Cross-browser compatibility
- Debounce frequent events
