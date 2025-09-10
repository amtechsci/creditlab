<?php
require_once 'lib/s3_aws_sdk.php';

echo "🚀 Starting FULL migration of all zxc/uploads/ files to S3...\n";
echo "Bucket: " . S3_BUCKET . "\n";
echo "Region: " . S3_REGION . "\n";
echo "Target: " . S3_ZXC_PREFIX . "\n\n";

$sourceDir = __DIR__ . '/zxc/uploads/';
$files = glob($sourceDir . '*');

if (empty($files)) {
    echo "❌ No files found in zxc/uploads/\n";
    exit;
}

$totalFiles = count($files);
$batchSize = 50;
$totalBatches = ceil($totalFiles / $batchSize);
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "📊 Migration Statistics:\n";
echo "Total files: " . number_format($totalFiles) . "\n";
echo "Batch size: $batchSize\n";
echo "Total batches: $totalBatches\n";
echo "Estimated time: " . round($totalBatches * 2) . " minutes\n\n";

$startTime = time();

for ($batch = 0; $batch < $totalBatches; $batch++) {
    $startFrom = $batch * $batchSize;
    $batchFiles = array_slice($files, $startFrom, $batchSize);
    $actualBatchSize = count($batchFiles);
    
    echo "📦 Processing batch " . ($batch + 1) . "/$totalBatches (files " . ($startFrom + 1) . "-" . ($startFrom + $actualBatchSize) . ")...\n";
    
    foreach ($batchFiles as $index => $filePath) {
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);
        $currentFile = $startFrom + $index + 1;
        
        echo "[$currentFile/$totalFiles] $fileName (" . number_format($fileSize) . " bytes)... ";
        
        // Upload to S3 with ZXC prefix
        $s3 = new S3Helper();
        list($success, $result) = $s3->uploadFile($filePath, $fileName, 'application/pdf', true);
        
        if ($success) {
            echo "✅\n";
            $successCount++;
        } else {
            echo "❌ $result\n";
            $errorCount++;
            $errors[] = "$fileName: $result";
        }
    }
    
    // Progress update
    $elapsed = time() - $startTime;
    $processed = $successCount + $errorCount;
    $remaining = $totalFiles - $processed;
    $avgTimePerFile = $elapsed / max($processed, 1);
    $eta = round(($remaining * $avgTimePerFile) / 60);
    
    echo "✅ Batch " . ($batch + 1) . " complete. Progress: $processed/$totalFiles files. ETA: {$eta}min\n\n";
    
    // Small delay to avoid overwhelming S3
    sleep(1);
}

$totalTime = time() - $startTime;

echo str_repeat("=", 60) . "\n";
echo "🎉 MIGRATION COMPLETE!\n";
echo str_repeat("=", 60) . "\n";
echo "Total files: " . number_format($totalFiles) . "\n";
echo "Successfully uploaded: " . number_format($successCount) . "\n";
echo "Failed: " . number_format($errorCount) . "\n";
echo "Success rate: " . round(($successCount / $totalFiles) * 100, 2) . "%\n";
echo "Total time: " . round($totalTime / 60, 2) . " minutes\n";

if (!empty($errors)) {
    echo "\n❌ Errors encountered:\n";
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "- $error\n";
    }
    if (count($errors) > 10) {
        echo "... and " . (count($errors) - 10) . " more errors\n";
    }
}

echo "\n📁 Files are now available at:\n";
echo "S3: s3://" . S3_BUCKET . "/" . S3_ZXC_PREFIX . "filename.pdf\n";
echo "Proxy: http://localhost/creditlab/user/file.php?f=filename.pdf\n";
echo "Legacy: http://localhost/creditlab/zxc/uploads/filename.pdf\n";

if ($errorCount == 0) {
    echo "\n🎯 All files migrated successfully! You can now delete the local zxc/uploads/ folder.\n";
} else {
    echo "\n⚠️  Some files failed to migrate. Check the errors above.\n";
}
?>

