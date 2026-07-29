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
        if (empty($files) || empty($userOutputDir)) {
            http_response_code(400);
            echo "Missing files or output directory.";
            return;
        }

        if (!is_dir($userOutputDir)) {
            if (!mkdir($userOutputDir, 0777, true)) {
                http_response_code(400);
                echo "Could not create destination directory: $userOutputDir";
                return;
            }
        }

        $uploader = new UploadHandler($this->config['upload_dir']);
        $jobDir = $uploader->handle($files);
        $jobId = basename($jobDir);

        // Launch the merge as a background process, pointing at the uploaded files
        // and the user-specified output directory
        $this->launchBackgroundMerge($jobId, $jobDir, $userOutputDir);

        // Redirect user to a status page they can poll
        header("Location: /index.php?action=status&job_id=$jobId");
    }

    private function launchBackgroundMerge(string $jobId, string $sourceDir, string $outputDir): void
    {
        $phpBinary = PHP_BINARY; // path to php.exe currently running
        $script = realpath(__DIR__ . '/../../../bin/web_merge_worker.php');

        $cmd = sprintf(
            'start /B %s %s %s %s %s',
            escapeshellarg($phpBinary),
            escapeshellarg($script),
            escapeshellarg($jobId),
            escapeshellarg($sourceDir),
            escapeshellarg($outputDir)
        );

        // Windows: "start /B" runs detached without opening a new window
        pclose(popen('start /B ' . $cmd, 'r'));
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

        $path = __DIR__ . '/../../../resources/views/progress.php';
        echo realpath($path) ?: "NOT FOUND: $path";
    }
}