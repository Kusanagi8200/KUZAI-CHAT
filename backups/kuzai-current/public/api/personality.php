<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../app/config.php';

function respondJson(array $payload, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function failJson(string $message, int $code = 400, array $extra = []): never
{
    respondJson(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), $code);
}

function profileStorageDir(): string
{
    $dir = realpath(__DIR__ . '/../../storage/personality_profiles');

    if ($dir === false) {
        $target = __DIR__ . '/../../storage/personality_profiles';

        if (!is_dir($target) && !mkdir($target, 0750, true)) {
            failJson('Unable to create profile storage directory', 500);
        }

        $dir = realpath($target);
    }

    if ($dir === false || !is_dir($dir)) {
        failJson('Invalid profile storage directory', 500);
    }

    return $dir;
}

function normalizeProfileId(string $id): string
{
    $id = trim($id);

    if ($id === '') {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,80}$/', $id)) {
        return '';
    }

    return $id;
}

function slugifyProfileId(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
    $value = trim($value, '-');

    if ($value === '') {
        $value = 'profile-' . date('Ymd-His');
    }

    return normalizeProfileId($value) ?: ('profile-' . date('Ymd-His'));
}

function readJsonFile(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);

    if ($raw === false || trim($raw) === '') {
        return null;
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

function scalarValue(array $data, array $keys): string
{
    foreach ($keys as $key) {
        if (isset($data[$key]) && is_scalar($data[$key])) {
            $value = trim((string) $data[$key]);

            if ($value !== '') {
                return preg_replace('/\s+/', ' ', $value) ?? $value;
            }
        }
    }

    return '';
}

function profilePath(string $id): string
{
    $id = normalizeProfileId($id);

    if ($id === '') {
        failJson('Invalid profile ID');
    }

    return profileStorageDir() . DIRECTORY_SEPARATOR . $id . '.json';
}

function summarizeProfile(array $profile, string $file): array
{
    $id = normalizeProfileId((string) ($profile['id'] ?? pathinfo($file, PATHINFO_FILENAME)));
    $label = scalarValue($profile, ['label', 'profile_label', 'name', 'title']);

    if ($label === '') {
        $label = $id;
    }

    $description = scalarValue($profile, ['description', 'desc', 'summary']);
    $updatedAt = scalarValue($profile, ['updated_at', 'updatedAt']);

    return [
        'id' => $id,
        'file' => $file,
        'label' => $label,
        'description' => $description,
        'updated_at' => $updatedAt,
        'locked' => (bool) ($profile['locked'] ?? false),
        'protected' => $id === 'default-generalist',
    ];
}

function listProfiles(): array
{
    $dir = profileStorageDir();
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];
    $profiles = [];

    foreach ($files as $filePath) {
        $profile = readJsonFile($filePath);

        if (!is_array($profile)) {
            continue;
        }

        $profiles[] = summarizeProfile($profile, basename($filePath));
    }

    usort($profiles, static function (array $a, array $b): int {
        if (($a['id'] ?? '') === 'default-generalist') {
            return -1;
        }

        if (($b['id'] ?? '') === 'default-generalist') {
            return 1;
        }

        return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
    });

    return $profiles;
}

function readProfile(string $id): array
{
    $id = normalizeProfileId($id);

    if ($id === '') {
        failJson('Invalid profile ID');
    }

    $path = profilePath($id);
    $profile = readJsonFile($path);

    if (!is_array($profile)) {
        failJson('Profile not found', 404);
    }

    return [
        'summary' => summarizeProfile($profile, basename($path)),
        'profile_document' => $profile,
    ];
}

function saveProfile(array $input): array
{
    $document = null;

    if (isset($input['profile_document']) && is_array($input['profile_document'])) {
        $document = $input['profile_document'];
    } elseif (isset($input['document']) && is_array($input['document'])) {
        $document = $input['document'];
    } elseif (isset($input['profile']) && is_array($input['profile']) && (isset($input['id']) || isset($input['label']))) {
        $document = $input;
    } elseif (isset($input['id']) || isset($input['label'])) {
        $document = $input;
    }

    if (!is_array($document)) {
        failJson('Missing profile document');
    }

    $label = scalarValue($document, ['label', 'profile_label', 'name', 'title']);

    if ($label === '' && isset($document['profile']) && is_array($document['profile'])) {
        $label = scalarValue($document['profile'], ['label', 'name', 'title']);
    }

    $id = normalizeProfileId((string) ($document['id'] ?? ''));

    if ($id === '') {
        $id = slugifyProfileId($label);
    }

    if ($id === '') {
        failJson('Invalid profile ID');
    }

    if ($label === '') {
        $label = $id;
    }

    $description = scalarValue($document, ['description', 'desc', 'summary']);

    $document['id'] = $id;
    $document['label'] = $label;
    $document['description'] = $description;
    $document['locked'] = (bool) ($document['locked'] ?? false);
    $document['updated_at'] = date('c');

    $path = profilePath($id);
    $tmp = $path . '.tmp-' . getmypid();

    $json = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);

    if (!is_string($json) || $json === '') {
        failJson('Unable to encode profile JSON', 500);
    }

    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        failJson('Unable to write temporary profile file', 500);
    }

    chmod($tmp, 0640);

    if (!rename($tmp, $path)) {
        @unlink($tmp);
        failJson('Unable to save profile file', 500);
    }

    return [
        'summary' => summarizeProfile($document, basename($path)),
        'profile_document' => $document,
    ];
}

function deleteProfile(string $id): array
{
    $id = normalizeProfileId($id);

    if ($id === '') {
        failJson('Invalid profile ID');
    }

    if ($id === 'default-generalist') {
        failJson('default-generalist is protected and cannot be deleted from the UI', 403);
    }

    $path = profilePath($id);

    if (!is_file($path)) {
        failJson('Profile not found', 404);
    }

    $profile = readJsonFile($path) ?? [];
    $dir = profileStorageDir();
    $deletedDir = $dir . DIRECTORY_SEPARATOR . '.deleted';

    if (!is_dir($deletedDir) && !mkdir($deletedDir, 0750, true)) {
        failJson('Unable to create deleted profiles directory', 500);
    }

    $deletedPath = $deletedDir . DIRECTORY_SEPARATOR . $id . '.deleted-' . date('Ymd-His') . '.json';

    if (!rename($path, $deletedPath)) {
        failJson('Unable to move profile to deleted directory', 500);
    }

    chmod($deletedPath, 0640);

    return [
        'id' => $id,
        'file' => basename($path),
        'deleted_file' => basename($deletedPath),
        'deleted_path' => $deletedPath,
        'summary' => summarizeProfile($profile, basename($path)),
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawInput = file_get_contents('php://input');
$input = [];

if (is_string($rawInput) && trim($rawInput) !== '') {
    $decoded = json_decode($rawInput, true);

    if (!is_array($decoded)) {
        failJson('Invalid JSON payload');
    }

    $input = $decoded;
}

$action = strtolower(trim((string) ($_GET['action'] ?? $input['action'] ?? '')));

if ($action === '') {
    $action = $method === 'POST' ? 'save' : 'list';
}

if ($method === 'GET' && $action === 'list') {
    respondJson([
        'ok' => true,
        'profiles' => listProfiles(),
    ]);
}

if ($method === 'GET' && $action === 'read') {
    $id = (string) ($_GET['id'] ?? '');
    $profile = readProfile($id);

    respondJson([
        'ok' => true,
        'profile' => $profile['summary'],
        'profile_document' => $profile['profile_document'],
    ]);
}

if ($method === 'POST' && $action === 'save') {
    $saved = saveProfile($input);

    respondJson([
        'ok' => true,
        'profile' => $saved['summary'],
        'profile_document' => $saved['profile_document'],
    ]);
}

if (($method === 'POST' || $method === 'DELETE') && $action === 'delete') {
    $id = (string) ($_GET['id'] ?? $input['id'] ?? '');
    $deleted = deleteProfile($id);

    respondJson([
        'ok' => true,
        'deleted' => true,
        'profile' => $deleted['summary'],
        'deleted_file' => $deleted['deleted_file'],
        'deleted_path' => $deleted['deleted_path'],
        'profiles' => listProfiles(),
    ]);
}

failJson('Unsupported action or method', 405, [
    'method' => $method,
    'action' => $action,
]);
