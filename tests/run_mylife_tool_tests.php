<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atom\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

final class MyLifeToolFixtureClient implements Atom\MyLife\Contract\MyLifeClientInterface
{
    public int $reads = 0;

    public function ping(): Atom\MyLife\Contract\MyLifeResponse
    {
        $this->reads++;
        return Atom\MyLife\Contract\MyLifeResponse::success(['status' => 'ok']);
    }

    public function pair(string $pairingSecret, string $clientName = 'ATOM-WINDOWS'): Atom\MyLife\Contract\MyLifeResponse
    {
        return Atom\MyLife\Contract\MyLifeResponse::failure('not used', 501);
    }

    public function getDeviceStatus(): Atom\MyLife\Contract\MyLifeResponse
    {
        $this->reads++;
        return Atom\MyLife\Contract\MyLifeResponse::success(['battery' => ['level' => 87]]);
    }

    public function setTrackingPaused(bool $paused): Atom\MyLife\Contract\MyLifeResponse
    {
        return Atom\MyLife\Contract\MyLifeResponse::failure('not used', 501);
    }

    public function getExecutiveSummary(): Atom\MyLife\Contract\MyLifeResponse
    {
        $this->reads++;
        return Atom\MyLife\Contract\MyLifeResponse::success(['health' => ['today_steps' => 500]]);
    }

    public function getModuleData(Atom\MyLife\Contract\MyLifeModuleEnum $module, array $params = []): Atom\MyLife\Contract\MyLifeResponse
    {
        $this->reads++;
        return Atom\MyLife\Contract\MyLifeResponse::success(['module' => $module->value]);
    }

    public function postModuleAction(Atom\MyLife\Contract\MyLifeModuleEnum $module, string $action, array $payload): Atom\MyLife\Contract\MyLifeResponse
    {
        return Atom\MyLife\Contract\MyLifeResponse::failure('not used', 501);
    }

    public function downloadEncryptedBackup(string $passphrase, string $destinationPath): Atom\MyLife\Contract\MyLifeResponse
    {
        return Atom\MyLife\Contract\MyLifeResponse::failure('not used', 501);
    }
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$client = new MyLifeToolFixtureClient();
$tool = new Atom\MyLife\Client\MyLifeOSTool($client);
$manager = new Atom\Tools\ToolManager();
$manager->registerTool($tool);

$definition = $manager->getRegistry()->getDefinition('mylife_os');
$assert($definition !== null && $definition->requiresHumanApproval(), 'MyLife tool must be classified high risk.');

$modelFlag = $manager->executeTool('mylife_os', ['operation' => 'summary', 'human_approved' => true]);
$assert(!$modelFlag['success'] && !empty($modelFlag['requires_human_gate']), 'Model-provided approval must not authorize MyLife access.');
$assert($client->reads === 0, 'MyLife data was read before trusted approval.');

$manager->setApprovalProvider(static fn (string $name, array $arguments, Atom\Tools\ToolDefinition $definition): bool => false);
$denied = $manager->executeTool('mylife_os', ['operation' => 'summary']);
$assert(!$denied['success'] && $client->reads === 0, 'A rejected approval must not read personal data.');

$manager->setApprovalProvider(static fn (string $name, array $arguments, Atom\Tools\ToolDefinition $definition): bool => true);
$approved = $manager->executeTool('mylife_os', ['operation' => 'module', 'module' => '02_finance']);
$assert($approved['success'] && !empty($approved['sensitive_data']), 'Approved module read did not return sensitive-data metadata.');
$assert($client->reads === 1, 'Approved access should perform exactly one read.');

$context = new Atom\Brain\ContextBuilder(dirname(__DIR__));
$context->registerToolCapability('mylife_os', 'Reads a requested local module after user approval.');
$prompt = $context->buildSystemMessage()['content'];
$assert(str_contains($prompt, "'mylife_os'") && str_contains($prompt, 'after user approval'), 'ContextBuilder did not advertise the enabled MyLife capability.');

fwrite(STDOUT, "MyLife tool checks passed (7 assertions).\n");
