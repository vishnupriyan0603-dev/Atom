# ATOM AI Assistant &mdash; 3D Cyber Desktop Frontend Shell

A state-of-the-art, standalone desktop client for the ATOM AI Assistant platform featuring interactive 3D WebGL models, real-time audio spectrum visualization, and glassmorphism cyber HUD design.

---

## 🚀 Quick Start

Launch the desktop assistant by double-clicking [`run-atom-desktop.bat`](file:///E:/xampp/htdocs/my%20work/Atom/frontend/desktop/run-atom-desktop.bat) or running from terminal:

```cmd
frontend\desktop\run-atom-desktop.bat
```

---

## 🖥️ Supported Execution Modes

The desktop launcher automatically selects the best available engine on your Windows machine:
1. **Chromium App Mode (Primary / Recommended)** &mdash; Launches Edge or Chrome in dedicated, standalone window mode (`--app=...`) with modern web standards, hardware acceleration, and glassmorphic UI.
2. **Microsoft HTML Application (`mshta.exe`)** &mdash; Zero-dependency legacy fallback supported on all Windows versions.
3. **Default Browser** &mdash; Direct browser launch fallback.

---

## 🎨 Top 3D Models & Visual FX (`atom3d.js`)

The desktop client features an interactive **Three.js WebGL 3D Graphics Engine** with real-time cursor tracking, orbit controls, and 3 selectable modes:

| 3D Mode | Visual Description | Interactive Behaviors |
| :--- | :--- | :--- |
| **🤖 Cyber Bot** | High-fidelity metallic robot avatar with bronze trim and shoulder pauldrons | Head and glowing cyber eyes track the mouse cursor in real-time; floating levitation ring; pulsing green reactor core when talking. |
| **🧠 Neural Cortex** | Procedural dual-hemisphere 3D brain point cloud (1,800+ particles) | Gyri/sulci harmonic folds, synaptic glowing core, smooth dual-color gradient rotation. |
| **⚛️ Quantum Core** | Triple gyroscopic nested torus energy rings | Multiaxial rotation, central plasma reactor sphere, orbiting quantum electron particles. |

### Interactive 3D Controls
- **Drag & Rotate:** Click and drag inside the 3D viewport to inspect the model in full 360 degrees.
- **Double Click:** Reset camera and rotation to default orientation.
- **Wireframe Mode:** Toggle wireframe view with the `Wire` button.

---

## 🌊 Audio Spectrum Visualizer & Ambient FX
- **Live Audio Waveform Canvas:** 16-band dynamic audio spectrum analyzer that pulses and dances in response to AI thinking, typing, and speaking events.
- **Ambient Particle Grid:** Interactive background particle mesh that reacts subtly to cursor proximity.
- **Luminous Glassmorphism HUD:** Frosted backdrop filters (`backdrop-filter: blur(16px)`), cyberpunk neon accents, and smooth view transitions.

---

## 🧭 Functional Panels & Tabs

| Panel | Description | Key Capabilities |
| :--- | :--- | :--- |
| **Assistant** | Real-time chat console | Direct LLM completions, prompt presets, typing indicator, clear history. |
| **Automation** | Diagnostic & Operations Hub | System Health Check, Self-Improvement trigger, Model registry checks, Buffer flushes. |
| **Knowledge** | Search & Memory Inspector | Direct semantic search across indexing records and project architecture notes. |
| **Settings** | Configuration Management | Custom API URL configuration, Active Model switcher, Provider selector. |

---

## 🔌 Backend Integration

- **Default Endpoint:** `http://localhost:8080/api`
- **Supported Providers:** Ollama, Groq, Google Gemini, OpenAI, Anthropic Claude
- **Offline Fallback Engine:** Features an intelligent local response generator when the backend REST API server is offline or starting up.
