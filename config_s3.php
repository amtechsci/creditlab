<?php
require_once __DIR__ . '/lib/env.php';

// Bucket and region
define('S3_BUCKET', env('S3_BUCKET', 'creditlab.in'));
define('S3_REGION', env('S3_REGION', 'ap-south-1'));

// Object key prefixes inside the bucket
define('S3_PREFIX', 'uploads/');
define('S3_ZXC_PREFIX', env('S3_ZXC_PREFIX', 'zxc/uploads/'));
define('S3_POCKET_PREFIX', 'pocket/');

define('AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID'));
define('AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY'));

define('S3_OBJECTS_ARE_PRIVATE', true);
define('UPLOADS_KEEP_LOCAL_COPY', true);

// Only enable via .env for local dev when cURL lacks CA bundle (insecure)
define('S3_CURL_SKIP_SSL_VERIFY', env_bool('S3_CURL_SKIP_SSL_VERIFY', false));

if (!function_exists('s3_client_config')) {
	/**
	 * AWS SDK client options. On EC2 with an instance role, omit keys from .env.
	 */
	function s3_client_config(): array
	{
		$config = [
			'version' => 'latest',
			'region'  => S3_REGION,
		];
		if (AWS_ACCESS_KEY_ID !== '' && AWS_SECRET_ACCESS_KEY !== '') {
			$config['credentials'] = [
				'key'    => AWS_ACCESS_KEY_ID,
				'secret' => AWS_SECRET_ACCESS_KEY,
			];
		}
		return $config;
	}
}

if (!function_exists('s3_all_prefixes')) {
	function s3_all_prefixes(): array
	{
		return [S3_PREFIX, S3_ZXC_PREFIX, S3_POCKET_PREFIX];
	}
}

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
