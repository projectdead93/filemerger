<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\MergeController;
use App\Service\FileValidator;
use App\Service\PdfMerger;
use App\Service\Logger;
use App\Service\ProgressTracker;
use App\Service\BatchQueue;
use App\Service\FileScanner;
use App\Service\ProgressReporter;

$config = require __DIR__ . '/../config/config.php';

// Ensure storage dirs exist
foreach ([$config['temp_dir'], $config['output_dir'], $config['log_dir']] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// $logger = new Logger($config['log_dir']);
// $validator    = new FileValidator($logger);
// $progressTracker = new ProgressTracker($config['log_dir']);
// $merger       = new PdfMerger($config['qpdf_path'], $config['temp_dir'], $logger);
// $batchQueue = new BatchQueue($validator, $progressTracker, $config['max_bytes'], $config['max_files_per_batch']);
// $batchBuilder = new BatchBuilder($validator, $config['max_bytes']);
// $merger       = new PdfMerger($config['qpdf_path'], $config['temp_dir'], $logger);
// $reporter = new ProgressReporter($progressTracker);

$logger          = new Logger($config['log_dir']);
$validator       = new FileValidator($logger);
$progressTracker = new ProgressTracker($config['log_dir']);
$reporter        = new ProgressReporter($progressTracker);
$batchQueue      = new BatchQueue($validator, $progressTracker, $config['max_bytes']);
$merger          = new PdfMerger($config['qpdf_path'], $config['temp_dir'], $logger);

$controller   = new MergeController(
                    $batchQueue, 
                    $merger, 
                    $config['output_dir'], 
                    $logger, 
                    $progressTracker,
                    $reporter
                    );

$sourceDir = $config['source_dir'];

echo "Scanning $sourceDir ...\n";
$scanner = new FileScanner();

// Count candidates first for the final summary (cheap enough at 200k with a generator)
$totalCandidates = iterator_count($scanner->scan($sourceDir));
echo "Found $totalCandidates files.\n\n";

$fileStream = $scanner->scan($sourceDir);
$controller->run($fileStream, 'batch');

$reporter->reportFinalSummary($totalCandidates);