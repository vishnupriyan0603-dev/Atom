# jQuery Reference

## Core Syntax
- `$(selector)` - Select elements.
- `$(selector).on('event', handler)` - Event binding.
- `$(selector).addClass('class')` / `.removeClass()`.
- `$(selector).val()` - Get/set input value.
- `$(selector).text()` / `.html()` - Get/set content.
- `$(selector).data('key')` - Access data attributes.

## AJAX
```javascript
$.ajax({
  url: '/api/endpoint',
  method: 'POST',
  data: { id: 1 },
  dataType: 'json',
  success: function(response) { ... },
  error: function(xhr, status, error) { ... }
});
```

## Best Practices
- Cache selectors: `var $el = $('#element');`.
- Use event delegation for dynamic elements: `$(parent).on('click', '.child', fn)`.
- Chain methods where readable.
- Prefer `$.on()` over deprecated `$.live()`, `$.bind()`, `$.click()`.
- Use `$(document).ready(function() { ... })` shorthand.
