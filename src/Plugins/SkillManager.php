<?php

namespace Atom\Plugins;

use Atom\Tools\ToolRegistry;

class SkillManager
{
    /** @var array<string, SkillManifest> */
    private array $skills = [];
    private array $executionHistory = [];
    private ?ToolRegistry $toolRegistry;

    public function __construct(?ToolRegistry $toolRegistry = null)
    {
        $this->toolRegistry = $toolRegistry;
        $this->loadDefaultBuiltinSkills();
    }

    private function loadDefaultBuiltinSkills(): void
    {
        $builtins = [
            new SkillManifest('filesystem', '1.2.0', 'Workspace file reading, creating, and linting tools', 'ATOM Core', ['read_file', 'create_file', 'patch_file'], ['filesystem.read', 'filesystem.write']),
            new SkillManifest('database', '1.1.0', 'Database query, migration, and solution backup tools', 'ATOM Core', ['db_query', 'db_backup'], ['database.read', 'database.write']),
            new SkillManifest('calculator', '1.0.0', 'Mathematical reasoning and evaluation tools', 'ATOM Core', ['calculate'], ['math.eval']),
            new SkillManifest('github', '1.0.0', 'Git repository integration and pull request tools', 'ATOM Core', ['git_commit', 'git_branch'], ['git.write']),
            new SkillManifest('weather', '1.0.0', 'Live environment weather and location tools', 'ATOM Core', ['get_weather'], ['api.network']),
            new SkillManifest('browser', '1.0.0', 'Headless web page content scraper tool', 'ATOM Core', ['scrape_url'], ['network.http']),
        ];

        foreach ($builtins as $skill) {
            $this->registerSkill($skill);
        }
    }

    public function registerSkill(SkillManifest $manifest): void
    {
        $this->skills[$manifest->name] = $manifest;
    }

    public function getSkill(string $name): ?SkillManifest
    {
        return $this->skills[strtolower($name)] ?? null;
    }

    public function getSkills(): array
    {
        return array_values($this->skills);
    }

    public function enableSkill(string $name): bool
    {
        $skill = $this->getSkill($name);
        if ($skill === null) {
            return false;
        }

        $skill->enabled = true;

        if ($this->toolRegistry !== null) {
            foreach ($skill->tools as $t) {
                $this->toolRegistry->enableTool($t);
            }
        }

        return true;
    }

    public function disableSkill(string $name): bool
    {
        $skill = $this->getSkill($name);
        if ($skill === null) {
            return false;
        }

        $skill->enabled = false;

        if ($this->toolRegistry !== null) {
            foreach ($skill->tools as $t) {
                $this->toolRegistry->disableTool($t);
            }
        }

        return true;
    }

    public function logExecution(string $skillName, string $toolName, bool $success, ?string $error = null): void
    {
        $this->executionHistory[] = [
            'skill'      => $skillName,
            'tool'       => $toolName,
            'success'    => $success,
            'error'      => $error,
            'timestamp'  => date('Y-m-d H:i:s'),
        ];
    }

    public function getExecutionHistory(?string $skillName = null): array
    {
        if ($skillName !== null && $skillName !== '') {
            $sName = strtolower($skillName);
            return array_values(array_filter($this->executionHistory, fn($h) => strtolower($h['skill']) === $sName));
        }

        return $this->executionHistory;
    }
}
