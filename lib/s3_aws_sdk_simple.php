<?php
// Simplified S3 SDK for servers without composer
// This is a basic implementation - for production, use the full AWS SDK

require_once __DIR__ . '/../config_s3.php';

class SimpleS3Helper {
    private $accessKey;
    private $secretKey;
    private $region;
    private $bucket;
    
    public function __construct() {
        $this->accessKey = AWS_ACCESS_KEY_ID;
        $this->secretKey = AWS_SECRET_ACCESS_KEY;
        $this->region = S3_REGION;
        $this->bucket = S3_BUCKET;
    }
    
    public function uploadFile($localPath, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false) {
        try {
            $prefix = $useZxcPrefix ? S3_ZXC_PREFIX : S3_PREFIX;
            $key = $prefix . ltrim($destName, '/');
            
            // For now, just copy file to local directory
            // In production, implement actual S3 upload
            $uploadDir = __DIR__ . '/../user/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $destPath = $uploadDir . basename($destName);
            if (copy($localPath, $destPath)) {
                return [true, ['ObjectURL' => 'https://' . s3_endpoint_host() . '/' . $key]];
            } else {
                return [false, 'Failed to copy file'];
            }
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function uploadString($contents, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false) {
        try {
            $prefix = $useZxcPrefix ? S3_ZXC_PREFIX : S3_PREFIX;
            $key = $prefix . ltrim($destName, '/');
            
            // For now, just save to local directory
            $uploadDir = __DIR__ . '/../user/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $destPath = $uploadDir . basename($destName);
            if (file_put_contents($destPath, $contents)) {
                return [true, ['ObjectURL' => 'https://' . s3_endpoint_host() . '/' . $key]];
            } else {
                return [false, 'Failed to save file'];
            }
        } catch (Exception $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function downloadFile($name) {
        // Try to find file in local uploads directory
        $uploadDir = __DIR__ . '/../user/uploads/';
        $localPath = $uploadDir . basename($name);
        
        if (file_exists($localPath)) {
            return [true, file_get_contents($localPath), ['ContentType' => 'application/octet-stream']];
        }
        
        return [false, 'File not found', null];
    }
    
    public function getFileUrl($name) {
        // Return local file URL for now
        $uploadDir = '/user/uploads/';
        $localPath = $uploadDir . basename($name);
        return [true, 'https://testing.creditlab.in' . $localPath];
    }
}

// Global helper functions
if (!function_exists('s3_upload_file')) {
    function s3_upload_file($localPath, $destName, $contentType = 'application/octet-stream') {
        $s3 = new SimpleS3Helper();
        return $s3->uploadFile($localPath, $destName, $contentType);
    }
}

if (!function_exists('s3_upload_string')) {
    function s3_upload_string($contents, $destName, $contentType = 'application/octet-stream') {
        $s3 = new SimpleS3Helper();
        return $s3->uploadString($contents, $destName, $contentType);
    }
}

if (!function_exists('s3_download_file')) {
    function s3_download_file($name) {
        $s3 = new SimpleS3Helper();
        return $s3->downloadFile($name);
    }
}

if (!function_exists('s3_get_file_url')) {
    function s3_get_file_url($name) {
        $s3 = new SimpleS3Helper();
        return $s3->getFileUrl($name);
    }
}
?>

