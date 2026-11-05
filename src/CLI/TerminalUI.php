<?php

namespace Atom\CLI;

class TerminalUI
{
    public const COLOR_RESET   = "\033[0m";
    public const COLOR_GREEN   = "\033[32m";
    public const COLOR_RED     = "\033[31m";
    public const COLOR_YELLOW  = "\033[33m";
    public const COLOR_BLUE    = "\033[34m";
    public const COLOR_MAGENTA = "\033[35m";
    public const COLOR_CYAN    = "\033[36m";
    public const COLOR_BOLD    = "\033[1m";
    public const COLOR_DIM     = "\033[2m";

    public function __construct()
    {
        // Enforce UTF-8 Code Page 65001 on Windows console to support box drawing & unicode glyphs
        if (function_exists('sapi_windows_cp_set')) {
            @sapi_windows_cp_set(65001);
        }
    }

    /**
     * Prints text followed by a newline, optional color.
     */
    public function writeLine(string $text = '', string $color = ''): void
    {
        echo $this->colorize($text, $color) . PHP_EOL;
    }

    /**
     * Prints text without a newline, optional color.
     */
    public function write(string $text, string $color = ''): void
    {
        echo $this->colorize($text, $color);
    }

    /**
     * Reads user input with an optional prompt.
     */
    public function readInput(string $prompt = 'atom> '): string
    {
        $this->write($prompt, self::COLOR_BOLD . self::COLOR_CYAN);
        $input = fgets(STDIN);
        return $input === false ? '' : trim($input);
    }

    /**
     * Clears the terminal screen.
     */
    public function clearScreen(): void
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            passthru('cls');
        } else {
            passthru('clear');
        }
    }

    /**
     * Wraps text in ANSI color codes if stdout is a terminal.
     */
    private function colorize(string $text, string $color): string
    {
        if (empty($color)) {
            return $text;
        }
        return $color . $text . self::COLOR_RESET;
    }

    // Helper functions for common styles
    public function success(string $text): void { $this->writeLine($text, self::COLOR_GREEN); }
    public function error(string $text): void { $this->writeLine($text, self::COLOR_RED); }
    public function warning(string $text): void { $this->writeLine($text, self::COLOR_YELLOW); }
    public function info(string $text): void { $this->writeLine($text, self::COLOR_BLUE); }
    public function highlight(string $text): void { $this->writeLine($text, self::COLOR_BOLD . self::COLOR_CYAN); }

    public function renderHeader(array $status): void
    {
        $providerName = $status['provider_name'] ?? 'Groq';
        $colMode = strtoupper($status['collaboration_mode'] ?? 'BALANCED');
        $safeMode = ($status['mode'] ?? 'SAFE') . " MODE";
        $knowledgeLevel = strtoupper($status['knowledge_level'] ?? 'ADVANCED');
        $workspaceFiles = $status['workspace_files'] ?? 0;
        $pdfDocs = $status['pdf_library'] ?? 0;
        $memoriesCount = $status['memories_count'] ?? 0;

        $this->writeLine("╔════════════════════════════════════════════════════╗", self::COLOR_CYAN);
        $this->writeLine("║                     A T O M                        ║", self::COLOR_BOLD . self::COLOR_CYAN);
        $this->writeLine("║                                                    ║", self::COLOR_CYAN);
        $this->writeLine("║            Personal AI System for Vichu            ║", self::COLOR_BOLD . self::COLOR_GREEN);
        $this->writeLine("╠════════════════════════════════════════════════════╣", self::COLOR_CYAN);
        $this->writeLine();
        $this->writeLine("  Core            READY", self::COLOR_GREEN);
        $this->writeLine("  Workspace       READY", self::COLOR_GREEN);
        $this->writeLine("  Memory          READY", self::COLOR_GREEN);
        $this->writeLine("  Knowledge       READY", self::COLOR_GREEN);
        $this->writeLine("  Learning Engine READY", self::COLOR_GREEN);
        $this->writeLine();
        $this->writeLine("  AI Provider     " . $providerName, self::COLOR_CYAN);
        $this->writeLine("  Collaboration   " . $colMode, self::COLOR_YELLOW);
        $this->writeLine("  Safe Mode       " . $safeMode, self::COLOR_GREEN);
        $this->writeLine();
        $this->writeLine("  Knowledge Level " . $knowledgeLevel, self::COLOR_MAGENTA);
        $this->writeLine();
        $this->writeLine("  Workspace       " . $workspaceFiles . " files");
        $this->writeLine("  PDF Library     " . $pdfDocs . " documents");
        $this->writeLine("  Memories        " . $memoriesCount . " memories");
        $this->writeLine("╚════════════════════════════════════════════════════╝", self::COLOR_CYAN);
        $this->writeLine();
    }
}
