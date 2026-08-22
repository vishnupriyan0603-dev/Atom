# Software Engineering Principles

## SOLID
- **S**ingle Responsibility: Each class/method has one reason to change.
- **O**pen/Closed: Open for extension, closed for modification.
- **L**iskov Substitution: Derived classes must be substitutable for base.
- **I**nterface Segregation: Small, focused interfaces.
- **D**ependency Inversion: Depend on abstractions, not concretions.

## DRY
- Every piece of knowledge must have a single, unambiguous representation.
- Extract reusable code into functions, classes, or services.
- Avoid copy-paste. When similar code appears, abstract it.

## KISS
- Simplicity over complexity.
- The most straightforward solution is usually the best.
- Avoid over-engineering. Do not add patterns or abstractions until needed.

## YAGNI
- You aren't gonna need it.
- Never add functionality until it is actually required.
- Speculative generality adds maintenance cost without value.

## Additional Principles
- **Law of Demeter**: Talk only to immediate friends.
- **Composition over Inheritance**: Prefer composing behavior over class hierarchies.
- **Fail Fast**: Validate early, fail with clear messages.
- **Principle of Least Astonishment**: Code should surprise no one.
