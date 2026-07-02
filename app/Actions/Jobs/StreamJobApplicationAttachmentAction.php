<?php

namespace App\Actions\Jobs;

use App\Models\JobApply;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StreamJobApplicationAttachmentAction
{
    private const CHUNK_SIZE = 8192;

    public function handle(JobApply $application): StreamedResponse
    {
        $filePath = $this->attachmentPath($application);
        if ($filePath === null) {
            throw new RuntimeException('Unable to open job application attachment.');
        }

        $fileName = (string) $application->attachment;

        return response()->streamDownload(function () use ($filePath): void {
            $handle = fopen($filePath, 'rb');

            if ($handle === false) {
                throw new RuntimeException('Unable to open job application attachment.');
            }

            try {
                while (! feof($handle)) {
                    echo fread($handle, self::CHUNK_SIZE);
                    flush();
                }
            } finally {
                fclose($handle);
            }
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exists(JobApply $application): bool
    {
        return $this->attachmentPath($application) !== null;
    }

    private function attachmentPath(JobApply $application): ?string
    {
        $fileName = (string) $application->attachment;
        if (! $this->isSafePdfFileName($fileName)) {
            return null;
        }

        $privatePath = Storage::disk('local')->path('job/cv/'.$fileName);
        if ($this->isReadableFileWithin($privatePath, Storage::disk('local')->path('job/cv'))) {
            return $privatePath;
        }

        $legacyPath = public_path('storage/job/cv/'.$fileName);
        if ($this->isReadableFileWithin($legacyPath, public_path('storage/job/cv'))) {
            return $legacyPath;
        }

        return null;
    }

    private function isSafePdfFileName(string $fileName): bool
    {
        return $fileName !== ''
            && basename($fileName) === $fileName
            && ! str_contains($fileName, "\0")
            && (bool) preg_match('/\A[A-Za-z0-9._-]+\.pdf\z/i', $fileName);
    }

    private function isReadableFileWithin(string $path, string $directory): bool
    {
        $root = realpath($directory);
        $file = realpath($path);

        return $root !== false
            && $file !== false
            && is_file($file)
            && str_starts_with($file, rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    }
}
