<?php
/**
 * Knowledge Library Configuration
 */
return [
    'supported_types' => ['pdf', 'txt', 'md'],
    'chunk_size' => 1000, // characters
    'chunk_overlap' => 200, // characters
    'max_retrieved_chunks' => 3,
];
