<?php

namespace App\Service;

class ProgressTracker
{
    private string $stateFile;
    private array $processedFiles = [];
    private int $lastBatchNumber = 0;

    public function __construct(string $storageDir)
    {
        $this->stateFile = rtrim($storageDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'progress_state.csv';

        $this->load();
    }

    private function load(): void
    {
        if (!file_exists($this->stateFile)) {
            return;
        }

        $fh = fopen($this->stateFile, 'r');
        $header = fgetcsv($fh); // skip header row

        while (($row = fgetcsv($fh)) !== false) {
            [$batchNumber, $filePath] = $row;
            $this->processedFiles[$filePath] = true;
            $this->lastBatchNumber = max($this->lastBatchNumber, (int) $batchNumber);
        }

        fclose($fh);
    }

    public function isProcessed(string $filePath): bool
    {
        return isset($this->processedFiles[$filePath]);
    }

    public function getNextBatchNumber(): int
    {
        return $this->lastBatchNumber + 1;
    }

    /**
     * Call this only after a batch has successfully merged —
     * records every file in it as done, so a resumed run skips them.
     *
     * @param string[] $files
     */
    public function markBatchComplete(int $batchNumber, array $files): void
    {
        $isNew = !file_exists($this->stateFile);
        $fh = fopen($this->stateFile, 'a');

        if ($isNew) {
            fputcsv($fh, ['batch_number', 'file_path']);
        }

        foreach ($files as $file) {
            fputcsv($fh, [$batchNumber, $file]);
            $this->processedFiles[$file] = true;
        }

        fclose($fh);
        $this->lastBatchNumber = max($this->lastBatchNumber, $batchNumber);
    }

    // in ProgressTracker
    public function getProcessedCount(): int
    {
        return count($this->processedFiles);
    }
}