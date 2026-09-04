<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\WebMergeController;

$config = require __DIR__ . '/../config/config.php';
$controller = new WebMergeController($config);

$action = $_GET['action'] ?? 'form';

foreach ([$config['temp_dir'], $config['output_dir'], $config['log_dir'], $config['upload_dir']] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

switch ($action) {
    case 'upload':
        $controller->handleUpload($_FILES['files'] ?? [], $_POST['output_dir'] ?? '');
        break;
    case 'status':
        $controller->showStatus($_GET['job_id'] ?? '');
        break;
    default:
        require __DIR__ . '/../resources/views/upload_form.php';
        break;
}