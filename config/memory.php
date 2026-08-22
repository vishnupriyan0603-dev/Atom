<?php
/**
 * Memory Configuration
 */
return [
    'layers' => [
        'working' => true,
        'session' => true,
        'long_term' => true,
        'personal' => true,
        'project' => true
    ],
    'personal_profile_path' => 'storage/profile/personal.json',
    'session_max_messages' => 20, // Max conversation history kept in working memory
];
