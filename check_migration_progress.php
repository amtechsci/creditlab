<?php
require_once 'lib/s3_aws_sdk.php';

echo "📊 Migration Progress Checker\n";
echo str_repeat("=", 40) . "\n";

// Check local files
$sourceDir = __DIR__ . '/zxc/uploads/';
$localFiles = glob($sourceDir . '*');
$totalLocalFiles = count($localFiles);

echo "Local files remaining: " . number_format($totalLocalFiles) . "\n";

// Check S3 files (this might be slow for large numbers)
echo "Checking S3 files...\n";
try {
    require_once 'vendor/autoload.php';
    $s3Client = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => S3_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);
    
    $result = $s3Client->listObjects([
        'Bucket' => S3_BUCKET,
        'Prefix' => S3_ZXC_PREFIX,
        'MaxKeys' => 1000
    ]);
    
    $s3FileCount = count($result['Contents'] ?? []);
    echo "S3 files uploaded: " . number_format($s3FileCount) . "\n";
    
    if ($s3FileCount > 0) {
        $progress = round(($s3FileCount / 17275) * 100, 2);
        echo "Progress: $progress%\n";
        
        if ($s3FileCount >= 17275) {
            echo "🎉 Migration appears to be complete!\n";
        } else {
            $remaining = 17275 - $s3FileCount;
            echo "Remaining: " . number_format($remaining) . " files\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking S3: " . $e->getMessage() . "\n";
}

echo "\nTo check again, run: php check_migration_progress.php\n";
?>
