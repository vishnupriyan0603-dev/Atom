<?php

namespace Atom\Tools;

interface ExtendedToolInterface extends ToolInterface
{
    /**
     * Returns the full metadata definition of the tool.
     */
    public function getDefinition(): ToolDefinition;
}
