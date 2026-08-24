# API Reference

- **Base URL**: `http://localhost:8080/api` (v1: `/api/v1`)
- **Version**: 1.0.0
- **Auth method**: Bearer JWT token
- **Rate limit**: 60 requests/min

## Endpoints

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/auth/register` | Auto-register anonymous / web user | No |
| POST | `/api/auth/login` | Authenticate user & receive JWT token | No |
| GET | `/api/auth/me` | Fetch authenticated user info | Yes |
| GET | `/api/chats` | List user chat sessions | Yes |
| POST | `/api/chats` | Create new chat session | Yes |
| GET | `/api/chats/{id}` | Get chat messages & history | Yes |
| POST | `/api/chat/{id}/send` | Send message & generate AI response | Yes |
| POST | `/api/chat/{id}/preview` | Send prompt preview for chat | Yes |
| GET | `/api/v1/knowledge` | List indexed PDF RAG documents | Yes |
| POST | `/api/v1/knowledge/upload` | Upload & chunk PDF/text files | Yes |
| GET | `/api/v1/knowledge/triples` | Query Subject-Predicate-Object triples | Yes |
| GET | `/api/v1/memory` | List personal stored memory items | Yes |
| GET | `/api/v1/system/status` | System health, telemetry, provider status | Yes |

## Response Format

See `.ai/templates/api-response.md`.

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Validation error |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not found |
| 422 | Unprocessable entity |
| 500 | Server error |
