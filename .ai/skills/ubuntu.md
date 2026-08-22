# Ubuntu Reference

## Common Commands
```bash
# System
sudo apt update && sudo apt upgrade -y
systemctl status apache2
systemctl restart php8.1-fpm
journalctl -u nginx -f

# File permissions
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Users
adduser username
usermod -aG sudo username
passwd username
```

## PHP Setup
```bash
sudo apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath
```

## Security
- Enable UFW: `sudo ufw enable; sudo ufw allow 22,80,443/tcp`
- Install Fail2ban: `sudo apt install fail2ban`
- Automatic security updates: `sudo dpkg-reconfigure unattended-upgrades`
- Disable root login via SSH (edit `/etc/ssh/sshd_config`).
