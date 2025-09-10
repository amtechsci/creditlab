<?php
require_once 'lib/s3_aws_sdk.php';

// Configuration
$batchSize = 50; // Process 50 files at a time
$startFrom = isset($argv[1]) ? (int)$argv[1] : 0;

echo "Starting batch migration of zxc/uploads/ files to S3...\n";
echo "Batch size: $batchSize\n";
echo "Starting from file: $startFrom\n\n";

$sourceDir = __DIR__ . '/zxc/uploads/';
$files = glob($sourceDir . '*');

if (empty($files)) {
    echo "No files found in zxc/uploads/\n";
    exit;
}

$totalFiles = count($files);
$filesToProcess = array_slice($files, $startFrom, $batchSize);
$actualBatchSize = count($filesToProcess);

echo "Total files available: $totalFiles\n";
echo "Processing files: " . ($startFrom + 1) . " to " . ($startFrom + $actualBatchSize) . "\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($filesToProcess as $index => $filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    $currentFile = $startFrom + $index + 1;
    
    echo "[$currentFile/$totalFiles] Uploading: $fileName (" . number_format($fileSize) . " bytes)... ";
    
    // Upload to S3 with ZXC prefix
    $s3 = new S3Helper();
    list($success, $result) = $s3->uploadFile($filePath, $fileName, 'application/pdf', true);
    
    if ($success) {
        echo "✅ Success\n";
        $successCount++;
        
        // Optional: Delete local file after successful upload
        // unlink($filePath);
        
    } else {
        echo "❌ Failed: " . $result . "\n";
        $errorCount++;
        $errors[] = "$fileName: $result";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "BATCH COMPLETE\n";
echo str_repeat("=", 50) . "\n";
echo "Files processed: $actualBatchSize\n";
echo "Successfully uploaded: $successCount\n";
echo "Failed: $errorCount\n";

if (!empty($errors)) {
    echo "\nErrors in this batch:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

$nextStart = $startFrom + $actualBatchSize;
if ($nextStart < $totalFiles) {
    echo "\nTo continue with next batch, run:\n";
    echo "php migrate_zxc_batch.php $nextStart\n";
} else {
    echo "\n🎉 All files have been processed!\n";
}
?>
