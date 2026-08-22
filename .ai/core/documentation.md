# Documentation Standards

## Code Documentation
- Document public APIs: params, return types, exceptions.
- Use PHPDoc or JSDoc for functions and methods.
- Keep comments explaining "why", not "what".
- Update documentation when code changes.

## Markdown Style
- Use ATX headings (`#`) with space after `#`.
- Use fenced code blocks with language identifier.
- Lists: use `-` for unordered, `1.` for ordered.
- Tables for structured data.
- Links with `[text](url)` format.

## File Structure
- `README.md` in every directory explaining purpose.
- `CHANGELOG.md` for project history.
- Keep files small and focused on one topic.
- Use consistent naming: `kebab-case` for files.

## Organization
- `.ai/`: Reusable, technology-agnostic knowledge.
- `.antigravity/`: Project-specific knowledge.
- Never duplicate information between the two.
- Cross-reference instead of copying.
