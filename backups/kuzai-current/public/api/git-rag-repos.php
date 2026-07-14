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
            'commit' => $commit,
        ];
    }

    return [
        'branch' => 'DETACHED',
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

$responseRepos = [];

foreach ($repos as $repoId => $repoConfig) {
    if (!is_array($repoConfig)) {
        continue;
    }

    $repoId = (string) $repoId;
    $repoName = (string) ($repoConfig['name'] ?? $repoId);
    $repoPath = (string) ($repoConfig['path'] ?? '');
    $configuredRemote = (string) ($repoConfig['remote'] ?? '');
    $configuredBranch = (string) ($repoConfig['active_branch'] ?? '');
    $enabled = (bool) ($repoConfig['enabled'] ?? false);

    $exists = $repoPath !== '' && is_dir($repoPath);
    $insideReposDir = $exists && isPathInside($reposDir, $repoPath);
    $isGitRepo = $insideReposDir && is_dir(rtrim($repoPath, '/') . '/.git');

    $head = [
        'branch' => null,
        'commit' => null,
    ];

    $detectedRemote = null;

    if ($isGitRepo) {
        $head = readGitHead($repoPath);
        $detectedRemote = readGitRemoteUrl($repoPath, 'origin');
    }

    $branchMatches = $configuredBranch !== '' && $head['branch'] === $configuredBranch;
    $remoteMatches = $configuredRemote !== '' && $detectedRemote === $configuredRemote;

    $ready = $enabled && $exists && $insideReposDir && $isGitRepo && $branchMatches && $remoteMatches;

    $responseRepos[] = [
        'id' => $repoId,
        'name' => $repoName,
        'enabled' => $enabled,
        'ready' => $ready,
        'branch' => $head['branch'],
        'configured_branch' => $configuredBranch,
        'commit' => $head['commit'],
        'commit_short' => $head['commit'] !== null ? substr((string) $head['commit'], 0, 12) : null,
        'remote_ok' => $remoteMatches,
        'branch_ok' => $branchMatches,
        'git_repo_ok' => $isGitRepo,
    ];
}

usort($responseRepos, static function (array $a, array $b): int {
    return strcmp((string) $a['name'], (string) $b['name']);
});

jsonOut([
    'ok' => true,
    'module' => 'KUZAI GIT-RAG',
    'repos_count' => count($responseRepos),
    'repos' => $responseRepos,
]);
