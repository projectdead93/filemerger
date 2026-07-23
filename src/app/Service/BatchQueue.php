<?php

namespace App\Service;
use App\Service\ProgressTracker;

class BatchQueue
{
    public function __construct(
        private FileValidator $validator,
        private ProgressTracker $progressTracker,
        private int $maxBytes,
        private int $maxFilesPerBatch = 1000
    ) {}

    /**
     * @param \Generator<string> $fileStream
     * @return \Generator<string[]>
     */
    public function generateBatches(\Generator $fileStream): \Generator
    {
        $batch = [];
        $total = 0;

        foreach ($fileStream as $file) {
            if ($this->progressTracker->isProcessed($file)) {
                continue; // skip already processed files
            }
            
            if (!$this->validator->isRealPdf($file)) {
                continue; // log rejection here or upstream
            }

            $size = filesize($file);

            $wouldExceedSize  = ($total + $size) > $this->maxBytes;
            $wouldExceedCount = count($batch) >= $this->maxFilesPerBatch;

            if ($wouldExceedSize || $wouldExceedCount) {
                yield $batch;
                $batch = [];
                $total = 0;
            }

            $batch[] = $file;
            $total += $size;
        }

        if (!empty($batch)) {
            yield $batch; // final partial batch
        }
    }
}