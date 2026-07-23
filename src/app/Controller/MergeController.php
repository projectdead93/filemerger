<?php

namespace App\Controller;

use App\Service\PdfMerger;
use App\Service\BatchQueue;
use App\Service\Logger;
use App\Service\ProgressTracker;
use App\Service\ProgressReporter;

class MergeController
{
    public function __construct(
        private BatchQueue $batchQueue,
        private PdfMerger $merger,
        private string $outputDir,
        private Logger $logger,
        private ProgressTracker $progressTracker,
        private ProgressReporter $reporter
    ) {}

    public function run(\Generator $fileStream, string $batchPrefix): void
    {
                $batchNumber = $this->progressTracker->getNextBatchNumber();

                foreach ($this->batchQueue->generateBatches($fileStream) as $batch) {
                    $outputFile = $this->outputDir . DIRECTORY_SEPARATOR
                        . sprintf('%s_%03d.pdf', $batchPrefix, $batchNumber);

                    try {
                        $this->merger->merge($batch, $outputFile);
                        $outputBytes = filesize($outputFile);
                        $this->logger->logBatchResult($batchNumber, $outputFile, count($batch), $outputBytes, true);
                        $this->progressTracker->markBatchComplete($batchNumber, $batch); // <-- restored
                        $this->reporter->reportBatchSuccess($batchNumber, $outputFile, count($batch), $outputBytes);
                    } catch (\RuntimeException $e) {
                        $this->reporter->reportBatchFailure($batchNumber, $e->getMessage());
                        $this->logger->logBatchResult($batchNumber, $outputFile, count($batch), 0, false);
                    }

                    $batchNumber++;
            }
   }
}