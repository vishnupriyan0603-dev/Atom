# API Reference

- **Base URL**:
- **Version**:
- **Auth method**:
- **Rate limit**:

## Endpoints

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| GET | /api/resource | List resources | Yes |
| POST | /api/resource | Create resource | Yes |
| PUT | /api/resource/{id} | Update resource | Yes |
| DELETE | /api/resource/{id} | Delete resource | Yes |

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
