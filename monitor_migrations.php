<?php
require_once 'lib/s3_aws_sdk.php';

echo "📊 MIGRATION PROGRESS MONITOR\n";
echo str_repeat("=", 50) . "\n";

// Check ZXC migration progress
echo "📁 ZXC UPLOADS MIGRATION:\n";
$zxcSourceDir = __DIR__ . '/zxc/uploads/';
$zxcLocalFiles = glob($zxcSourceDir . '*');
$zxcTotalLocal = count($zxcLocalFiles);

echo "Local files remaining: " . number_format($zxcTotalLocal) . "\n";

// Check S3 ZXC files
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
    
    // Count ZXC files
    $zxcResult = $s3Client->listObjects([
        'Bucket' => S3_BUCKET,
        'Prefix' => S3_ZXC_PREFIX,
        'MaxKeys' => 1000
    ]);
    $zxcS3Count = count($zxcResult['Contents'] ?? []);
    echo "S3 files uploaded: " . number_format($zxcS3Count) . "\n";
    
    if ($zxcS3Count > 0) {
        $zxcProgress = round(($zxcS3Count / 17275) * 100, 2);
        echo "Progress: $zxcProgress%\n";
        
        if ($zxcS3Count >= 17275) {
            echo "🎉 ZXC migration complete!\n";
        } else {
            $zxcRemaining = 17275 - $zxcS3Count;
            echo "Remaining: " . number_format($zxcRemaining) . " files\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking ZXC S3: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 30) . "\n";

// Check User uploads migration progress
echo "📁 USER UPLOADS MIGRATION:\n";
$userSourceDir = __DIR__ . '/user/uploads/';
$userLocalFiles = glob($userSourceDir . '*');
$userTotalLocal = count($userLocalFiles);

echo "Local files remaining: " . number_format($userTotalLocal) . "\n";

// Check S3 User files
try {
    $userResult = $s3Client->listObjects([
        'Bucket' => S3_BUCKET,
        'Prefix' => S3_PREFIX,
        'MaxKeys' => 1000
    ]);
    $userS3Count = count($userResult['Contents'] ?? []);
    echo "S3 files uploaded: " . number_format($userS3Count) . "\n";
    
    if ($userS3Count > 0) {
        echo "User uploads in S3: " . number_format($userS3Count) . " files\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking User S3: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Check running processes
echo "🔄 RUNNING PROCESSES:\n";
$processes = shell_exec('powershell -Command "Get-Process php -ErrorAction SilentlyContinue | Select-Object Id, ProcessName, CPU | Format-Table -AutoSize"');
if ($processes) {
    echo $processes;
} else {
    echo "No PHP processes found\n";
}

echo "\n📋 SUMMARY:\n";
echo "• ZXC Migration: " . ($zxcS3Count >= 17275 ? "✅ Complete" : "🔄 In Progress") . "\n";
echo "• User Migration: " . ($userS3Count > 0 ? "🔄 In Progress" : "⏳ Starting") . "\n";
echo "• Total S3 Files: " . number_format(($zxcS3Count ?? 0) + ($userS3Count ?? 0)) . "\n";

echo "\nTo check again, run: php monitor_migrations.php\n";
?>

