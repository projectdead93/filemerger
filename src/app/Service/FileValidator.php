<?php

namespace App\Service;

use  App\Service\Logger;

class FileValidator
{
    public function __construct(private Logger $logger) {}

    public function isRealPdf(string $path): bool
    {
        if (!is_file($path)) {
            $this->logger->logRejection($path, 'File does not exist or is not accessible');
            return false;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        if ($mime !== 'application/pdf') {
            $this->logger->logRejection($path, "Invalid mime type: $mime");
            return false;
        }

        return true;

    }
}