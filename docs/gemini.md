# Atom AI - Gemini Integration

This document covers the configuration and connection parameters for the Gemini AI Provider.

## Config & Settings

All credentials and API parameters are loaded from the environment `.env` file:
- `LLM_PROVIDER=gemini`
- `LLM_MODEL=gemini-1.5-flash`
- `LLM_API_KEY=your-api-key`
- `LLM_API_URL=https://generativelanguage.googleapis.com/v1beta`

## Connectivity Check

The application runs a lightweight model query during boot to test provider health. If offline, the interface falls back to offline/local mode automatically.
- Check Status: `/provider status`
- Test Connection: `/provider test`
