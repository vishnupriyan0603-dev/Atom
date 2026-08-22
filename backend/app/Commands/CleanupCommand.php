<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanupCommand extends BaseCommand
{
    protected $group       = 'Atom';
    protected $name        = 'atom:cleanup-gemini-history';
    protected $description = 'Clean up Gemini detailed history older than configured days (default 7 days).';
    protected $usage       = 'atom:cleanup-gemini-history [days]';
    protected $arguments   = [
        'days' => 'Number of retention days. Defaults to GEMINI_HISTORY_DAYS or 7.'
    ];

    public function run(array $params)
    {
        $days = $params[0] ?? getenv('GEMINI_HISTORY_DAYS') ?: 7;
        if (!is_numeric($days) || $days < 0) {
            $days = 7;
        }

        $db = \Config\Database::connect();
        
        CLI::write("Cleaning up Gemini detailed responses older than {$days} days...", 'yellow');
        
        try {
            // Delete responses where created_at is older than $days
            $builder = $db->table('atom_responses');
            $builder->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")));
            $builder->delete();
            
            CLI::write("Cleanup completed successfully.", 'green');
        } catch (\Exception $e) {
            CLI::write("Error executing cleanup: " . $e->getMessage(), 'red');
        }
    }
}
