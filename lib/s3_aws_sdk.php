<?php
require_once __DIR__ . '/../config_s3.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Helper {
    private $s3Client;
    
    public function __construct() {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => S3_REGION,
            'credentials' => [
                'key'    => AWS_ACCESS_KEY_ID,
                'secret' => AWS_SECRET_ACCESS_KEY,
            ],
        ]);
    }
    
    public function uploadFile($localPath, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false) {
        try {
            $prefix = $useZxcPrefix ? S3_ZXC_PREFIX : S3_PREFIX;
            $key = $prefix . ltrim($destName, '/');
            $result = $this->s3Client->putObject([
                'Bucket' => S3_BUCKET,
                'Key'    => $key,
                'SourceFile' => $localPath,
                'ContentType' => $contentType
            ]);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function uploadString($contents, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false) {
        try {
            $prefix = $useZxcPrefix ? S3_ZXC_PREFIX : S3_PREFIX;
            $key = $prefix . ltrim($destName, '/');
            $result = $this->s3Client->putObject([
                'Bucket' => S3_BUCKET,
                'Key'    => $key,
                'Body'   => $contents,
                'ContentType' => $contentType
            ]);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function downloadFile($name) {
        // Try uploads/ first, then zxc/uploads/
        $prefixes = [S3_PREFIX, S3_ZXC_PREFIX];
        
        foreach ($prefixes as $prefix) {
            try {
                $key = $prefix . ltrim($name, '/');
                $result = $this->s3Client->getObject([
                    'Bucket' => S3_BUCKET,
                    'Key'    => $key
                ]);
                return [true, $result['Body']->getContents(), $result];
            } catch (AwsException $e) {
                // Continue to next prefix if this one fails
                continue;
            }
        }
        
        return [false, 'File not found in any S3 prefix', null];
    }
    
    public function deleteFile($name) {
        try {
            $key = S3_PREFIX . ltrim($name, '/');
            $result = $this->s3Client->deleteObject([
                'Bucket' => S3_BUCKET,
                'Key'    => $key
            ]);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function getFileUrl($name) {
        // Try uploads/ first, then zxc/uploads/
        $prefixes = [S3_PREFIX, S3_ZXC_PREFIX];
        
        foreach ($prefixes as $prefix) {
            try {
                $key = $prefix . ltrim($name, '/');
                $cmd = $this->s3Client->getCommand('GetObject', [
                    'Bucket' => S3_BUCKET,
                    'Key'    => $key
                ]);
                $request = $this->s3Client->createPresignedRequest($cmd, '+1 hour');
                return [true, (string) $request->getUri()];
            } catch (AwsException $e) {
                // Continue to next prefix if this one fails
                continue;
            }
        }
        
        return [false, 'File not found in any S3 prefix'];
    }
}

// Global helper functions
if (!function_exists('s3_upload_file')) {
    function s3_upload_file($localPath, $destName, $contentType = 'application/octet-stream') {
        $s3 = new S3Helper();
        return $s3->uploadFile($localPath, $destName, $contentType);
    }
}

if (!function_exists('s3_upload_string')) {
    function s3_upload_string($contents, $destName, $contentType = 'application/octet-stream') {
        $s3 = new S3Helper();
        return $s3->uploadString($contents, $destName, $contentType);
    }
}

if (!function_exists('s3_download_file')) {
    function s3_download_file($name) {
        $s3 = new S3Helper();
        return $s3->downloadFile($name);
    }
}

if (!function_exists('s3_get_file_url')) {
    function s3_get_file_url($name) {
        $s3 = new S3Helper();
        return $s3->getFileUrl($name);
    }
}
?>
