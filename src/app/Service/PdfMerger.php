<?php

namespace App\Service;

use RuntimeException;

use App\Service\Logger;

class PdfMerger
{
    public function __construct(
        private string $qpdfPath,
        private string $tempDir,
        private Logger $logger
    ) {}

    /**
     * @param string[] $inputFiles
     */
    public function merge(array $inputFiles, string $outputFile): void
    {
        $responseFile = tempnam($this->tempDir, 'qpdf_') . '.txt';

        $lines = ['--empty', '--pages'];
        foreach ($inputFiles as $file) {
            $lines[] = $file;
        }
        $lines[] = '--';
        $lines[] = $outputFile;

        file_put_contents($responseFile, implode("\n", $lines));

        $cmd = sprintf('%s @%s 2>&1', escapeshellarg($this->qpdfPath), escapeshellarg($responseFile));

        exec($cmd, $out, $exitCode);

        if (file_exists($responseFile)) {
            @unlink($responseFile);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException('qpdf merge failed: ' . implode("\n", $out));
        }

        $this->logger->info("Merged " . count($inputFiles) . " files -> $outputFile");
    }
}