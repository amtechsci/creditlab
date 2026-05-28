<?php
require_once __DIR__ . '/auth.php';

/**
 * Build an internal /zxc/ mailer URL (server-side use).
 */
function creditlab_zxc_mail_url(string $baseUrl, string $email, ?string $url = null, ?string $url2 = null, ?string $url3 = null): string
{
	$baseUrl = rtrim($baseUrl, '/');
	$parts = [];
	if ($url !== null && $url !== '') {
		$parts[] = 'url=' . rawurlencode($url);
	}
	if ($url2 !== null && $url2 !== '') {
		$parts[] = 'url2=' . rawurlencode($url2);
	}
	if ($url3 !== null && $url3 !== '') {
		$parts[] = 'url3=' . rawurlencode(creditlab_append_internal_token($url3));
	}
	$parts[] = 'email=' . rawurlencode($email);
	return $baseUrl . '/zxc/?' . implode('&', $parts) . creditlab_zxc_access_query();
}
