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

    if ($repoId === '') {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9._-]{1,120}$/', $repoId)) {
        return '';
    }

    return $repoId;
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

function validateRepo(string $repoId, array $config, array $repos): array
{
    $repoId = normalizeRepoId($repoId);

    if ($repoId === '') {
        return [
            'ok' => false,
            'error' => 'Missing or invalid repository id',
        ];
    }

    if (!isset($repos[$repoId]) || !is_array($repos[$repoId])) {
        return [
            'ok' => false,
            'error' => 'Repository is not whitelisted',
        ];
    }

    $repo = $repos[$repoId];

    if (!(bool) ($repo['enabled'] ?? false)) {
        return [
            'ok' => false,
            'error' => 'Repository is disabled',
        ];
    }

    $reposDir = (string) ($config['paths']['repos_dir'] ?? '/srv/kuzai-git-rag/repos');
    $repoPath = (string) ($repo['path'] ?? '');

    if ($repoPath === '' || !is_dir($repoPath)) {
        return [
            'ok' => false,
            'error' => 'Repository path not found',
        ];
    }

    if (!isPathInside($reposDir, $repoPath)) {
        return [
            'ok' => false,
            'error' => 'Repository path is outside allowed directory',
        ];
    }

    if (!is_dir(rtrim($repoPath, DIRECTORY_SEPARATOR) . '/.git')) {
        return [
            'ok' => false,
            'error' => 'Repository is not a Git repository',
        ];
    }

    return [
        'ok' => true,
        'repo_id' => $repoId,
        'repo' => $repo,
    ];
}

function callGitRagIndexService(string $endpoint, int $timeout, string $repoId): array
{
    $ch = curl_init(rtrim($endpoint, '/') . '/index');

    if ($ch === false) {
        return [
            'ok' => false,
            'error' => 'Unable to initialize curl',
            'http_code' => 0,
            'response' => null,
        ];
    }

    $payload = [
        'repo' => $repoId,
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: KUZAI-GIT-RAG-INDEX-PHP/0.1',
        ],
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $body === '') {
        return [
            'ok' => false,
            'error' => $curlError !== '' ? $curlError : 'Empty GIT-RAG service response',
            'http_code' => $httpCode,
            'response' => null,
        ];
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        return [
            'ok' => false,
            'error' => 'Invalid JSON from GIT-RAG service',
            'http_code' => $httpCode,
            'response' => [
                'raw' => mb_substr($body, 0, 2000, 'UTF-8'),
            ],
        ];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300 && (bool) ($data['ok'] ?? false),
        'error' => $data['error'] ?? null,
        'http_code' => $httpCode,
        'response' => $data,
    ];
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
        'error' => 'GIT-RAG config file unavailable',
    ], 500);
}

$config = require $configPath;

if (!is_array($config)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid GIT-RAG config',
    ], 500);
}

$configRepos = [];

if (isset($config['repos']) && is_array($config['repos'])) {
    $configRepos = $config['repos'];
}

$localRepos = loadPhpArrayFile($reposPath);
$repos = array_merge($configRepos, $localRepos);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '', true);

if (!is_array($data)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid JSON payload',
    ], 400);
}

$repoId = normalizeRepoId((string) ($data['repo'] ?? ''));
$repoValidation = validateRepo($repoId, $config, $repos);

if (!($repoValidation['ok'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => $repoValidation['error'] ?? 'Repository validation failed',
        'repo' => $repoId,
    ], 400);
}

$serviceEndpoint = (string) ($config['service']['endpoint'] ?? 'http://127.0.0.1:8890');
$serviceTimeout = (int) ($config['service']['timeout'] ?? 120);

if ($serviceTimeout < 30) {
    $serviceTimeout = 30;
}

if ($serviceTimeout > 900) {
    $serviceTimeout = 900;
}

$result = callGitRagIndexService($serviceEndpoint, $serviceTimeout, $repoId);

if (!($result['ok'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => $result['error'] ?? 'GIT-RAG index failed',
        'repo' => $repoId,
        'service_http_code' => $result['http_code'] ?? 0,
        'service_response' => $result['response'] ?? null,
    ], 502);
}

$response = is_array($result['response'] ?? null) ? $result['response'] : [];

jsonOut([
    'ok' => true,
    'module' => 'KUZAI GIT-RAG',
    'action' => 'index',
    'repo' => $repoId,
    'service_http_code' => $result['http_code'],
    'manifest' => $response,
]);
