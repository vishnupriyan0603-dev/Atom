<?php

namespace Atom\Tools;

class ToolManager
{
    private array $tools = [];
    private ToolRegistry $registry;

    public function __construct(?ToolRegistry $registry = null)
    {
        $this->registry = $registry ?? new ToolRegistry();
    }

    public function getRegistry(): ToolRegistry
    {
        return $this->registry;
    }

    /**
     * Registers a tool.
     */
    public function registerTool(ToolInterface $tool, ?ToolDefinition $definition = null): void
    {
        $this->tools[$tool->getName()] = $tool;
        $this->registry->registerTool($tool, $definition);
    }

    /**
     * Checks if tool exists.
     */
    public function hasTool(string $name): bool
    {
        return $this->registry->hasTool($name);
    }

    /**
     * Executes the registered tool by name following full lifecycle security checks.
     */
    public function executeTool(string $name, array $arguments): array
    {
        if (!$this->hasTool($name)) {
            return [
                'success' => false,
                'error'   => "Requested tool '{$name}' is not registered."
            ];
        }

        if (!$this->registry->isEnabled($name)) {
            return [
                'success' => false,
                'error'   => "Tool '{$name}' is currently disabled by administrator policy."
            ];
        }

        // 1. Schema Validation
        $validation = $this->registry->validateInput($name, $arguments);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error'   => "Schema validation failed: " . $validation['error']
            ];
        }

        // 2. Risk Evaluation & Human Approval Gate
        $def = $this->registry->getDefinition($name);
        if ($def !== null && $def->requiresHumanApproval()) {
            if (empty($arguments['human_approved'])) {
                return [
                    'success'               => false,
                    'requires_human_gate'  => true,
                    'tool_name'             => $name,
                    'risk_level'            => $def->riskLevel,
                    'error'                 => "High-risk tool '{$name}' requires human approval before execution."
                ];
            }
        }

        // 3. Execution
        $handler = $this->registry->getHandler($name) ?? ($this->tools[$name] ?? null);
        if ($handler === null) {
            return [
                'success' => false,
                'error'   => "No handler found for tool '{$name}'."
            ];
        }

        try {
            return $handler->execute($arguments);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error'   => "Tool execution error: " . $e->getMessage()
            ];
        }
    }

    /**
     * Lists registered tools for context builder schemas.
     */
    public function getToolsList(): array
    {
        $list = [];
        foreach ($this->tools as $name => $tool) {
            $def = $this->registry->getDefinition($name);
            $desc = $def ? $def->description : $this->getToolDescription($name);
            $list[] = [
                'name'        => $name,
                'description' => $desc,
                'risk_level'  => $def ? $def->riskLevel : 'low',
                'schema'      => $def ? $def->inputSchema : []
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
