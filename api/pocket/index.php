<?php
/**
 * Pocket S3 bridge API for apps outside the CreditLab AWS account.
 *
 * Actions (query param action=):
 *   presign  GET  ?key=reports/jan.pdf&expires=3600
 *   download GET  ?key=reports/jan.pdf
 *   upload   POST multipart field "file", POST/GET key=reports/jan.pdf
 *   delete   POST/DELETE key=reports/jan.pdf
 *   list     GET  ?prefix=reports/  (optional, relative to pocket/)
 *
 * Auth: Authorization: Bearer <POCKET_API_TOKEN>  or  X-API-Key header
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../lib/pocket_api.php';
require_once __DIR__ . '/../../lib/s3_aws_sdk.php';

creditlab_pocket_api_require_auth();

$action = strtolower(trim((string) ($_GET['action'] ?? $_POST['action'] ?? 'presign')));
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

function pocket_json_error(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function pocket_json_ok(array $payload): void
{
    echo json_encode(array_merge(['ok' => true], $payload));
    exit;
}

$keyInput = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$relativeKey = creditlab_pocket_validate_key($keyInput);

switch ($action) {
    case 'presign':
        if ($method !== 'GET') {
            pocket_json_error(405, 'GET required');
        }
        if ($relativeKey === null) {
            pocket_json_error(400, 'Valid key required');
        }
        $expires = (int) ($_GET['expires'] ?? 3600);
        if ($expires < 60) {
            $expires = 60;
        }
        if ($expires > 604800) {
            $expires = 604800;
        }
        list($ok, $url) = s3_pocket_presign($relativeKey, '+' . $expires . ' seconds');
        if (!$ok) {
            pocket_json_error(404, (string) $url);
        }
        pocket_json_ok([
            'key' => $relativeKey,
            'url' => $url,
            'expires_in' => $expires,
        ]);
        break;

    case 'download':
        if ($method !== 'GET') {
            pocket_json_error(405, 'GET required');
        }
        if ($relativeKey === null) {
            pocket_json_error(400, 'Valid key required');
        }
        list($ok, $content, $meta) = s3_pocket_download($relativeKey);
        if (!$ok) {
            pocket_json_error(404, (string) $content);
        }
        $contentType = $meta['ContentType'] ?? 'application/octet-stream';
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . strlen((string) $content));
        header('Content-Disposition: inline; filename="' . basename($relativeKey) . '"');
        echo $content;
        exit;

    case 'upload':
        if (!in_array($method, ['POST', 'PUT'], true)) {
            pocket_json_error(405, 'POST or PUT required');
        }
        if ($relativeKey === null) {
            pocket_json_error(400, 'Valid key required');
        }
        if (empty($_FILES['file']['tmp_name'])) {
            pocket_json_error(400, 'Multipart file field "file" required');
        }
        $tmp = $_FILES['file']['tmp_name'];
        $contentType = $_FILES['file']['type'] ?? 'application/octet-stream';
        if ($contentType === '' || $contentType === 'application/octet-stream') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if ($detected) {
                    $contentType = $detected;
                }
            }
        }
        list($ok, $result) = s3_pocket_upload_file($tmp, $relativeKey, $contentType);
        if (!$ok) {
            pocket_json_error(500, (string) $result);
        }
        pocket_json_ok([
            'key' => $relativeKey,
            's3_key' => creditlab_pocket_s3_key($relativeKey),
        ]);
        break;

    case 'delete':
        if (!in_array($method, ['POST', 'DELETE'], true)) {
            pocket_json_error(405, 'POST or DELETE required');
        }
        if ($relativeKey === null) {
            pocket_json_error(400, 'Valid key required');
        }
        $s3 = new S3Helper();
        list($ok, $result) = $s3->deleteByKey(creditlab_pocket_s3_key($relativeKey));
        if (!$ok) {
            pocket_json_error(500, (string) $result);
        }
        pocket_json_ok(['key' => $relativeKey, 'deleted' => true]);
        break;

    case 'list':
        if ($method !== 'GET') {
            pocket_json_error(405, 'GET required');
        }
        $prefixInput = creditlab_pocket_validate_prefix((string) ($_GET['prefix'] ?? ''));
        if ($prefixInput === null) {
            pocket_json_error(400, 'Invalid prefix');
        }
        $s3 = new S3Helper();
        list($ok, $items) = $s3->listByPrefix(S3_POCKET_PREFIX . $prefixInput);
        if (!$ok) {
            pocket_json_error(500, (string) $items);
        }
        $relativeItems = [];
        $pocketPrefixLen = strlen(S3_POCKET_PREFIX);
        foreach ($items as $item) {
            $fullKey = $item['key'];
            $relativeItems[] = [
                'key' => substr($fullKey, $pocketPrefixLen),
                'size' => $item['size'],
                'last_modified' => $item['last_modified'],
            ];
        }
        pocket_json_ok(['prefix' => $prefixInput, 'items' => $relativeItems]);
        break;

    default:
        pocket_json_error(400, 'Unknown action. Use presign, download, upload, delete, or list.');
}
