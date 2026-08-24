# UI Rules

- **Framework**: HTML5, Vanilla CSS3 (Custom Atom Dark Glassmorphism Design System)
- **Theme**: Atom Dark / Deep Space Cyberpunk (`#070a12`, `#0b0f19`)
- **Colors**: Primary Cyan (`#00f2fe`), Accent Purple (`#7f00ff`, `#a855f7`), Accent Green (`#00e676`), Text (`#f8fafc`), Muted (`#94a3b8`)
- **Typography**: Google Fonts (Inter, Outfit, Fira Code)
- **Spacing**: 8px grid system (gap 12px / 16px / 20px / 28px)
- **Responsive breakpoints**: Desktop (1200px+), Tablet (768px - 1199px), Mobile (<768px)
- **CSS preprocessor**: Pure Vanilla CSS (`frontend/web/css/combined.css`)

## Components Used

- [x] FontAwesome 6.5.1
- [x] Custom Glass Cards (`.glass-card`)
- [x] Chat Input Box (`.chat-input-box`) with Paperclip Attachment Button (`.btn-attach`)
- [x] Attachment Preview Badge (`.attachment-badge`)
- [x] Animated AI Typing Indicator (`.typing-indicator-msg`)
- [x] Interactive SVG Knowledge Graph Visualizer (`#graphSvg`)
- [x] RAG Document Ingestion Dropzone (`#pdfDropzone`)
- [x] Telemetry Metrics Cards & Quota Monitors

## Layout

- **Header**: App Header (`.app-header`) with Brand Avatar, status badges, model selector, user manual link
- **Sidebar**: App Sidebar (`.app-sidebar`) with tab navigation items and system stats widget
- **Main Area**: Main Viewport (`.app-main`) hosting tab panels (`view-portal`, `view-chat`, `view-rag`, `view-graph`, `view-learning`, `view-telemetry`)
