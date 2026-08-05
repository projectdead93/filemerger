<?php

namespace App\Controller;

use App\Service\BatchQueue;
use App\Service\FileScanner;
use App\Service\FileValidator;
use App\Service\Logger;
use App\Service\PdfMerger;
use App\Service\ProgressReporter;
use App\Service\ProgressTracker;
use App\Service\UploadHandler;

class WebMergeController
{
    public function __construct(private array $config) {}

    public function handleUpload(array $files, string $userOutputDir): void
    {
        $t0 = microtime(true);
        $debugLog = $this->config['log_dir'] . DIRECTORY_SEPARATOR . 'upload_timing_debug.log';

        if (!is_dir($this->config['log_dir'])) {
            mkdir($this->config['log_dir'], 0777, true);
        }

        $log = fn($label) => file_put_contents($debugLog, sprintf("%s: %.3fs\n", $label, microtime(true) - $t0), FILE_APPEND);
        $log('start');

        if (empty($files) || empty($userOutputDir)) {
            http_response_code(400);
            echo "Missing files or output directory.";
            return;
        }
        $log('after validation');

        if (!is_dir($userOutputDir)) {
            if (!mkdir($userOutputDir, 0777, true)) {
                http_response_code(400);
                echo "Could not create destination directory: $userOutputDir";
                return;
            }
        }
        $log('after mkdir output dir');

        $uploader = new UploadHandler($this->config['upload_dir']);
        $jobDir = $uploader->handle($files);
        $log('after UploadHandler::handle');

        $jobId = basename($jobDir);
        $log('after basename jobDir');

        $this->launchBackgroundMerge($jobId, $jobDir, $userOutputDir);
        $log('after launchBackgroundMerge');

        header("Location: /index.php?action=status&job_id=$jobId");
        $log('after header redirect');

    }

    private function launchBackgroundMerge(string $jobId, string $sourceDir, string $outputDir): void
    {
        $phpBinary = PHP_BINARY; // path to php.exe currently running
        $script = realpath(__DIR__ . '/../../../bin/web_merge_worker.php');

         $errorLog = $this->config['log_dir'] . DIRECTORY_SEPARATOR . "worker_error_{$jobId}.log";

    $innerCmd = sprintf(
        '%s %s %s %s %s > %s 2>&1',
        escapeshellarg($phpBinary),
        escapeshellarg($script),
        escapeshellarg($jobId),
        escapeshellarg($sourceDir),
        escapeshellarg($outputDir),
        escapeshellarg($errorLog)
    );

         $cmd = 'cmd /c "' . $innerCmd . '"';

        // Windows: "start /B" runs detached without opening a new window
        $shell = new \COM('WScript.Shell');
        $shell->Run($cmd, 0, false);
    }

    public function showStatus(string $jobId): void
    {
        if (empty($jobId)) {
            echo "No job specified.";
            return;
        }

        $logDir = $this->config['log_dir'] . DIRECTORY_SEPARATOR . $jobId;
        $batchesFile = $logDir . DIRECTORY_SEPARATOR . 'batches.csv';

        require __DIR__ . '/../../../resources/views/progress.php';
    }
}