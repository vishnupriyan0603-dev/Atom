<?php
/**
 * AI Provider Configuration
 */
return [
    'provider' => \Atom\Config\Config::get('LLM_PROVIDER', 'groq'),
    'model' => \Atom\Config\Config::get('LLM_MODEL', 'openai/gpt-oss-120b'),
    'api_key' => \Atom\Config\Config::get('LLM_API_KEY', ''),
    'api_url' => \Atom\Config\Config::get('LLM_API_URL', 'https://api.groq.com/openai/v1'),

    // Provider-specific credentials (all registered simultaneously for per-chat routing)
    'providers' => [
        'groq' => [
            'api_key' => \Atom\Config\Config::get('GROQ_API_KEY', ''),
            'api_url' => \Atom\Config\Config::get('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
            'model' => \Atom\Config\Config::get('GROQ_MODEL', 'openai/gpt-oss-120b'),
        ],
        'gemini' => [
            'api_key' => \Atom\Config\Config::get('GEMINI_API_KEY', ''),
            'api_url' => \Atom\Config\Config::get('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model' => \Atom\Config\Config::get('GEMINI_MODEL', 'gemini-3.6-flash'),
        ],
        'openai' => [
            'api_key' => \Atom\Config\Config::get('OPENAI_API_KEY', ''),
            'api_url' => \Atom\Config\Config::get('OPENAI_API_URL', 'https://api.openai.com/v1'),
            'model' => \Atom\Config\Config::get('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'local' => [
            'api_url' => \Atom\Config\Config::get('LLM_LOCAL_ENDPOINT', 'http://localhost:11434/v1'),
            'model' => \Atom\Config\Config::get('LLM_LOCAL_MODEL', 'llama3.1'),
        ],
    ],
];
