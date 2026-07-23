<?php

namespace App\Service;

class BatchBuilder
{
    public function __construct(
        private FileValidator $validator,
        private int $maxBytes
    ) {}

    /**
     * @param string[] $candidateFiles
     * @return string[]
     */
    public function build(array $candidateFiles): array
    {
        $batch = [];
        $total = 0;

        foreach ($candidateFiles as $file) {
            if (!$this->validator->isRealPdf($file)) {
                continue;
            }
            $size = filesize($file);
            if ($total + $size > $this->maxBytes) {
                break;
            }
            $batch[] = $file;
            $total += $size;
        }

        return $batch;
    }
}