<?php

namespace Atom\Plugins;

class SkillManifest
{
    public string $name;
    public string $version;
    public string $description;
    public string $author;
    public array $tools;
    public array $permissions;
    public array $dependencies;
    public bool $enabled;

    public function __construct(
        string $name,
        string $version = '1.0.0',
        string $description = '',
        string $author = 'ATOM Platform',
        array $tools = [],
        array $permissions = [],
        array $dependencies = [],
        bool $enabled = true
    ) {
        $this->name = strtolower($name);
        $this->version = $version;
        $this->description = $description;
        $this->author = $author;
        $this->tools = $tools;
        $this->permissions = $permissions;
        $this->dependencies = $dependencies;
        $this->enabled = $enabled;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? 'unnamed_skill',
            version: $data['version'] ?? '1.0.0',
            description: $data['description'] ?? '',
            author: $data['author'] ?? 'ATOM Platform',
            tools: $data['tools'] ?? [],
            permissions: $data['permissions'] ?? [],
            dependencies: $data['dependencies'] ?? [],
            enabled: (bool)($data['enabled'] ?? true)
        );
    }

    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'version'      => $this->version,
            'description'  => $this->description,
            'author'       => $this->author,
            'tools'        => $this->tools,
            'permissions'  => $this->permissions,
            'dependencies' => $this->dependencies,
            'enabled'      => $this->enabled,
        ];
    }
}
