<?php
/**
 * Script to generate presigned URLs for existing report files
 * 
 * NOTE: This bucket does not support ACLs (Object Ownership is "Bucket owner enforced").
 * Instead, we use presigned URLs which provide temporary secure access.
 * 
 * This script demonstrates how to generate presigned URLs for existing reports.
 * The get_download_links.php page automatically generates presigned URLs when viewing.
 * 
 * Usage: php make_reports_public.php [report_id]
 *   - If report_id is provided, only that report's presigned URL will be shown
 *   - If no report_id, all reports with email_sent=0 will be shown
 */

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Include database connection
require_once __DIR__ . '/../db.php';

// Include S3 helper
require_once __DIR__ . '/../lib/s3_aws_sdk.php';
require_once __DIR__ . '/../config_s3.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Get report ID from command line if provided
$report_id = isset($argv[1]) ? (int)$argv[1] : null;

// Initialize S3 client (EC2 instance role when .env keys are empty)
$s3Client = new S3Client(s3_client_config());

echo "Generating Presigned URLs for S3 Report Files\n";
echo "==============================================\n";
echo "NOTE: This bucket does not support ACLs.\n";
echo "Presigned URLs provide secure temporary access (valid for 7 days).\n\n";

// Build query
if ($report_id) {
    $sql = "SELECT id, report_name, s3_key, s3_url FROM download_links WHERE id = $report_id LIMIT 1";
    echo "Updating report ID: $report_id\n";
} else {
    $sql = "SELECT id, report_name, s3_key, s3_url FROM download_links WHERE email_sent = 0 ORDER BY id DESC";
    echo "Updating all reports with email_sent=0\n";
}

$result = towquery($sql);

if (!$result) {
    echo "ERROR: Database query failed\n";
    exit(1);
}

$updated = 0;
$failed = 0;
$errors = [];

while ($row = towfetch($result)) {
    $id = $row['id'];
    $report_name = $row['report_name'];
    $s3_key = $row['s3_key'];
    $s3_url = $row['s3_url'];
    
    echo "Processing ID $id: $report_name\n";
    echo "  S3 Key: $s3_key\n";
    
    // Extract key from URL if s3_key doesn't have prefix
    $key = $s3_key;
    if (empty($key) || !strpos($key, '/')) {
        // Try to extract from URL
        if (preg_match('#/(uploads/.+)$#', $s3_url, $matches)) {
            $key = $matches[1];
        } else {
            echo "  ERROR: Could not determine S3 key\n";
            $failed++;
            $errors[] = "ID $id: Could not determine S3 key";
            continue;
        }
    }
    
    // Ensure key has proper prefix
    if (strpos($key, S3_PREFIX) !== 0) {
        $key = S3_PREFIX . ltrim($key, '/');
    }
    
    try {
        // Generate presigned URL (valid for 7 days)
        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => S3_BUCKET,
            'Key'    => $key
        ]);
        $request = $s3Client->createPresignedRequest($cmd, '+7 days');
        $presigned_url = (string) $request->getUri();
        
        echo "  ✓ Presigned URL generated (valid for 7 days)\n";
        echo "  URL: " . substr($presigned_url, 0, 100) . "...\n";
        $updated++;
        
    } catch (AwsException $e) {
        echo "  ✗ ERROR: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "ID $id: " . $e->getMessage();
    }
    
    echo "\n";
}

echo "=============================\n";
echo "Summary:\n";
echo "  Updated: $updated\n";
echo "  Failed: $failed\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\nDone!\n";

?>
