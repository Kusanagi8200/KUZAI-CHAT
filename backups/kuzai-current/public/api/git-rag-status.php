<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function jsonOut(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function readTextFile(string $path, int $maxBytes = 1048576): ?string
{
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $size = filesize($path);

    if ($size === false || $size > $maxBytes) {
        return null;
    }

    $content = file_get_contents($path);

    if ($content === false) {
        return null;
    }

    return trim($content);
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

function readGitHead(string $repoPath): array
{
    $headPath = rtrim($repoPath, '/') . '/.git/HEAD';
    $head = readTextFile($headPath, 8192);

    if ($head === null || $head === '') {
        return [
            'branch' => null,
            'head_raw' => null,
            'commit' => null,
        ];
    }

    if (str_starts_with($head, 'ref: ')) {
        $ref = trim(substr($head, 5));
        $branch = basename($ref);
        $commitPath = rtrim($repoPath, '/') . '/.git/' . $ref;
        $commit = readTextFile($commitPath, 8192);

        if ($commit === null) {
            $packedRefsPath = rtrim($repoPath, '/') . '/.git/packed-refs';
            $packedRefs = readTextFile($packedRefsPath, 1048576);

            if ($packedRefs !== null) {
                foreach (explode("\n", $packedRefs) as $line) {
                    $line = trim($line);

                    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '^')) {
                        continue;
                    }

                    $parts = preg_split('/\s+/', $line);

                    if (is_array($parts) && count($parts) === 2 && $parts[1] === $ref) {
                        $commit = $parts[0];
                        break;
                    }
                }
            }
        }

        return [
            'branch' => $branch,
            'head_raw' => $head,
            'commit' => $commit,
        ];
    }

    return [
        'branch' => 'DETACHED',
        'head_raw' => $head,
        'commit' => $head,
    ];
}

function readGitRemoteUrl(string $repoPath, string $remoteName = 'origin'): ?string
{
    $configPath = rtrim($repoPath, '/') . '/.git/config';
    $config = readTextFile($configPath, 1048576);

    if ($config === null) {
        return null;
    }

    $currentSection = '';

    foreach (explode("\n", $config) as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        if (str_starts_with($line, '[') && str_ends_with($line, ']')) {
            $currentSection = $line;
            continue;
        }

        if ($currentSection === '[remote "' . $remoteName . '"]' && str_starts_with($line, 'url =')) {
            return trim(substr($line, strlen('url =')));
        }
    }

    return null;
}

function loadPhpArrayFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

$configPath = __DIR__ . '/../../app/git-rag.config.php';
$reposPath = __DIR__ . '/../../app/git-rag.repos.php';

if (!is_file($configPath)) {
    jsonOut([
        'ok' => false,
        'error' => 'GIT-RAG config file not found',
        'config_path' => $configPath,
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

$paths = $config['paths'] ?? [];
$pathStatus = [];

foreach ($paths as $key => $path) {
    $path = (string) $path;

    $pathStatus[$key] = [
        'path' => $path,
        'exists' => is_dir($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path),
    ];
}

$gitVersion = null;
$gitReturnCode = 1;
$gitOutput = [];

exec('/usr/bin/env git --version 2>&1', $gitOutput, $gitReturnCode);

if ($gitReturnCode === 0 && isset($gitOutput[0])) {
    $gitVersion = $gitOutput[0];
}

$embeddingEnvFile = (string) ($config['embeddings']['env_file'] ?? '');
$embeddingEnvStatus = [
    'path' => $embeddingEnvFile,
    'exists' => $embeddingEnvFile !== '' && is_file($embeddingEnvFile),
    'readable' => $embeddingEnvFile !== '' && is_readable($embeddingEnvFile),
];

$reposDir = (string) ($paths['repos_dir'] ?? '/srv/kuzai-git-rag/repos');
$repoStatus = [];

foreach ($repos as $repoId => $repoConfig) {
    if (!is_array($repoConfig)) {
        continue;
    }

    $repoId = (string) $repoId;
    $repoPath = (string) ($repoConfig['path'] ?? '');
    $enabled = (bool) ($repoConfig['enabled'] ?? false);
    $configuredRemote = (string) ($repoConfig['remote'] ?? '');
    $configuredBranch = (string) ($repoConfig['active_branch'] ?? '');

    $exists = $repoPath !== '' && is_dir($repoPath);
    $insideReposDir = $exists && isPathInside($reposDir, $repoPath);
    $isGitRepo = $insideReposDir && is_dir(rtrim($repoPath, '/') . '/.git');

    $head = [
        'branch' => null,
        'head_raw' => null,
        'commit' => null,
    ];

    $detectedRemote = null;

    if ($isGitRepo) {
        $head = readGitHead($repoPath);
        $detectedRemote = readGitRemoteUrl($repoPath, 'origin');
    }

    $branchMatches = $configuredBranch !== '' && $head['branch'] === $configuredBranch;
    $remoteMatches = $configuredRemote !== '' && $detectedRemote === $configuredRemote;

    $repoStatus[$repoId] = [
        'id' => $repoId,
        'name' => (string) ($repoConfig['name'] ?? $repoId),
        'enabled' => $enabled,
        'path' => $repoPath,
        'exists' => $exists,
        'inside_repos_dir' => $insideReposDir,
        'is_git_repo' => $isGitRepo,
        'configured_remote' => $configuredRemote,
        'detected_remote' => $detectedRemote,
        'remote_matches_config' => $remoteMatches,
        'configured_branch' => $configuredBranch,
        'detected_branch' => $head['branch'],
        'branch_matches_config' => $branchMatches,
        'commit' => $head['commit'],
        'ready' => $enabled && $exists && $insideReposDir && $isGitRepo && $branchMatches && $remoteMatches,
    ];
}

$allPathsOk = true;

foreach ($pathStatus as $item) {
    if (!$item['exists'] || !$item['readable']) {
        $allPathsOk = false;
        break;
    }
}

$allReposReady = true;

foreach ($repoStatus as $item) {
    if (!$item['ready']) {
        $allReposReady = false;
        break;
    }
}

jsonOut([
    'ok' => $allPathsOk && $gitReturnCode === 0,
    'module' => 'KUZAI GIT-RAG',
    'enabled' => (bool) ($config['enabled'] ?? false),
    'php_version' => PHP_VERSION,
    'git' => [
        'available' => $gitReturnCode === 0,
        'version' => $gitVersion,
    ],
    'paths' => $pathStatus,
    'embeddings' => [
        'mode' => (string) ($config['embeddings']['mode'] ?? ''),
        'provider' => (string) ($config['embeddings']['provider'] ?? ''),
        'env_file' => $embeddingEnvStatus,
        'api_key_loaded' => false,
    ],
    'git_permissions' => [
        'auth_mode' => (string) ($config['git']['auth_mode'] ?? ''),
        'clone_mode' => (string) ($config['git']['clone_mode'] ?? ''),
        'allow_status' => (bool) ($config['git']['allow_status'] ?? false),
        'allow_pull' => (bool) ($config['git']['allow_pull'] ?? false),
        'allow_diff' => (bool) ($config['git']['allow_diff'] ?? false),
        'allow_commit' => (bool) ($config['git']['allow_commit'] ?? false),
        'allow_push' => (bool) ($config['git']['allow_push'] ?? false),
        'allow_force_push' => (bool) ($config['git']['allow_force_push'] ?? false),
        'single_active_branch' => (bool) ($config['git']['single_active_branch'] ?? true),
    ],
    'repos_file' => [
        'path' => $reposPath,
        'exists' => is_file($reposPath),
        'readable' => is_readable($reposPath),
    ],
    'repos_count' => count($repoStatus),
    'repos_ready' => $allReposReady,
    'repos' => $repoStatus,
]);
