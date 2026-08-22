#!/bin/bash
# Atom - Local AI Server Setup for WSL Ubuntu
# Installs Ollama and runs a local AI model

echo "========================================"
echo "  Atom - Local AI Server (WSL Ubuntu)"
echo "========================================"
echo

# Check if Ollama is installed
if ! command -v ollama &> /dev/null; then
    echo "[1/4] Installing Ollama..."
    curl -fsSL https://ollama.ai/install.sh | sh
else
    echo "[1/4] Ollama already installed"
fi

# Start Ollama server
echo "[2/4] Starting Ollama server..."
ollama serve &
sleep 3

# Pull a model
echo "[3/4] Pulling llama3.1 model..."
ollama pull llama3.1

echo "[4/4] Model ready!"
echo
echo "========================================"
echo "  AI Server running on localhost:11434"
echo "========================================"
echo
echo "  The Atom app can now use your local AI."
echo "  Model: llama3.1 (Ollama)"
echo "  Endpoint: http://localhost:11434/api/chat"
echo
echo "  To add this model to Atom:"
echo "  1. Open atom.bat"
echo "  2. Select [5] Add Custom Model"
echo "  3. Name: llama3.1"
echo "  4. Provider: Ollama"
echo "  5. Endpoint: http://localhost:11434/api/chat"
echo
echo "  Press Ctrl+C to stop the server."
echo

# Keep server running
wait
