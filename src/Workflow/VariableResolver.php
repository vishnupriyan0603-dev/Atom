<?php

namespace Atom\Workflow;

use Atom\Security\SecretRedactor;

class VariableResolver
{
    public static function resolveString(string $template, array $variables = []): string
    {
        if (strpos($template, '{{') === false) {
            return $template;
        }

        $redactor = new SecretRedactor();

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\-\.]+)\s*\}\}/', function ($matches) use ($variables, $redactor) {
            $key = $matches[1];
            $value = self::getValueByPath($variables, $key);
            if ($value === null) {
                return '';
            }
            $strVal = is_array($value) ? json_encode($value) : (string)$value;
            return $redactor->redact($strVal);
        }, $template);
    }

    public static function getValueByPath(array $array, string $path)
    {
        $parts = explode('.', $path);
        $current = $array;
        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } else {
                return null;
            }
        }
        return $current;
    }
}
