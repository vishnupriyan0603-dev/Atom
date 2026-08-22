# API Response Template

```json
{
  "success": true,
  "data": {},
  "message": "Operation successful",
  "errors": null
}
```

Error:
```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "field": ["The field is required."]
  }
}
```

Pagination:
```json
{
  "success": true,
  "data": [],
  "message": null,
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```
