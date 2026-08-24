<?php

namespace Atom\ModelGateway;

class ProviderCapabilities
{
    public bool $streaming;
    public bool $tools;
    public bool $vision;
    public bool $embeddings;
    public bool $structuredOutput;
    public bool $reasoning;

    public function __construct(
        bool $streaming = true,
        bool $tools = true,
        bool $vision = false,
        bool $embeddings = false,
        bool $structuredOutput = false,
        bool $reasoning = false
    ) {
        $this->streaming = $streaming;
        $this->tools = $tools;
        $this->vision = $vision;
        $this->embeddings = $embeddings;
        $this->structuredOutput = $structuredOutput;
        $this->reasoning = $reasoning;
    }

    public function toArray(): array
    {
        return [
            'streaming'         => $this->streaming,
            'tools'             => $this->tools,
            'vision'            => $this->vision,
            'embeddings'        => $this->embeddings,
            'structured_output' => $this->structuredOutput,
            'reasoning'         => $this->reasoning,
        ];
    }
}
