# Desktop UI Shell

## Summary

The first Atom desktop app shell has been added under `frontend/desktop/`.

## Decision

The app uses a Windows HTA shell so it can open in a desktop window without installing Electron, Python, or other dependencies.

## Files

- `frontend/desktop/Atom.hta` - desktop window UI shell.
- `frontend/desktop/run-atom-desktop.bat` - launcher for the desktop window.
- `frontend/desktop/README.md` - run instructions and scope.

## Current Scope

- Shows the Atom desktop assistant shell.
- Provides a placeholder assistant console.
- Shows a custom Atom bot avatar built into the desktop UI.

## Not Included

- AI model integration.
- Backend API integration.
- Desktop automation.
- External 3D model rendering.
- Package installation.

## Bot Visual Note

The previous downloaded 3D model files were removed from the active project and replaced with a lightweight built-in Atom bot visual. A backup record is stored under `backups/2026-07-23-remove-3d-model/`.
