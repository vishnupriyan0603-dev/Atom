<?php

namespace Atom\PersonalModel;

class ModelResponse
{
    private bool $success;
    private string $content;
    private ?string $error;
    private ?int $tokensIn;
    private ?int $tokensOut;

    public function __construct(bool $success, string $content, ?string $error = null, ?int $tokensIn = null, ?int $tokensOut = null)
    {
        $this->success = $success;
        $this->content = $content;
        $this->error = $error;
        $this->tokensIn = $tokensIn;
        $this->tokensOut = $tokensOut;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getTokensIn(): ?int
    {
        return $this->tokensIn;
    }

    public function getTokensOut(): ?int
    {
        return $this->tokensOut;
    }
}
