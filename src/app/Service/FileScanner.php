<?php

namespace App\Service;
use RuntimeException;

class FileScanner
{

     /**
     * @return \Generator<string>
     */
    public function scan(string $directory): \Generator
    {
        if (!is_dir($directory)) {
            throw new RuntimeException("Source directory does not exist: $directory");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                yield $fileInfo->getPathname();
            }
        }
    }

}