<?php

namespace App\Service;

use RuntimeException;

class UploadHandler
{
    public function __construct(private string $uploadBaseDir) {}

    /**
     * @param array $files The $_FILES['files'] array (multi-file upload)
     * @return string The job-specific directory containing the uploaded files
     */
    public function handle(array $files): string
    {
        $jobId = uniqid('job_');
        $jobDir = $this->uploadBaseDir . DIRECTORY_SEPARATOR . $jobId;

        if (!mkdir($jobDir, 0777, true)) {
            throw new RuntimeException("Could not create upload directory: $jobDir");
        }

        $names = $files['name'] ?? [];
        $tmpPaths = $files['tmp_name'] ?? [];
        $errors = $files['error'] ?? [];

        foreach ($names as $i => $name) {
            if ($errors[$i] !== UPLOAD_ERR_OK) {
                continue; // log this via Logger in a real version
            }

            $safeName = basename($name); // strip any path info from filename
            $destination = $jobDir . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($tmpPaths[$i], $destination)) {
                throw new RuntimeException("Failed to move uploaded file: $name");
            }
        }

        return $jobDir;
    }
}