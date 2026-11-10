<?php

declare(strict_types=1);

namespace Atom\MyLife\Client;

use Atom\MyLife\Contract\MyLifeClientInterface;
use Atom\MyLife\Contract\MyLifeModuleEnum;
use Atom\Tools\ExtendedToolInterface;
use Atom\Tools\ToolDefinition;
use JsonException;

/** Read-only MyLife access for explicit, human-approved chat requests. */
final class MyLifeOSTool implements ExtendedToolInterface
{
    public function __construct(private readonly MyLifeClientInterface $client)
    {
    }

    public function getName(): string
    {
        return 'mylife_os';
    }

    public function getDefinition(): ToolDefinition
    {
        return new ToolDefinition(
            name: $this->getName(),
            description: 'Reads the requested MyLife OS status, executive summary, or one module. This returns sensitive personal data and requires a real human approval prompt before access.',
            inputSchema: [
                'required' => ['operation'],
                'properties' => [
                    'operation' => ['type' => 'string', 'enum' => ['status', 'summary', 'module']],
                    'module' => ['type' => 'string', 'enum' => array_map(static fn (MyLifeModuleEnum $module): string => $module->value, MyLifeModuleEnum::cases())],
                ],
            ],
            permission: 'mylife.read',
            riskLevel: 'high',
            timeout: 10
        );
    }

    public function execute(array $input): array
    {
        $operation = $input['operation'] ?? null;
        if (!in_array($operation, ['status', 'summary', 'module'], true)) {
            return ['success' => false, 'error' => 'MyLife operation must be status, summary, or module.'];
        }

        if ($operation === 'status') {
            $response = $this->client->getDeviceStatus();
        } elseif ($operation === 'summary') {
            $response = $this->client->getExecutiveSummary();
        } else {
            $moduleValue = $input['module'] ?? null;
            $module = is_string($moduleValue) ? MyLifeModuleEnum::tryFrom($moduleValue) : null;
            if ($module === null) {
                return ['success' => false, 'error' => 'Provide a valid MyLife module ID.'];
            }
            $response = $this->client->getModuleData($module);
        }

        if (!$response->isSuccess()) {
            return [
                'success' => false,
                'error' => $response->getError() ?? 'MyLife request failed.',
                'status_code' => $response->getStatusCode(),
            ];
        }

        try {
            $output = json_encode($response->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['success' => false, 'error' => 'MyLife returned data that could not be formatted safely.'];
        }
        if (strlen($output) > 4500) {
            return ['success' => false, 'error' => 'MyLife result is too large for one private chat request. Ask for a narrower module or summary.'];
        }

        return [
            'success' => true,
            'sensitive_data' => true,
            'device' => $response->getDeviceInfo(),
            'output' => $output,
        ];
    }
}
