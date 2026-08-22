# API Reference

## RESTful Design
- Use nouns for resources: `/api/users`, `/api/orders`.
- HTTP methods: GET (read), POST (create), PUT/PATCH (update), DELETE.
- Use plural nouns for collections.
- Nest related resources: `/api/users/{id}/orders`.
- Version API: `/api/v1/`.

## Response Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "errors": null
}
```

## Error Responses
- `400` - Bad Request (validation errors)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity
- `500` - Internal Server Error

## Best Practices
- Always validate input before processing.
- Always authenticate and authorize requests.
- Use pagination for list endpoints.
- Return consistent response structure.
- Rate-limit API endpoints.
- Log API requests for debugging.
- Use proper HTTP status codes.
