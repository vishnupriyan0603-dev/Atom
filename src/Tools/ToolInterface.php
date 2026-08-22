<?php

namespace Atom\Tools;

interface ToolInterface
{
    /**
     * Returns the unique name of the tool.
     */
    public function getName(): string;

    /**
     * Executes the tool with structured parameters.
     * Returns an array containing the results (e.g. ['success' => true, 'output' => '...']).
     */
    public function execute(array $input): array;
}
