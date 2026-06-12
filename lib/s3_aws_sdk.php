<?php
require_once __DIR__ . '/../config_s3.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3Helper {
    private $s3Client;
    
    public function __construct() {
        $this->s3Client = new S3Client(s3_client_config());
    }

    public function getClient(): S3Client
    {
        return $this->s3Client;
    }

    private function resolvePrefix(string $area): string
    {
        if ($area === 'zxc') {
            return S3_ZXC_PREFIX;
        }
        if ($area === 'pocket') {
            return S3_POCKET_PREFIX;
        }
        return S3_PREFIX;
    }
    
    public function uploadFile($localPath, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false, $makePublic = false) {
        $area = $useZxcPrefix ? 'zxc' : 'uploads';
        return $this->uploadFileToArea($localPath, $destName, $contentType, $area, $makePublic);
    }

    public function uploadFileToArea($localPath, $destName, $contentType = 'application/octet-stream', string $area = 'uploads', $makePublic = false) {
        try {
            $prefix = $this->resolvePrefix($area);
            $key = $prefix . ltrim($destName, '/');
            $params = [
                'Bucket' => S3_BUCKET,
                'Key'    => $key,
                'SourceFile' => $localPath,
                'ContentType' => $contentType
            ];
            
            if ($makePublic) {
                $params['ACL'] = 'public-read';
            }
            
            $result = $this->s3Client->putObject($params);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function uploadString($contents, $destName, $contentType = 'application/octet-stream', $useZxcPrefix = false, $makePublic = false) {
        $area = $useZxcPrefix ? 'zxc' : 'uploads';
        return $this->uploadStringToArea($contents, $destName, $contentType, $area, $makePublic);
    }

    public function uploadStringToArea($contents, $destName, $contentType = 'application/octet-stream', string $area = 'uploads', $makePublic = false) {
        try {
            $prefix = $this->resolvePrefix($area);
            $key = $prefix . ltrim($destName, '/');
            $params = [
                'Bucket' => S3_BUCKET,
                'Key'    => $key,
                'Body'   => $contents,
                'ContentType' => $contentType
            ];
            
            if ($makePublic) {
                $params['ACL'] = 'public-read';
            }
            
            $result = $this->s3Client->putObject($params);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }

    public function downloadByKey(string $key) {
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => S3_BUCKET,
                'Key'    => $key
            ]);
            return [true, $result['Body']->getContents(), $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage(), null];
        }
    }
    
    public function downloadFile($name) {
        foreach (s3_all_prefixes() as $prefix) {
            try {
                $key = $prefix . ltrim($name, '/');
                $result = $this->s3Client->getObject([
                    'Bucket' => S3_BUCKET,
                    'Key'    => $key
                ]);
                return [true, $result['Body']->getContents(), $result];
            } catch (AwsException $e) {
                continue;
            }
        }
        
        return [false, 'File not found in any S3 prefix', null];
    }

    public function deleteByKey(string $key) {
        try {
            $result = $this->s3Client->deleteObject([
                'Bucket' => S3_BUCKET,
                'Key'    => $key
            ]);
            return [true, $result];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
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

    public function listByPrefix(string $prefix, int $maxKeys = 1000): array
    {
        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => S3_BUCKET,
                'Prefix' => $prefix,
                'MaxKeys' => $maxKeys,
            ]);
            $items = [];
            foreach ($result['Contents'] ?? [] as $object) {
                $key = (string) $object['Key'];
                if ($key === $prefix) {
                    continue;
                }
                $items[] = [
                    'key' => $key,
                    'size' => (int) ($object['Size'] ?? 0),
                    'last_modified' => isset($object['LastModified']) ? (string) $object['LastModified'] : null,
                ];
            }
            return [true, $items];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
    
    public function getFileUrl($name, $expiration = '+7 days') {
        $name_trimmed = ltrim($name, '/');
        $key = $name_trimmed;
        
        $knownPrefixes = s3_all_prefixes();
        $has_prefix = false;
        foreach ($knownPrefixes as $prefix) {
            if (strpos($name_trimmed, $prefix) === 0) {
                $has_prefix = true;
                break;
            }
        }

        if (!$has_prefix) {
            foreach ($knownPrefixes as $prefix) {
                try {
                    $test_key = $prefix . $name_trimmed;
                    $cmd = $this->s3Client->getCommand('GetObject', [
                        'Bucket' => S3_BUCKET,
                        'Key'    => $test_key
                    ]);
                    $request = $this->s3Client->createPresignedRequest($cmd, $expiration);
                    return [true, (string) $request->getUri()];
                } catch (AwsException $e) {
                    continue;
                }
            }
        }
        
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => S3_BUCKET,
                'Key'    => $key
            ]);
            $request = $this->s3Client->createPresignedRequest($cmd, $expiration);
            return [true, (string) $request->getUri()];
        } catch (AwsException $e) {
            return [false, 'File not found: ' . $e->getMessage()];
        }
    }

    public function getPresignedUrlForKey(string $key, $expiration = '+1 hour'): array
    {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => S3_BUCKET,
                'Key'    => $key
            ]);
            $request = $this->s3Client->createPresignedRequest($cmd, $expiration);
            return [true, (string) $request->getUri()];
        } catch (AwsException $e) {
            return [false, $e->getMessage()];
        }
    }
}

if (!function_exists('s3_upload_file')) {
    function s3_upload_file($localPath, $destName, $contentType = 'application/octet-stream', $makePublic = false) {
        $s3 = new S3Helper();
        return $s3->uploadFile($localPath, $destName, $contentType, false, $makePublic);
    }
}

if (!function_exists('s3_upload_string')) {
    function s3_upload_string($contents, $destName, $contentType = 'application/octet-stream', $makePublic = false) {
        $s3 = new S3Helper();
        return $s3->uploadString($contents, $destName, $contentType, false, $makePublic);
    }
}

if (!function_exists('s3_download_file')) {
    function s3_download_file($name) {
        $s3 = new S3Helper();
        return $s3->downloadFile($name);
    }
}

if (!function_exists('s3_get_file_url')) {
    function s3_get_file_url($name, $expiration = '+7 days') {
        $s3 = new S3Helper();
        return $s3->getFileUrl($name, $expiration);
    }
}

if (!function_exists('s3_pocket_upload_file')) {
    function s3_pocket_upload_file($localPath, $destName, $contentType = 'application/octet-stream') {
        $s3 = new S3Helper();
        return $s3->uploadFileToArea($localPath, $destName, $contentType, 'pocket');
    }
}

if (!function_exists('s3_pocket_upload_string')) {
    function s3_pocket_upload_string($contents, $destName, $contentType = 'application/octet-stream') {
        $s3 = new S3Helper();
        return $s3->uploadStringToArea($contents, $destName, $contentType, 'pocket');
    }
}

if (!function_exists('s3_pocket_download')) {
    function s3_pocket_download(string $relativeKey) {
        $s3 = new S3Helper();
        return $s3->downloadByKey(S3_POCKET_PREFIX . ltrim($relativeKey, '/'));
    }
}

if (!function_exists('s3_pocket_presign')) {
    function s3_pocket_presign(string $relativeKey, $expiration = '+1 hour') {
        $s3 = new S3Helper();
        return $s3->getPresignedUrlForKey(S3_POCKET_PREFIX . ltrim($relativeKey, '/'), $expiration);
    }
}
