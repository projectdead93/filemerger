<?php

namespace App\Service;

class Logger
{
    private string $logDir;

    public function __construct(string $logDir)
    {
        $this->logDir = rtrim($logDir, DIRECTORY_SEPARATOR);

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    private function write(string $level, string $message): void
    {
        $line = sprintf('[%s] %s: %s' . PHP_EOL, date('Y-m-d H:i:s'), $level, $message);
        file_put_contents($this->logDir . DIRECTORY_SEPARATOR . 'app.log', $line, FILE_APPEND);
    }

    /**
     * Structured CSV log — specifically for rejected/failed files, since
     * you'll want to filter/sort/audit this later, not just read it top to bottom.
     */
    public function logRejection(string $filePath, string $reason): void
    {
        $csvFile = $this->logDir . DIRECTORY_SEPARATOR . 'rejected.csv';
        $isNew = !file_exists($csvFile);

        $fh = fopen($csvFile, 'a');
        if ($fh === false) {
            error_log("Could not open rejected.csv for writing — is it open in another program?");
            return;
        }

        if ($isNew) {
            fputcsv($fh, ['timestamp', 'file', 'reason']);
        }
        fputcsv($fh, [date('Y-m-d H:i:s'), $filePath, $reason]);
        fclose($fh);
    }

    public function logBatchResult(int $batchNumber, string $outputFile, int $fileCount, int $outputBytes, bool $success, string $error = ''): void
    {
        $csvFile = $this->logDir . DIRECTORY_SEPARATOR . 'batches.csv';
        $isNew = !file_exists($csvFile);

        $fh = fopen($csvFile, 'a');
        if ($fh === false) {
            error_log("Could not open batches.csv for writing — is it open in another program?");
            return;
        }

        if ($isNew) {
            fputcsv($fh, ['timestamp', 'batch_number', 'output_file', 'file_count', 'output_bytes', 'success', 'error']);
        }
        fputcsv($fh, [date('Y-m-d H:i:s'), $batchNumber, $outputFile, $fileCount, $outputBytes, $success ? '1' : '0', $error]);
        fclose($fh);
    }
}