#!/bin/bash
# Atom Backend - WSL Ubuntu Setup & Run
# Usage: bash start-wsl.sh

echo "========================================"
echo "  Atom Backend - WSL Ubuntu Server"
echo "========================================"
echo

# Find the Atom project on Windows filesystem
ATOM_DIR="/mnt/f/Atom"
if [ ! -d "$ATOM_DIR/backend" ]; then
    ATOM_DIR="/mnt/e/Atom"
fi
if [ ! -d "$ATOM_DIR/backend" ]; then
    echo "[ERROR] Cannot find Atom project at /mnt/f/Atom or /mnt/e/Atom"
    echo "Edit this script and set ATOM_DIR to your project path."
    exit 1
fi

echo "[1/4] Project found at: $ATOM_DIR"
cd "$ATOM_DIR/backend" || exit 1

# Check PHP
if ! command -v php &> /dev/null; then
    echo "[2/4] PHP not found. Installing..."
    sudo apt update -qq
    sudo apt install -y php php-cli php-mbstring php-xml php-curl php-zip php-mysql
else
    echo "[2/4] PHP found: $(php -r 'echo PHP_VERSION;')"
fi

# Check Composer
if [ ! -f vendor/autoload.php ]; then
    echo "[3/4] Installing dependencies..."
    if ! command -v composer &> /dev/null; then
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --quiet
        php -r "unlink('composer-setup.php');"
        sudo mv composer.phar /usr/local/bin/composer
    fi
    composer install --quiet
else
    echo "[3/4] Dependencies already installed"
fi

# Run migrations
echo "[4/4] Running migrations..."
php spark migrate --force 2>/dev/null

echo
echo "========================================"
echo "  Starting API on http://localhost:8080"
echo "========================================"
echo
echo "  Frontend: Open frontend/web/index.html"
echo "  Stop:     Ctrl+C"
echo

php spark serve --host 0.0.0.0
