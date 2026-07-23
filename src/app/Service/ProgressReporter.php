<?php

namespace App\Service;
use App\Service\ProgressTracker;

class ProgressReporter
{
    public function __construct(private ProgressTracker $progressTracker) {}

    public function reportBatchStart(int $batchNumber, int $fileCount): void
    {
        echo sprintf("[Batch %d] Starting — %d files\n", $batchNumber, $fileCount);
    }

    public function reportBatchSuccess(int $batchNumber, string $outputFile, int $fileCount, int $outputBytes): void
    {
        $mb = round($outputBytes / 1024 / 1024, 2);
        echo sprintf(
            "[Batch %d] Done — %d files, %s MB -> %s\n",
            $batchNumber, $fileCount, $mb, basename($outputFile)
        );
    }

    public function reportBatchFailure(int $batchNumber, string $error): void
    {
        echo sprintf("[Batch %d] FAILED — %s\n", $batchNumber, $error);
    }

    public function reportFinalSummary(int $totalCandidates): void
    {
        $processed = $this->progressTracker->getProcessedCount();
        $remaining = max(0, $totalCandidates - $processed);

        echo str_repeat('-', 40) . "\n";
        echo sprintf("Total candidate files: %d\n", $totalCandidates);
        echo sprintf("Successfully processed: %d\n", $processed);
        echo sprintf("Remaining/skipped: %d\n", $remaining);
        echo str_repeat('-', 40) . "\n";
    }
}