<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(
        array_merge([
            'ok' => false,
            'error' => $message,
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function successJson(array $data): never
{
    echo json_encode(
        array_merge([
            'ok' => true,
        ], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function cleanText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[^\P{C}\n\t]/u', '', $text) ?? $text;
    $text = preg_replace('/\n{5,}/', "\n\n\n\n", $text) ?? $text;

    return trim($text);
}

function safeOriginalName(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', $name) ?? 'file';
    $name = trim($name);

    return $name !== '' ? $name : 'file';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson('Method not allowed', 405);
}

if (!(bool) ($config['uploads']['enabled'] ?? false)) {
    failJson('Uploads are disabled', 403);
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    failJson('Missing uploaded file');
}

$file = $_FILES['file'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    failJson('Upload failed', 400, [
        'upload_error' => $file['error'] ?? null,
    ]);
}

$tmpName = (string) ($file['tmp_name'] ?? '');
$originalName = safeOriginalName((string) ($file['name'] ?? 'file'));

if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    failJson('Invalid uploaded file');
}

$maxBytes = (int) $config['uploads']['max_file_bytes'];
$fileSize = (int) ($file['size'] ?? 0);

if ($fileSize < 1) {
    failJson('Uploaded file is empty');
}

if ($fileSize > $maxBytes) {
    failJson('Uploaded file is too large', 413, [
        'max_file_bytes' => $maxBytes,
        'received_bytes' => $fileSize,
    ]);
}

$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExtensions = array_map('strtolower', (array) $config['uploads']['allowed_extensions']);

if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
    failJson('File extension is not allowed', 415, [
        'extension' => $extension,
        'allowed_extensions' => $allowedExtensions,
    ]);
}

$rawContent = file_get_contents($tmpName);

if ($rawContent === false) {
    failJson('Unable to read uploaded file', 500);
}

if (!mb_check_encoding($rawContent, 'UTF-8')) {
    $converted = @mb_convert_encoding($rawContent, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

    if (is_string($converted) && $converted !== '') {
        $rawContent = $converted;
    }
}

$text = cleanText($rawContent);

if ($text === '') {
    failJson('No readable text extracted from file');
}

$maxTextChars = (int) $config['uploads']['max_text_chars_per_file'];
$truncated = false;

if (mb_strlen($text, 'UTF-8') > $maxTextChars) {
    $text = mb_substr($text, 0, $maxTextChars, 'UTF-8');
    $truncated = true;
}

$storageDir = rtrim((string) $config['uploads']['storage_dir'], '/');

if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true)) {
    failJson('Unable to create upload storage directory', 500);
}

$fileId = bin2hex(random_bytes(16));
$storedOriginalPath = $storageDir . '/' . $fileId . '.raw';
$storedMetaPath = $storageDir . '/' . $fileId . '.json';

if (!move_uploaded_file($tmpName, $storedOriginalPath)) {
    failJson('Unable to store uploaded file', 500);
}

$meta = [
    'id' => $fileId,
    'original_name' => $originalName,
    'extension' => $extension,
    'size_bytes' => $fileSize,
    'stored_raw_path' => $storedOriginalPath,
    'text' => $text,
    'text_chars' => mb_strlen($text, 'UTF-8'),
    'truncated' => $truncated,
    'created_at' => date('c'),
];

if (file_put_contents(
    $storedMetaPath,
    json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n",
    LOCK_EX
) === false) {
    @unlink($storedOriginalPath);
    failJson('Unable to store upload metadata', 500);
}

chmod($storedOriginalPath, 0640);
chmod($storedMetaPath, 0640);

successJson([
    'file' => [
        'id' => $fileId,
        'original_name' => $originalName,
        'extension' => $extension,
        'size_bytes' => $fileSize,
        'text_chars' => mb_strlen($text, 'UTF-8'),
        'truncated' => $truncated,
        'preview' => mb_substr($text, 0, 1200, 'UTF-8'),
    ],
]);
