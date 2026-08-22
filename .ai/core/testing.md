# Testing Strategy

## Test Pyramid
- **Unit Tests**: Test individual classes/functions in isolation. Fast, numerous.
- **Integration Tests**: Test interaction between components (DB, API).
- **E2E Tests**: Test full user workflows. Slow, few.

## What to Test
- Business logic and domain rules.
- Input validation and error handling.
- Edge cases: empty values, boundaries, special characters.
- Authentication and authorization.
- API contract (status codes, response format).

## What NOT to Test
- Framework internals.
- Trivial getters/setters.
- Third-party library behavior.

## Best Practices
- One assertion per test where possible.
- Descriptive test method names: `test_[method]_[scenario]_[expected]`
- Use factories or fixtures for test data.
- Clean up test data after each test.
- Tests must be deterministic.

## PHP Testing
- PHPUnit for unit/integration tests.
- Mock external dependencies.
- Use in-memory SQLite for fast DB tests.

## JS Testing
- Jest or Mocha for unit tests.
- Cypress for E2E testing.
