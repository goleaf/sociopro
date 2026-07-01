<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ExceptionHandlingAuditTest extends TestCase
{
    public function test_application_code_does_not_keep_die_or_exit_placeholders(): void
    {
        $matches = [];

        foreach ($this->phpFilesIn(app_path()) as $file) {
            $contents = file_get_contents($file->getPathname());

            if (preg_match('/\b(?:die|exit)\s*(?:\(|;)/', $contents) === 1) {
                $matches[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $matches);
    }

    /**
     * @return iterable<int, SplFileInfo>
     */
    private function phpFilesIn(string $path): iterable
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }
}
