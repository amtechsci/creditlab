<?php
/**
 * HTTP helpers with strict timeouts (prevents PHP-FPM worker starvation).
 */

function creditlab_http_get(string $url, int $timeoutSeconds = 10): ?string
{
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 3,
		CURLOPT_CONNECTTIMEOUT => min(5, $timeoutSeconds),
		CURLOPT_TIMEOUT => $timeoutSeconds,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
	]);
	$response = curl_exec($curl);
	if ($response === false) {
		error_log('creditlab_http_get failed: ' . curl_error($curl) . ' url=' . $url);
	}
	curl_close($curl);
	return $response === false ? null : $response;
}

/**
 * Trigger internal mail/PDF jobs without blocking the caller for the full SMTP+PDF pipeline.
 */
function creditlab_zxc_mail_trigger(string $url, int $timeoutSeconds = 3): void
{
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 2,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => $timeoutSeconds,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_NOSIGNAL => true,
	]);
	$response = curl_exec($curl);
	if ($response === false) {
		error_log('creditlab_zxc_mail_trigger: ' . curl_error($curl) . ' url=' . $url);
	}
	curl_close($curl);
}
