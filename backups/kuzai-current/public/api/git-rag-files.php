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

function normalizeRepoId(string $repoId): string
{
    $repoId = trim($repoId);

    if ($repoId === '') {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9._-]{1,120}$/', $repoId)) {
        return '';
    }

    return $repoId;
}

function isBinaryExtension(string $extension): bool
{
    $binaryExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'bmp', 'tiff',
        'pdf',
        'zip', 'gz', 'tar', '7z', 'rar',
        'mp3', 'wav', 'ogg', 'flac',
        'mp4', 'avi', 'mov', 'mkv', 'webm',
        'sqlite', 'db',
        'onnx', 'gguf',
        'exe', 'dll', 'so', 'bin',
        'woff', 'woff2', 'ttf', 'otf',
    ];

    return in_array(strtolower($extension), $binaryExtensions, true);
}

function buildFileTree(string $repoPath, int $maxFiles = 10000): array
{
    $repoPath = rtrim($repoPath, DIRECTORY_SEPARATOR);
    $files = [];
    $extensions = [];
    $totalBytes = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator(
                $repoPath,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            static function (SplFileInfo $current): bool {
                $name = $current->getFilename();

                if ($current->isDir() && $name === '.git') {
                    return false;
                }

                return true;
            }
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if (!$item instanceof SplFileInfo) {
            continue;
        }

        if (!$item->isFile()) {
            continue;
        }

        $absolutePath = $item->getPathname();
        $relativePath = substr($absolutePath, strlen($repoPath) + 1);

        if ($relativePath === false || $relativePath === '') {
            continue;
        }

        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

        if (str_starts_with($relativePath, '.git/')) {
            continue;
        }

        $size = $item->getSize();
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $mtime = $item->getMTime();

        if ($extension === '') {
            $extensionKey = '[none]';
        } else {
            $extensionKey = $extension;
        }

        if (!isset($extensions[$extensionKey])) {
            $extensions[$extensionKey] = 0;
        }

        $extensions[$extensionKey]++;
        $totalBytes += $size;

        $files[] = [
            'path' => $relativePath,
            'name' => basename($relativePath),
            'dir' => dirname($relativePath) === '.' ? '' : dirname($relativePath),
            'extension' => $extension,
            'size_bytes' => $size,
            'modified_at' => date('c', $mtime),
            'likely_binary' => isBinaryExtension($extension),
        ];

        if (count($files) >= $maxFiles) {
            break;
        }
    }

    usort($files, static function (array $a, array $b): int {
        return strcmp((string) $a['path'], (string) $b['path']);
    });

    ksort($extensions);

    return [
        'files' => $files,
        'extensions' => $extensions,
        'total_bytes' => $totalBytes,
        'truncated' => count($files) >= $maxFiles,
        'max_files' => $maxFiles,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonOut([
        'ok' => false,
        'error' => 'Method not allowed',
    ], 405);
}

$configPath = __DIR__ . '/../../app/git-rag.config.php';
$reposPath = __DIR__ . '/../../app/git-rag.repos.php';

if (!is_file($configPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'GIT-RAG config file not found',
    ], 500);
}

$config = require $configPath;

if (!is_array($config)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid GIT-RAG config',
    ], 500);
}

$reposDir = (string) ($config['paths']['repos_dir'] ?? '/srv/kuzai-git-rag/repos');

$configRepos = [];

if (isset($config['repos']) && is_array($config['repos'])) {
    $configRepos = $config['repos'];
}

$localRepos = loadPhpArrayFile($reposPath);
$repos = array_merge($configRepos, $localRepos);

if (!$repos) {
    jsonOut([
        'ok' => false,
        'error' => 'No GIT-RAG repository configured',
    ], 404);
}

$repoId = normalizeRepoId((string) ($_GET['repo'] ?? ''));

if ($repoId === '' && count($repos) === 1) {
    $repoId = (string) array_key_first($repos);
}

if ($repoId === '' || !isset($repos[$repoId]) || !is_array($repos[$repoId])) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid or missing repository id',
        'available_repos' => array_keys($repos),
    ], 400);
}

$repoConfig = $repos[$repoId];

if (!(bool) ($repoConfig['enabled'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository is disabled',
        'repo' => $repoId,
    ], 403);
}

$repoPath = (string) ($repoConfig['path'] ?? '');

if ($repoPath === '' || !is_dir($repoPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository path not found',
        'repo' => $repoId,
    ], 404);
}

if (!isPathInside($reposDir, $repoPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository path is outside allowed repos directory',
        'repo' => $repoId,
    ], 403);
}

if (!is_dir(rtrim($repoPath, DIRECTORY_SEPARATOR) . '/.git')) {
    jsonOut([
        'ok' => false,
        'error' => 'Repository is not a Git repository',
        'repo' => $repoId,
    ], 400);
}

$maxFiles = (int) ($_GET['max_files'] ?? 10000);

if ($maxFiles < 1) {
    $maxFiles = 1;
}

if ($maxFiles > 50000) {
    $maxFiles = 50000;
}

$result = buildFileTree($repoPath, $maxFiles);

jsonOut([
    'ok' => true,
    'module' => 'KUZAI GIT-RAG',
    'repo' => [
        'id' => $repoId,
        'name' => (string) ($repoConfig['name'] ?? $repoId),
        'branch' => (string) ($repoConfig['active_branch'] ?? ''),
    ],
    'summary' => [
        'files_count' => count($result['files']),
        'total_bytes' => $result['total_bytes'],
        'extensions' => $result['extensions'],
        'truncated' => $result['truncated'],
        'max_files' => $result['max_files'],
    ],
    'files' => $result['files'],
]);
