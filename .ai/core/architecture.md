# Clean Architecture Guidelines

## Layer Separation
- **Presentation**: Controllers, views, UI logic.
- **Application**: Use cases, DTOs, application services.
- **Domain**: Entities, value objects, domain services, repository interfaces.
- **Infrastructure**: Database, external APIs, file system, mail.

## Dependency Rule
- Dependencies point inward: Infrastructure → Application → Domain.
- Domain layer has zero external dependencies.
- Use dependency injection to invert control.

## Communication
- Presentation calls Application via use cases.
- Application orchestrates Domain logic.
- Infrastructure implements interfaces defined in Domain/Application.
- Layers communicate through interfaces, never concrete classes.

## Module Organization
- Group by business feature, not technical layer.
- Each module contains its own controllers, models, services.
- Shared kernel for truly cross-cutting concerns.
