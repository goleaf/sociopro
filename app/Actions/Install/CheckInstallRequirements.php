<?php

namespace App\Actions\Install;

class CheckInstallRequirements
{
    public function handle(bool $isLocalInstall): array
    {
        return [
            $this->fileRequirement('config/database.php', $isLocalInstall),
            $this->fileRequirement('routes/web.php', $isLocalInstall),
            [
                'label' => 'Curl Enabled',
                'passed' => function_exists('curl_version'),
                'message' => function_exists('curl_version') ? 'Available' : 'Required PHP extension is missing',
            ],
        ];
    }

    private function fileRequirement(string $relativePath, bool $isLocalInstall): array
    {
        if ($isLocalInstall) {
            return [
                'label' => $relativePath,
                'passed' => true,
                'message' => 'Skipped on local installation',
            ];
        }

        $isWritable = is_writable(base_path($relativePath));

        return [
            'label' => $relativePath,
            'passed' => $isWritable,
            'message' => $isWritable ? 'Writable by installer' : 'Needs write permission',
        ];
    }
}
