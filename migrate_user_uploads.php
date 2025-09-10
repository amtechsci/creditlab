<?php
require_once 'lib/s3_aws_sdk.php';

echo "🚀 Starting migration of user/uploads/ files to S3...\n";
echo "Bucket: " . S3_BUCKET . "\n";
echo "Region: " . S3_REGION . "\n";
echo "Target: " . S3_PREFIX . "\n\n";

$sourceDir = __DIR__ . '/user/uploads/';
$files = glob($sourceDir . '*');

if (empty($files)) {
    echo "❌ No files found in user/uploads/\n";
    exit;
}

$totalFiles = count($files);
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "📊 Migration Statistics:\n";
echo "Total files: " . number_format($totalFiles) . "\n";

foreach ($files as $filePath) {
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Skip ZIP files - only upload individual media files
    if ($fileExt === 'zip') {
        echo "⏭️  Skipping ZIP file: $fileName (not uploading ZIP files)\n";
        continue;
    }
    
    // Only upload common media file types
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'wmv', 'pdf', 'doc', 'docx', 'txt'];
    if (!in_array($fileExt, $allowedTypes)) {
        echo "⏭️  Skipping unsupported file type: $fileName ($fileExt)\n";
        continue;
    }
    
    echo "📁 Processing: $fileName (" . number_format($fileSize) . " bytes)... ";
    
    // Determine content type
    $contentType = 'application/octet-stream';
    switch ($fileExt) {
        case 'jpg':
        case 'jpeg':
            $contentType = 'image/jpeg';
            break;
        case 'png':
            $contentType = 'image/png';
            break;
        case 'gif':
            $contentType = 'image/gif';
            break;
        case 'mp4':
            $contentType = 'video/mp4';
            break;
        case 'avi':
            $contentType = 'video/x-msvideo';
            break;
        case 'mov':
            $contentType = 'video/quicktime';
            break;
        case 'wmv':
            $contentType = 'video/x-ms-wmv';
            break;
        case 'pdf':
            $contentType = 'application/pdf';
            break;
        case 'doc':
            $contentType = 'application/msword';
            break;
        case 'docx':
            $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            break;
        case 'txt':
            $contentType = 'text/plain';
            break;
    }
    
    // Upload file to S3
    $s3 = new S3Helper();
    list($success, $result) = $s3->uploadFile($filePath, $fileName, $contentType, false);
    
    if ($success) {
        echo "✅\n";
        $successCount++;
    } else {
        echo "❌ $result\n";
        $errorCount++;
        $errors[] = "$fileName: $result";
    }
}

echo str_repeat("=", 60) . "\n";
echo "🎉 USER UPLOADS MIGRATION COMPLETE!\n";
echo str_repeat("=", 60) . "\n";
echo "Files processed: " . number_format($totalFiles) . "\n";
echo "Successfully uploaded: " . number_format($successCount) . "\n";
echo "Failed: " . number_format($errorCount) . "\n";

if ($successCount > 0) {
    echo "Success rate: " . round(($successCount / max($successCount + $errorCount, 1)) * 100, 2) . "%\n";
}

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
echo "S3: s3://" . S3_BUCKET . "/" . S3_PREFIX . "filename\n";
echo "Proxy: http://localhost/creditlab/user/file.php?f=filename\n";
echo "Legacy: http://localhost/creditlab/user/uploads/filename\n";

if ($errorCount == 0) {
    echo "\n🎯 All user uploads migrated successfully!\n";
} else {
    echo "\n⚠️  Some files failed to migrate. Check the errors above.\n";
}
?>
