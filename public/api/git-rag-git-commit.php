<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function jsonOut(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function normalizeRepoId(string $repoId): string
{
    $repoId = trim($repoId);

    if ($repoId === '' || !preg_match('/^[A-Za-z0-9._-]{1,120}$/', $repoId)) {
        return '';
    }

    return $repoId;
}

function normalizeCommitMessage(string $message): string
{
    $message = trim(str_replace(["\r", "\n"], ' ', $message));
    $message = preg_replace('/\s+/', ' ', $message) ?? '';

    if ($message === '' || mb_strlen($message, 'UTF-8') > 180) {
        return '';
    }

    return $message;
}

function callGitCommitService(string $repoId, string $message): array
{
    $configPath = __DIR__ . '/../../app/git-rag.config.php';
    $endpoint = 'http://127.0.0.1:8890';

    if (is_file($configPath) && is_readable($configPath)) {
        $config = require $configPath;

        if (is_array($config)) {
            $endpoint = (string) ($config['service']['endpoint'] ?? $endpoint);
        }
    }

    $ch = curl_init(rtrim($endpoint, '/') . '/git-commit');

    if ($ch === false) {
        return [
            'ok' => false,
            'error' => 'Unable to initialize curl',
            'http_code' => 0,
            'response' => null,
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'repo' => $repoId,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: KUZAI-GIT-COMMIT-PHP/0.1',
        ],
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $body === '') {
        return [
            'ok' => false,
            'error' => $curlError !== '' ? $curlError : 'Empty service response',
            'http_code' => $httpCode,
            'response' => null,
        ];
    }

    $data = json_decode($body, true);

    if (!is_array($data)) {
        return [
            'ok' => false,
            'error' => 'Invalid JSON from service',
            'http_code' => $httpCode,
            'response' => null,
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

$data = json_decode(file_get_contents('php://input') ?: '', true);

if (!is_array($data)) {
    jsonOut([
        'ok' => false,
        'error' => 'Invalid JSON payload',
    ], 400);
}

$repoId = normalizeRepoId((string) ($data['repo'] ?? ''));
$message = normalizeCommitMessage((string) ($data['message'] ?? ''));

if ($repoId === '') {
    jsonOut([
        'ok' => false,
        'error' => 'Missing or invalid repository id',
    ], 400);
}

if ($message === '') {
    jsonOut([
        'ok' => false,
        'error' => 'Missing or invalid commit message',
    ], 400);
}

$result = callGitCommitService($repoId, $message);

if (!($result['ok'] ?? false)) {
    jsonOut([
        'ok' => false,
        'error' => $result['error'] ?? 'Git commit service failed',
        'repo' => $repoId,
        'service_http_code' => $result['http_code'] ?? 0,
        'service_response' => $result['response'] ?? null,
    ], 502);
}

$response = is_array($result['response'] ?? null) ? $result['response'] : [];

jsonOut([
    'ok' => true,
    'repo' => $repoId,
    'service_http_code' => $result['http_code'],
    'message' => (string) ($response['message'] ?? $message),
    'commit' => (string) ($response['commit'] ?? ''),
    'commit_short' => (string) ($response['commit_short'] ?? ''),
    'status_before' => is_array($response['status_before'] ?? null) ? $response['status_before'] : [],
    'status_after' => is_array($response['status_after'] ?? null) ? $response['status_after'] : [],
    'stdout' => (string) ($response['stdout'] ?? ''),
    'stderr' => (string) ($response['stderr'] ?? ''),
]);
