<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function jsonOut(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function loadPhpArrayFile(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

function normalizeRepoId(string $repoId): string
{
    $repoId = trim($repoId);

    if ($repoId === '' || !preg_match('/^[A-Za-z0-9._-]{1,120}$/', $repoId)) {
        return '';
    }

    return $repoId;
}

function normalizeRepoFilePath(string $filePath): string
{
    $filePath = trim(str_replace('\\', '/', $filePath));

    if ($filePath === '' || str_starts_with($filePath, '/') || str_contains($filePath, "\0")) {
        return '';
    }

    foreach (explode('/', $filePath) as $part) {
        if ($part === '' || $part === '.' || $part === '..' || $part === '.git') {
            return '';
        }
    }

    return $filePath;
}

function isPathInside(string $baseDir, string $candidate): bool
{
    $baseReal = realpath($baseDir);
    $candidateReal = realpath($candidate);

    if ($baseReal === false || $candidateReal === false) {
        return false;
    }

    $baseReal = rtrim($baseReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $candidateReal = rtrim($candidateReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    return str_starts_with($candidateReal, $baseReal);
}

function isLikelyBinary(string $path): bool
{
    $sample = file_get_contents($path, false, null, 0, 4096);

    if ($sample === false) {
        return true;
    }

    return str_contains($sample, "\0");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut([
        'ok' => false,
        'error' => 'Method not allowed',
    ], 405);
}

$configPath = __DIR__ . '/../../app/git-rag.config.php';
$reposPath = __DIR__ . '/../../app/git-rag.repos.php';

if (!is_file($configPath) || !is_readable($configPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'GIT-RAG config unavailable',
    ], 500);
}

$config = require $configPath;

if (!is_array($config)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid GIT-RAG config',
    ], 500);
}

$configRepos = is_array($config['repos'] ?? null) ? $config['repos'] : [];
$repos = array_merge($configRepos, loadPhpArrayFile($reposPath));

$data = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($data)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid JSON payload',
    ], 400);
}

$repoId = normalizeRepoId((string) ($data['repo'] ?? ''));
$filePath = normalizeRepoFilePath((string) ($data['path'] ?? ''));
$content = (string) ($data['content'] ?? '');

if ($repoId === '') {
    jsonOut([
        'ok' => false,
        'error' => 'Missing or invalid repository id',
    ], 400);
}

if ($filePath === '') {
    jsonOut([
        'ok' => false,
        'error' => 'Missing or invalid file path',
    ], 400);
}

if (!isset($repos[$repoId]) || !is_array($repos[$repoId])) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository is not whitelisted',
    ], 400);
}

$repo = $repos[$repoId];

if (!(bool) ($repo['enabled'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository is disabled',
    ], 400);
}

$reposDir = (string) ($config['paths']['repos_dir'] ?? '/srv/kuzai-git-rag/repos');
$repoPath = (string) ($repo['path'] ?? '');

if ($repoPath === '' || !is_dir($repoPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository path not found',
    ], 400);
}

if (!isPathInside($reposDir, $repoPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository path outside allowed directory',
    ], 400);
}

$absolutePath = rtrim($repoPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filePath;
$parentDir = dirname($absolutePath);

if (!is_dir($parentDir)) {
    jsonOut([
        'ok' => false,
        'error' => 'Parent directory does not exist',
    ], 400);
}

if (!isPathInside($repoPath, $parentDir)) {
    jsonOut([
        'ok' => false,
        'error' => 'Parent directory outside repository',
    ], 400);
}

if (file_exists($absolutePath) && !is_file($absolutePath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Target path is not a file',
    ], 400);
}

if (file_exists($absolutePath) && !isPathInside($repoPath, $absolutePath)) {
    jsonOut([
        'ok' => false,
        'error' => 'File path outside repository',
    ], 400);
}

if (strlen($content) > 512000) {
    jsonOut([
        'ok' => false,
        'error' => 'Content too large',
        'max_bytes' => 512000,
    ], 413);
}

if (str_contains($content, "\0")) {
    jsonOut([
        'ok' => false,
        'error' => 'Binary content is not allowed',
    ], 415);
}

if (file_exists($absolutePath) && isLikelyBinary($absolutePath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Binary file writing is not allowed',
    ], 415);
}

$backupRoot = '/var/lib/kuzai-git-rag/file-backups';
$backupDir = $backupRoot . DIRECTORY_SEPARATOR . $repoId . DIRECTORY_SEPARATOR . date('Ymd-His');

if (!is_dir($backupDir) && !mkdir($backupDir, 0770, true)) {
    jsonOut([
        'ok' => false,
        'error' => 'Unable to create backup directory',
    ], 500);
}

$backupPath = null;

if (file_exists($absolutePath)) {
    $safeBackupName = str_replace('/', '__', $filePath);
    $backupPath = $backupDir . DIRECTORY_SEPARATOR . $safeBackupName;

    if (!copy($absolutePath, $backupPath)) {
        jsonOut([
            'ok' => false,
            'error' => 'Backup failed before write',
        ], 500);
    }
}

$tmpPath = $absolutePath . '.tmp-kuzai-write-' . bin2hex(random_bytes(6));

if (file_put_contents($tmpPath, $content, LOCK_EX) === false) {
    jsonOut([
        'ok' => false,
        'error' => 'Unable to write temporary file',
    ], 500);
}

chmod($tmpPath, 0660);

if (!rename($tmpPath, $absolutePath)) {
    @unlink($tmpPath);

    jsonOut([
        'ok' => false,
        'error' => 'Unable to replace target file',
    ], 500);
}

jsonOut([
    'ok' => true,
    'repo' => $repoId,
    'path' => $filePath,
    'size_bytes' => strlen($content),
    'backup_path' => $backupPath,
]);
