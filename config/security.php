<?php
/**
 * Security and Permissions Configuration
 */
return [
    'safe_mode' => true,
    'permissions' => [
        'read' => 'auto',
        'write' => 'confirm',
        'delete' => 'confirm',
        'execute' => 'confirm',
        'dangerous' => 'always_confirm'
    ]
];
