<?php
require_once 'lib/s3_aws_sdk.php';

echo "Starting migration of zxc/uploads/ files to S3...\n";
echo "Bucket: " . S3_BUCKET . "\n";
echo "Region: " . S3_REGION . "\n";
echo "Prefix: " . S3_PREFIX . "\n\n";

$sourceDir = __DIR__ . '/zxc/uploads/';
$files = glob($sourceDir . '*');

if (empty($files)) {
    echo "No files found in zxc/uploads/\n";
    exit;
}

$totalFiles = count($files);
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "Found $totalFiles files to migrate\n\n";

foreach ($files as $filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    
    echo "Uploading: $fileName (" . number_format($fileSize) . " bytes)... ";
    
    // Upload to S3
    list($success, $result) = s3_upload_file($filePath, $fileName, 'application/pdf');
    
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
    
    // Progress indicator
    if (($successCount + $errorCount) % 100 == 0) {
        echo "\n--- Progress: " . ($successCount + $errorCount) . "/$totalFiles files processed ---\n\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "MIGRATION COMPLETE\n";
echo str_repeat("=", 50) . "\n";
echo "Total files: $totalFiles\n";
echo "Successfully uploaded: $successCount\n";
echo "Failed: $errorCount\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

echo "\nFiles are now available at:\n";
echo "- S3: s3://" . S3_BUCKET . "/" . S3_PREFIX . "filename.pdf\n";
echo "- Proxy: http://localhost/creditlab/user/file.php?f=filename.pdf\n";
echo "- Legacy: http://localhost/creditlab/user/uploads/filename.pdf\n";
?>
