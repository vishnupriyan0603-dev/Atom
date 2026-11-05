<?php

namespace Atom\Tools;

use Atom\Arc\ArcTwoDesktopController;

/**
 * Tool representation of ARC TWO Windows Desktop Application & Browser Automation.
 */
class ArcDesktopTool implements ToolInterface
{
    private ArcTwoDesktopController $controller;

    public function __construct(?ArcTwoDesktopController $controller = null)
    {
        $this->controller = $controller ?? new ArcTwoDesktopController();
    }

    public function getName(): string
    {
        return 'arc_desktop';
    }

    public function execute(array $input): array
    {
        $action = strtolower((string)($input['action'] ?? 'open_application'));

        switch ($action) {
            case 'open_url':
            case 'url':
            case 'browse':
            case 'open_browser':
                $url = (string)($input['url'] ?? $input['target'] ?? $input['link'] ?? 'https://www.youtube.com');
                $browser = (string)($input['browser'] ?? 'chrome');
                return $this->controller->openUrl($url, $browser);

            case 'youtube':
            case 'search_youtube':
            case 'play_youtube':
            case 'play':
                $query = (string)($input['query'] ?? $input['text'] ?? $input['song'] ?? 'good tamil songs');
                $directPlay = (bool)($input['direct_play'] ?? false);
                return $this->controller->searchAndPlayYoutube($query, $directPlay);

            case 'open_application':
            case 'open':
            case 'launch':
                $app = (string)($input['app'] ?? $input['application'] ?? $input['target'] ?? 'notepad');
                $args = (array)($input['args'] ?? []);
                return $this->controller->openApplication($app, $args);

            case 'focus_window':
            case 'focus':
                $target = (string)($input['target'] ?? $input['title'] ?? 'notepad');
                return $this->controller->focusWindow($target);

            case 'type_text':
            case 'type':
            case 'write':
                $text = (string)($input['text'] ?? $input['content'] ?? '');
                $target = (string)($input['target'] ?? 'notepad');
                return $this->controller->typeText($text, $target);

            case 'press_key':
            case 'key':
                $key = (string)($input['key'] ?? '{ENTER}');
                $target = (string)($input['target'] ?? 'notepad');
                return $this->controller->pressKey($key, $target);

            case 'close_application':
            case 'close':
            case 'kill':
                $process = (string)($input['process'] ?? $input['app'] ?? 'notepad');
                $force = (bool)($input['force'] ?? false);
                return $this->controller->closeApplication($process, $force);

            case 'milestone_1':
            case 'milestone1':
                $text = (string)($input['text'] ?? 'Hi Vichu');
                return $this->controller->runMilestoneOne($text);

            default:
                return [
                    'success' => false,
                    'error' => "Unknown arc_desktop action '{$action}'. Supported: open_url, youtube, open_application, focus_window, type_text, press_key, close_application, milestone_1.",
                ];
        }
    }
}

