<?php

namespace Atom\DevOps;

class IaCBlueprintGenerator
{
    /**
     * Generate Infrastructure as Code blueprints based on target format.
     */
    public function generateBlueprint(string $format = 'docker', array $options = []): array
    {
        $format = strtolower(trim($format));
        $phpVersion = $options['php_version'] ?? '8.3';
        $appName = $options['app_name'] ?? 'atom-assistant';

        switch ($format) {
            case 'docker':
            case 'dockerfile':
                $content = $this->generateDockerfile($phpVersion);
                $filename = 'Dockerfile';
                break;

            case 'compose':
            case 'docker-compose':
                $content = $this->generateDockerCompose($appName);
                $filename = 'docker-compose.yml';
                break;

            case 'k8s':
            case 'kubernetes':
                $content = $this->generateKubernetesManifest($appName);
                $filename = 'k8s-deployment.yaml';
                break;

            case 'terraform':
                $content = $this->generateTerraform($appName);
                $filename = 'main.tf';
                break;

            default:
                return [
                    'success' => false,
                    'error' => "Unsupported IaC format '{$format}'. Supported: docker, compose, k8s, terraform.",
                ];
        }

        return [
            'success' => true,
            'format' => $format,
            'filename' => $filename,
            'blueprint' => $content,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function generateDockerfile(string $phpVersion): string
    {
        return <<<DOCKER
FROM php:{$phpVersion}-apache

# Install required extensions
RUN apt-get update && apt-get install -y \\
    libicu-dev libsqlite3-dev libzip-dev zip unzip git \\
    && docker-php-ext-install intl pdo pdo_mysql opcache zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html
COPY . /var/www/html/

# Expose ports
EXPOSE 80

CMD ["apache2-foreground"]
DOCKER;
    }

    private function generateDockerCompose(string $appName): string
    {
        return <<<COMPOSE
version: '3.8'

services:
  app:
    build: .
    container_name: {$appName}-app
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      - DB_HOST=db
      - DB_NAME=atom_assistant
      - DB_USER=atom
      - DB_PASSWORD=secret
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    container_name: {$appName}-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: atom_assistant
      MYSQL_USER: atom
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: root
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:alpine
    container_name: {$appName}-redis
    restart: unless-stopped

volumes:
  db_data:
COMPOSE;
    }

    private function generateKubernetesManifest(string $appName): string
    {
        return <<<K8S
apiVersion: apps/v1
kind: Deployment
metadata:
  name: {$appName}-deployment
  labels:
    app: {$appName}
spec:
  replicas: 3
  selector:
    matchLabels:
      app: {$appName}
  template:
    metadata:
      labels:
        app: {$appName}
    spec:
      containers:
      - name: {$appName}
        image: atom/{$appName}:latest
        ports:
        - containerPort: 80
        resources:
          limits:
            cpu: "500m"
            memory: "512Mi"
          requests:
            cpu: "100m"
            memory: "128Mi"
---
apiVersion: v1
kind: Service
metadata:
  name: {$appName}-service
spec:
  type: LoadBalancer
  ports:
  - port: 80
    targetPort: 80
  selector:
    app: {$appName}
K8S;
    }

    private function generateTerraform(string $appName): string
    {
        return <<<TF
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = "us-east-1"
}

resource "aws_ecs_cluster" "main" {
  name = "{$appName}-cluster"
}
TF;
    }
}