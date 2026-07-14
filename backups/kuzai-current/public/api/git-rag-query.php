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

function callGitRagService(string $endpoint, int $timeout, string $repoId, string $query): array
{
    $url = rtrim($endpoint, '/') . '/query';

    $payload = [
        'repo' => $repoId,
        'query' => $query,
    ];

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'ok' => false,
            'http_code' => 0,
            'error' => 'Unable to initialize curl',
            'response' => null,
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: KUZAI-GIT-RAG-PHP/0.1',
        ],
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $body === '') {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'error' => $curlError !== '' ? $curlError : 'Empty GIT-RAG service response',
            'response' => null,
        ];
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'error' => 'Invalid JSON from GIT-RAG service',
            'response' => [
                'raw' => mb_substr($body, 0, 2000, 'UTF-8'),
            ],
        ];
    }

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300 && (bool) ($data['ok'] ?? false),
        'http_code' => $httpCode,
        'error' => $data['error'] ?? null,
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
$query = trim((string) ($data['query'] ?? ''));

if ($query === '') {
    jsonOut([
        'ok' => false,
        'error' => 'Query is required',
    ], 400);
}

if (mb_strlen($query, 'UTF-8') > 8000) {
    jsonOut([
        'ok' => false,
        'error' => 'Query is too long',
    ], 413);
}

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

if ($serviceTimeout < 5) {
    $serviceTimeout = 5;
}

if ($serviceTimeout > 300) {
    $serviceTimeout = 300;
}

$serviceResult = callGitRagService($serviceEndpoint, $serviceTimeout, $repoId, $query);

if (!($serviceResult['ok'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => $serviceResult['error'] ?? 'GIT-RAG service query failed',
        'repo' => $repoId,
        'service_http_code' => $serviceResult['http_code'] ?? 0,
        'service_response' => $serviceResult['response'] ?? null,
    ], 502);
}

$response = $serviceResult['response'];

if (!is_array($response)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid GIT-RAG service response',
    ], 502);
}

jsonOut([
    'ok' => true,
    'module' => 'KUZAI GIT-RAG',
    'repo' => $repoId,
    'query' => $query,
    'service_http_code' => $serviceResult['http_code'],
    'mode' => (string) ($response['mode'] ?? ''),
    'chunks' => is_array($response['chunks'] ?? null) ? $response['chunks'] : [],
    'message' => (string) ($response['message'] ?? ''),
    'service_response' => $response,
]);
