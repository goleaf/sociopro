<?php

namespace App\Actions\Install;

class UpdateEnvironmentFile
{
    public function handle(array $values): void
    {
        $path = base_path('.env');

        if (! is_file($path) || ! is_writable($path)) {
            return;
        }

        $content = file_get_contents($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->formatValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        file_put_contents($path, $content);
    }

    private function formatValue(mixed $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }
}
