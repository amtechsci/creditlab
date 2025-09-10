<?php
require_once __DIR__ . '/../config_s3.php';

// Minimal AWS SigV4 signing for S3 REST requests (GET/PUT)

if (!function_exists('uploads_url')) {
	function uploads_url($objectName) {
		$objectName = ltrim($objectName, '/');
		$key = S3_PREFIX . $objectName;
		$encodedBucket = str_replace('%2F', '/', rawurlencode(S3_BUCKET));
		$encodedKey = str_replace('%2F', '/', rawurlencode($key));
		return 'https://s3.' . S3_REGION . '.amazonaws.com/' . $encodedBucket . '/' . $encodedKey;
	}
}

if (!function_exists('s3_build_canonical_request')) {
	function s3_build_canonical_request($method, $canonicalUri, $canonicalQueryString, $canonicalHeaders, $signedHeaders, $payloadHash) {
		return $method . "\n" . $canonicalUri . "\n" . $canonicalQueryString . "\n" . $canonicalHeaders . "\n\n" . $signedHeaders . "\n" . $payloadHash;
	}
}

if (!function_exists('s3_build_string_to_sign')) {
	function s3_build_string_to_sign($amzDate, $credentialScope, $canonicalRequest) {
		$hash = hash('sha256', $canonicalRequest);
		return 'AWS4-HMAC-SHA256' . "\n" . $amzDate . "\n" . $credentialScope . "\n" . $hash;
	}
}

if (!function_exists('s3_sign')) {
	function s3_sign($key, $dateStamp, $region, $service, $stringToSign) {
		$kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $key, true);
		$kRegion = hash_hmac('sha256', $region, $kDate, true);
		$kService = hash_hmac('sha256', $service, $kRegion, true);
		$kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
		return hash_hmac('sha256', $stringToSign, $kSigning);
	}
}

if (!function_exists('s3_request')) {
	function s3_request($method, $key, $headers = [], $body = '') {
		$service = 's3';
		// Use path-style for buckets with dots (like creditlab.in)
		$host = 's3.' . S3_REGION . '.amazonaws.com';
		$endpoint = 'https://' . $host . '/';
		$amzDate = gmdate('Ymd\THis\Z');
		$dateStamp = gmdate('Ymd');
		$canonicalUri = '/' . S3_BUCKET . '/' . str_replace('%2F', '/', rawurlencode($key));
		$canonicalQueryString = '';
		$payloadHash = hash('sha256', $body);
		$defaultHeaders = [
			'host' => $host,
			'x-amz-content-sha256' => $payloadHash,
			'x-amz-date' => $amzDate,
		];
		$allHeaders = array_change_key_case(array_merge($defaultHeaders, $headers), CASE_LOWER);
		ksort($allHeaders);
		$canonicalHeaders = '';
		$signedHeaderKeys = [];
		foreach ($allHeaders as $k => $v) {
			$canonicalHeaders .= $k . ':' . trim($v) . "\n";
			$signedHeaderKeys[] = $k;
		}
		$signedHeaders = implode(';', $signedHeaderKeys);
		$canonicalRequest = s3_build_canonical_request($method, $canonicalUri, $canonicalQueryString, $canonicalHeaders, $signedHeaders, $payloadHash);
		$credentialScope = $dateStamp . '/' . S3_REGION . '/' . $service . '/aws4_request';
		$stringToSign = s3_build_string_to_sign($amzDate, $credentialScope, $canonicalRequest);
		$signature = s3_sign(AWS_SECRET_ACCESS_KEY, $dateStamp, S3_REGION, $service, $stringToSign);
		$authorizationHeader = 'AWS4-HMAC-SHA256 Credential=' . AWS_ACCESS_KEY_ID . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;
		// Build header list for curl
		$curlHeaders = [];
		foreach ($allHeaders as $k => $v) {
			$curlHeaders[] = $k . ': ' . $v;
		}
		$curlHeaders[] = 'Authorization: ' . $authorizationHeader;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpoint . ltrim($canonicalUri, '/'));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		if (defined('S3_CURL_SKIP_SSL_VERIFY') && S3_CURL_SKIP_SSL_VERIFY) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		if ($method === 'PUT' || $method === 'POST') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		$response = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		curl_close($ch);
		if ($errno) {
			return [false, 0, [], $error];
		}
		$rawHeaders = substr($response, 0, $headerSize);
		$bodyOut = substr($response, $headerSize);
		$headersOut = [];
		foreach (explode("\r\n", $rawHeaders) as $line) {
			$pos = strpos($line, ':');
			if ($pos !== false) {
				$name = strtolower(trim(substr($line, 0, $pos)));
				$val = trim(substr($line, $pos + 1));
				$headersOut[$name] = $val;
			}
		}
		return [$httpCode >= 200 && $httpCode < 300, $httpCode, $headersOut, $bodyOut];
	}
}

if (!function_exists('s3_put_object_from_path')) {
	function s3_put_object_from_path($localPath, $destName, $contentType = 'application/octet-stream') {
		$key = S3_PREFIX . ltrim($destName, '/');
		$body = @file_get_contents($localPath);
		if ($body === false) return false;
		list($ok,) = s3_request('PUT', $key, [], $body);
		return $ok;
	}
}

if (!function_exists('s3_put_object_from_string')) {
	function s3_put_object_from_string($contents, $destName, $contentType = 'application/octet-stream') {
		$key = S3_PREFIX . ltrim($destName, '/');
		list($ok,) = s3_request('PUT', $key, [], $contents);
		return $ok;
	}
}

// Debug variants returning full response
if (!function_exists('s3_put_object_from_path_with_response')) {
	function s3_put_object_from_path_with_response($localPath, $destName, $contentType = 'application/octet-stream') {
		$key = S3_PREFIX . ltrim($destName, '/');
		$body = @file_get_contents($localPath);
		if ($body === false) return [false, 0, [], 'local file read failed'];
		return s3_request('PUT', $key, [], $body);
	}
}

if (!function_exists('s3_put_object_from_string_with_response')) {
	function s3_put_object_from_string_with_response($contents, $destName, $contentType = 'application/octet-stream') {
		$key = S3_PREFIX . ltrim($destName, '/');
		return s3_request('PUT', $key, [], $contents);
	}
}

if (!function_exists('s3_stream_object')) {
	function s3_stream_object($name) {
		$key = S3_PREFIX . ltrim($name, '/');
		list($ok, $code, $headers, $body) = s3_request('GET', $key);
		if (!$ok) return [false, $code, [], ''];
		return [true, $code, $headers, $body];
	}
}


