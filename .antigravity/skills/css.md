# CSS Reference

## Best Practices
- Use classes over IDs for styling.
- Use CSS custom properties for theme values: `--primary: #007bff;`.
- Mobile-first media queries: `min-width` over `max-width`.
- Avoid `!important` except for utility overrides.
- Group related properties.
- Use shorthand properties: `margin: 10px 5px;`.

## Flexbox
```css
display: flex;
justify-content: center; /* main axis */
align-items: center;     /* cross axis */
flex-wrap: wrap;
gap: 10px;
```

## Grid
```css
display: grid;
grid-template-columns: repeat(3, 1fr);
gap: 20px;
```

## Responsive
- Use `rem`/`em` for font sizes.
- Use `%`, `vw`, `vh` for layout.
- Use `clamp()` for fluid typography.
- Media queries at content breakpoints, not device sizes.
