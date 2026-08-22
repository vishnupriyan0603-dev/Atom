# Apache Reference

## Virtual Host
```apache
<VirtualHost *:80>
    ServerName example.com
    ServerAlias www.example.com
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

## .htaccess Common Rules
- `RewriteEngine On` to enable mod_rewrite.
- `Options -Indexes` to disable directory listing.
- Redirect HTTP to HTTPS.
- Set caching headers for static assets.
- Block access to sensitive files (`.env`, `.git`).

## Useful Modules
- `mod_rewrite` - URL rewriting.
- `mod_ssl` - HTTPS support.
- `mod_headers` - Custom HTTP headers.
- `mod_expires` - Caching headers.
- `mod_deflate` - Gzip compression.
- `mod_proxy` - Reverse proxy.
