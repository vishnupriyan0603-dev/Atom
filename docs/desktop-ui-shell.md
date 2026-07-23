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
- References the synced Atom 3D model asset.

## Not Included

- AI model integration.
- Backend API integration.
- Desktop automation.
- 3D model rendering.
- Package installation.

