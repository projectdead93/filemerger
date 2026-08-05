<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\MergeController;
use App\Service\BatchQueue;
use App\Service\FileScanner;
use App\Service\FileValidator;
use App\Service\Logger;
use App\Service\PdfMerger;
use App\Service\ProgressReporter;
use App\Service\ProgressTracker;

[$scriptName, $jobId, $sourceDir, $outputDir] = $argv;

$baseConfig = require __DIR__ . '/../config/config.php';

$logDir = $baseConfig['log_dir'] . DIRECTORY_SEPARATOR . $jobId;
$tempDir = $baseConfig['temp_dir'] . DIRECTORY_SEPARATOR . $jobId;

foreach ([$logDir, $tempDir, $outputDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$logger          = new Logger($logDir);
$validator       = new FileValidator($logger);
$progressTracker = new ProgressTracker($logDir);
$reporter        = new ProgressReporter($progressTracker);
$batchQueue      = new BatchQueue($validator, $progressTracker, $baseConfig['max_bytes']);
$merger          = new PdfMerger($baseConfig['qpdf_path'], $tempDir, $logger);

$controller = new MergeController($batchQueue, $merger, $outputDir, $logger, $progressTracker, $reporter);

$scanner = new FileScanner();
$fileStream = $scanner->scan($sourceDir);

$controller->run($fileStream, 'merged');