<!DOCTYPE html>
<html>
<head>
    <title>Merge Progress</title>
    <meta http-equiv="refresh" content="3">
</head>
<body>
    <h1>Job: <?= htmlspecialchars($jobId) ?></h1>

    <?php if (file_exists($batchesFile)): ?>
        <table border="1" cellpadding="5">
            <tr><th>Batch</th><th>File Count</th><th>Size (MB)</th><th>Status</th></tr>
            <?php
            $rows = array_map('str_getcsv', file($batchesFile));
            $header = array_shift($rows);
            foreach ($rows as $row):
                [$timestamp, $batchNum, $output, $fileCount, $bytes, $success, $error] = $row;
            ?>
            <tr>
                <td><?= htmlspecialchars($batchNum) ?></td>
                <td><?= htmlspecialchars($fileCount) ?></td>
                <td><?= round($bytes / 1024 / 1024, 2) ?></td>
                <td><?= $success === '1' ? '✅ Done' : '❌ Failed' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Job starting...</p>
    <?php endif; ?>
</body>
</html>