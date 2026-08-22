# Web Frontend Skill

## Overview
The Atom web frontend is located at `frontend/web/` and consists of:
- `index.html` - Main chat page with side-by-side layout (chat + manual panel)
- `css/style.css` - Atom dark theme styling
- `js/chat.js` - Backend API connectivity with JWT auth
- `admin.html` - Admin/management page

## Architecture
- **Sidebar** (left): Brand, model selector, chat list, navigation
- **Chat panel** (center): Message area, typing indicator, input
- **Manual panel** (right): Step-by-step quick start guide (collapsible)

## Backend API
All chat operations use the REST API at `http://localhost:8080/api`:
- `POST /api/auth/register` - Auto-register on first load
- `POST /api/auth/login` - Login with stored credentials
- `GET /api/auth/me` - Verify token validity
- `POST /api/ai/complete` - Quick AI completion (no auth)
- `POST /api/ai/models` - List AI models (no auth)
- `GET/POST /api/chats` - List/create chat sessions
- `GET /api/chats/{id}` - Get chat with messages
- `GET/POST /api/chats/{id}/messages` - List/add messages
- `POST /api/chat/{id}/send` - Send message, attempt real AI call
- `POST /api/chat/{id}/preview` - Send message and get response
- `GET/POST/PUT/DELETE /api/prompts` - Manage prompts
- `GET/POST /api/notes` - Manage notes
- `GET/POST /api/models` - Manage AI models
- `GET/POST /api/knowledge` - Manage knowledge items
- `GET/POST /api/files` - Manage files
- `GET/POST /api/plugins` - Manage plugins
- `GET/POST /api/settings` - App settings
- `GET/POST /api/sync` - Sync pull/push
- Versioned endpoints also available under `/api/v1/`

## Key Behaviors
- Auto-registers anonymous user on first load
- Persists auth token and active chat ID in localStorage
- Model selector updates provider/model for new chats
- Manual panel can be toggled open/closed

## Build & Run
No build step needed. Open `frontend/web/index.html` directly in a browser.
Backend must be running: `cd backend && php spark serve`
