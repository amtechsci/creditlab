<?php
// S3 Upload Helper for file uploads
require_once __DIR__ . '/s3_aws_sdk.php';

if (!function_exists('s3_upload_file_from_upload')) {
    function s3_upload_file_from_upload($uploadedFile, $destName, $contentType = 'application/octet-stream') {
        // Get file content from uploaded file
        $fileContent = file_get_contents($uploadedFile);
        if ($fileContent === false) {
            return [false, 'Failed to read uploaded file'];
        }
        
        // Upload to S3
        list($success, $result) = s3_upload_string($fileContent, $destName, $contentType);
        
        if ($success) {
            return [true, 'File uploaded to S3 successfully'];
        } else {
            return [false, 'S3 upload failed: ' . $result];
        }
    }
}

if (!function_exists('s3_upload_multiple_files_from_upload')) {
    function s3_upload_multiple_files_from_upload($uploadedFiles, $destNames, $contentType = 'application/octet-stream') {
        $results = [];
        $allSuccess = true;
        
        foreach ($uploadedFiles as $index => $uploadedFile) {
            if (isset($destNames[$index])) {
                list($success, $message) = s3_upload_file_from_upload($uploadedFile, $destNames[$index], $contentType);
                $results[$index] = ['success' => $success, 'message' => $message];
                if (!$success) {
                    $allSuccess = false;
                }
            }
        }
        
        return [$allSuccess, $results];
    }
}

if (!function_exists('get_file_url')) {
    function get_file_url($filename) {
        // Return the proxy URL for accessing files
        return 'https://creditlab.in/user/file.php?f=' . urlencode($filename);
    }
}
?>
