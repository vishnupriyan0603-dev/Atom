<?php

namespace Atom\Tools;

class ToolManager
{
    private array $tools = [];

    /**
     * Registers a tool.
     */
    public function registerTool(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * Checks if tool exists.
     */
    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Executes the registered tool by name.
     */
    public function executeTool(string $name, array $arguments): array
    {
        if (!$this->hasTool($name)) {
            return [
                'success' => false,
                'error' => "Requested tool '{$name}' is not registered."
            ];
        }

        return $this->tools[$name]->execute($arguments);
    }

    /**
     * Lists registered tools for context builder schemas.
     */
    public function getToolsList(): array
    {
        $list = [];
        foreach ($this->tools as $name => $tool) {
            // Document basic info
            $list[] = [
                'name' => $name,
                'description' => $this->getToolDescription($name)
            ];
        }
        return $list;
    }

    private function getToolDescription(string $name): string
    {
        switch ($name) {
            case 'read_file':
                return 'Reads text files inside the workspace. Arguments: {"file_path": "relative/path/to/file.php"}';
            case 'search_code':
                return 'Searches code content/filenames inside the workspace. Arguments: {"query": "string_to_search"}';
            case 'php_lint':
                return 'Checks syntax of a PHP file. Arguments: {"file_path": "relative/path/to/file.php"}';
            case 'create_file':
                return 'Creates a new file in workspace. Arguments: {"file_path": "relative/path/to/file.php", "content": "text"}';
            case 'patch_file':
                return 'Searches and replaces code inside a file. Arguments: {"file_path": "relative/path/to/file.php", "target_content": "old_code", "replacement_content": "new_code", "interactive": true}';
            default:
                return 'Custom tool execution.';
        }
    }
}
