# Nginx Reference

## Server Block
```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Performance
- Enable gzip: `gzip on;`
- Add cache headers for static files.
- Use `expires` for long-lived assets.
- Configure client body size: `client_max_body_size 32M;`
- Use `fastcgi_cache` for PHP caching.

## Security
- Hide nginx version: `server_tokens off;`
- Limit request rate: `limit_req_zone`.
- Restrict methods: `limit_except GET POST { deny all; }`.