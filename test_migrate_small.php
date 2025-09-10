<?php
require_once 'lib/s3_aws_sdk.php';

echo "Testing migration with first 5 files...\n\n";

$sourceDir = __DIR__ . '/zxc/uploads/';
$files = glob($sourceDir . '*');
$testFiles = array_slice($files, 0, 5); // First 5 files only

foreach ($testFiles as $filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    
    echo "Testing upload: $fileName (" . number_format($fileSize) . " bytes)... ";
    
    $s3 = new S3Helper();
    list($success, $result) = $s3->uploadFile($filePath, $fileName, 'application/pdf', true);
    
    if ($success) {
        echo "✅ Success\n";
        
        // Test download
        list($downloadSuccess, $content, $metadata) = s3_download_file($fileName);
        if ($downloadSuccess) {
            echo "  ✅ Download test successful\n";
        } else {
            echo "  ❌ Download test failed\n";
        }
        
    } else {
        echo "❌ Failed: " . $result . "\n";
    }
}

echo "\nTest complete! If successful, you can run the full migration.\n";
?>
