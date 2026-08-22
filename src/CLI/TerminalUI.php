<?php

namespace Atom\CLI;

class TerminalUI
{
    public const COLOR_RESET = "\033[0m";
    public const COLOR_GREEN = "\033[32m";
    public const COLOR_RED = "\033[31m";
    public const COLOR_YELLOW = "\033[33m";
    public const COLOR_BLUE = "\033[34m";
    public const COLOR_CYAN = "\033[36m";
    public const COLOR_BOLD = "\033[1m";

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

        // Direct stream checks aren't always perfect on Windows CLI, but standard ANSI supports Windows 10/11
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
        $this->writeLine("╔════════════════════════════════════════════════════╗", self::COLOR_BLUE);
        $this->writeLine("║                     A T O M                        ║", self::COLOR_BLUE);
        $this->writeLine("║                                                    ║", self::COLOR_BLUE);
        $this->writeLine("║            Personal AI System for Vichu            ║", self::COLOR_BLUE);
        $this->writeLine("╠════════════════════════════════════════════════════╣", self::COLOR_BLUE);
        $this->writeLine();
        $this->writeLine("  Core            READY");
        $this->writeLine("  Workspace       READY");
        $this->writeLine("  Memory          READY");
        $this->writeLine("  Knowledge       READY");
        $this->writeLine("  Learning Engine READY");
        $this->writeLine();
        $this->writeLine("  AI Provider     " . $status['provider_name']);
        $this->writeLine("  Collaboration   " . strtoupper($status['collaboration_mode']));
        $this->writeLine("  Safe Mode       " . $status['mode'] . " MODE");
        $this->writeLine();
        $this->writeLine("  Knowledge Level " . strtoupper($status['knowledge_level']));
        $this->writeLine();
        $this->writeLine("  Workspace       " . $status['workspace_files'] . " files");
        $this->writeLine("  PDF Library     " . $status['pdf_library'] . " documents");
        $this->ui_mem = $status['memories_count'] ?? 0;
        $this->writeLine("  Memories        " . $this->ui_mem . " memories");
        $this->writeLine("╚════════════════════════════════════════════════════╝", self::COLOR_BLUE);
        $this->writeLine();
    }
}
