<?php
// S3 configuration for uploads bridge
// Keep credentials out of VCS in production. For now, stored locally for integration.

// Bucket and region
define('S3_BUCKET', 'creditlab.in');
define('S3_REGION', 'ap-south-1');

// Object key prefixes inside the bucket
define('S3_PREFIX', 'uploads/');                    // For new uploads
define('S3_ZXC_PREFIX', 'zxc/uploads/');           // For existing zxc files

// Credentials from environment variables (more secure)
define('AWS_ACCESS_KEY_ID', $_ENV['AWS_ACCESS_KEY_ID'] ?? '');
define('AWS_SECRET_ACCESS_KEY', $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '');

// Behavior flags
// If true, the proxy will attempt to stream from S3 using signed requests (objects may be private)
define('S3_OBJECTS_ARE_PRIVATE', true);

// If true, when we later wire writes, we can also keep local copies during transition
define('UPLOADS_KEEP_LOCAL_COPY', true);

// LOCAL DEV ONLY: skip SSL verification if your PHP cURL lacks CA bundle (INSECURE)
define('S3_CURL_SKIP_SSL_VERIFY', true);

// Base endpoint helpers
if (!function_exists('s3_endpoint_host')) {
	function s3_endpoint_host() {
		return S3_BUCKET . '.s3.' . S3_REGION . '.amazonaws.com';
	}
}

if (!function_exists('s3_base_url')) {
	function s3_base_url() {
		return 'https://' . s3_endpoint_host() . '/';
	}
}


