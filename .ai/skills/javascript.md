# JavaScript Reference

## ES6+ Features
- `let`/`const` over `var`.
- Arrow functions: `const fn = () => {}`.
- Template literals: `` `${name}` ``.
- Destructuring: `const { name, age } = obj`.
- Spread: `const newArr = [...arr, item]`.
- Async/await: `const data = await fetch(url)`.
- Optional chaining: `user?.address?.city`.
- Nullish coalescing: `const x = value ?? defaultValue`.

## DOM Manipulation
- `document.querySelector()`, `querySelectorAll()`.
- `element.addEventListener()` for events.
- `element.classList.add()/remove()/toggle()`.
- `fetch()` for AJAX (not XMLHttpRequest).

## Best Practices
- Use `===` always (not `==`).
- Avoid global variables; use modules or IIFE.
- Debounce/throttle frequent events (scroll, resize, input).
- Cache DOM queries when reused.
- Use `try/catch` with async operations.
