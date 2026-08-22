<?php

namespace Atom\Brain;

class IntentDetector
{
    /**
     * Identifies the category of the user's intent.
     * Returns: 'conversation', 'project_list', 'project_coding', 'general'
     */
    public function detect(string $input): string
    {
        $inputLower = trim(strtolower($input));

        if (empty($inputLower)) {
            return 'conversation';
        }

        // 1. Simple greetings and purpose queries
        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'howdy', 'yo', 'what can you do?', 'what is your purpose?'];
        if (in_array($inputLower, $greetings, true) || preg_match('/^(hi|hello|hey|good\s+morning|what\s+can\s+you\s+do|what\s+is\s+your\s+purpose)(\s+.*)?$/i', $inputLower)) {
            return 'conversation';
        }

        // 2. Project scanning/lists
        if (preg_match('/(check my current project|scan files|list files|project structure|inspect project)/i', $inputLower)) {
            return 'project_list';
        }

        // 3. Coding/Debugging/Files
        if (preg_match('/(\.php|\.js|\.css|\.html|\.json|code|debug|explain|fix|error|syntax|variable|function|database|table|sql)/i', $inputLower)) {
            return 'project_coding';
        }

        return 'general';
    }
}
