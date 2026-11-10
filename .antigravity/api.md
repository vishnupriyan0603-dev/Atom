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
| POST | `/api/v1/chat/{id}/preview` | Versioned chat preview used by Mobile; same approval-aware response | Yes |
| POST | `/api/v1/chat/{id}/send` | Versioned chat send used by clients | Yes |
| GET | `/api/v1/chats` | Versioned authenticated chat list | Yes |
| POST | `/api/v1/chats` | Versioned authenticated chat creation | Yes |
| GET | `/api/v1/approvals?status=pending` | List the signed-in user's pending actions, including chat tool proposals | Yes |
| POST | `/api/v1/approvals/{id}/approve` | Execute the exact pending chat action once, then resume the originating turn; may return HTTP 202 if another action awaits approval | Yes |
| POST | `/api/v1/approvals/{id}/reject` | Reject a pending action and record the chat update | Yes |
| POST | `/api/v1/brain/tool/execute` | Create a pending per-action approval; never runs the tool in this request | Yes |
| GET | `/api/v1/approvals?status=pending` | List the authenticated user's pending approvals | Yes |
| POST | `/api/v1/approvals/{id}/approve` | Approve and, for a Brain tool action, execute the stored action once | Yes |
| POST | `/api/v1/approvals/{id}/reject` | Reject an approval owned by the authenticated user | Yes |
| GET | `/api/v1/knowledge` | List indexed PDF RAG documents | Yes |
| POST | `/api/v1/knowledge/upload` | Upload & chunk PDF/text files | Yes |
| GET | `/api/v1/knowledge/triples` | Query Subject-Predicate-Object triples | Yes |
| GET | `/api/v1/memory` | List personal stored memory items | Yes |
| GET | `/api/v1/system/status` | System health, telemetry, provider status | Yes |

## Response Format

Chat send/preview keeps the existing response envelope. Ordinary replies return HTTP 200. When a tool is proposed, it returns HTTP 202 with `data.pending_approval` containing `approval_id`, `status`, and an `action` explanation (`action`, `target`, `purpose`, `access`, `external_data_sharing`, `consequences`, `parameters`). No tool runs until the owner approves it. Approval executes the stored parameters once and returns `data.chat`; a second tool proposal returns another pending approval and pauses again. Approval and rejection are owner-bound, expire, and cannot be replayed.

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
