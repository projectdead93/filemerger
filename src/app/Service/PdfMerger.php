<?php

namespace App\Service;

use RuntimeException;

class PdfMerger
{
    public function __construct(
        private string $qpdfPath,
        private string $tempDir
    ) {}

    /**
     * @param string[] $inputFiles
     */
    public function merge(array $inputFiles, string $outputFile): void
    {
        $responseFile = tempnam($this->tempDir, 'qpdf_') . '.txt';

        $lines = ['--empty', '--pages'];
        foreach ($inputFiles as $file) {
            $lines[] = escapeshellarg($file);
        }
        $lines[] = '--';
        $lines[] = escapeshellarg($outputFile);

        file_put_contents($responseFile, implode("\n", $lines));

        $cmd = sprintf('%s @%s 2>&1', escapeshellarg($this->qpdfPath), escapeshellarg($responseFile));
        exec($cmd, $out, $exitCode);

        unlink($responseFile);

        if ($exitCode !== 0) {
            throw new RuntimeException('qpdf merge failed: ' . implode("\n", $out));
        }
    }
}