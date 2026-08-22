# Docker Reference

## Basic Commands
```bash
docker build -t image-name .
docker run -d -p 8080:80 image-name
docker compose up -d
docker compose down
docker ps                    # running containers
docker logs -f container_id  # follow logs
docker exec -it container_id bash  # shell access
```

## Docker Compose
```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
```

## Best Practices
- Use multi-stage builds for smaller images.
- Use `.dockerignore` to exclude unnecessary files.
- Use specific image tags (not `latest`).
- Keep containers stateless where possible.
- Use health checks.
- Never store secrets in Dockerfiles (use secrets or env).
