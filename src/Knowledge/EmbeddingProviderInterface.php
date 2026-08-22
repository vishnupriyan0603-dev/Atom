<?php

namespace Atom\Knowledge;

interface EmbeddingProviderInterface
{
    /**
     * Generate embedding vector for the given text.
     *
     * @param string $text
     * @return array
     */
    public function getEmbedding(string $text): array;
}
