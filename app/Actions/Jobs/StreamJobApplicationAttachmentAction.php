<?php

namespace App\Actions\Jobs;

use App\Models\JobApply;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StreamJobApplicationAttachmentAction
{
    private const CHUNK_SIZE = 8192;

    public function handle(JobApply $application): StreamedResponse
    {
        $filePath = public_path('storage/job/cv/'.$application->attachment);
        $fileName = basename((string) $application->attachment);

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
}
