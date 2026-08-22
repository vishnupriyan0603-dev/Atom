# PHP Reference

## Version
- Target PHP 8.1+ unless project specifies otherwise.
- Use strict types: `declare(strict_types=1);`

## Key Features
- **Type system**: typed properties, union types, mixed types.
- **Nullsafe operator**: `$user?->address?->city`
- **Match expression**: `match($x) { 1 => 'one', default => 'other' }`
- **Named arguments**: `func(name: $value)`
- **Attributes**: `#[AttributeName]`
- **Constructor promotion**: `public function __construct(public string $name) {}`

## Best Practices
- Use type hints everywhere.
- Return type declarations required on all methods.
- Use dependency injection, not `new` in controllers.
- Prefer early returns over nested conditions.
- Use enums for fixed value sets.
- Use readonly classes/properties for immutable objects.

## Error Handling
- Use try/catch with specific exception types.
- Log exceptions with context.
- Never suppress errors with `@`.
- Custom exceptions extend `\Exception` or `\RuntimeException`.
