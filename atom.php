<?php

// Standalone PSR-4 Autoloader for Atom Namespace
spl_autoload_register(function ($class) {
    $prefix = 'Atom\\';
    $base_dir = __DIR__ . '/src/';
    
    // Check if the class uses our prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Custom mapping for Config namespace to config/ directory
    if (strncmp('Atom\\Config\\', $class, 12) === 0) {
        $file = __DIR__ . '/config/' . strtolower(substr($class, 12)) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separator with directory separator
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, load it
    if (file_exists($file)) {
        require $file;
    }
});

// Run the application
$app = new Atom\CLI\Application();
$app->run($argv);
