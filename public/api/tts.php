<?php

declare(strict_types=1);

$config = require __DIR__ . '/../../app/config.php';

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function successJson(array $data): never
{
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge([
        'ok' => true,
    ], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

function cleanText(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
    $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

    return trim($value);
}

function prepareTextForSpeech(string $value): string
{
    $value = cleanText($value);

    if ($value === '') {
        return '';
    }

    $value = preg_replace('/```.*?```/s', ' ', $value) ?? $value;
    $value = preg_replace('/~~~.*?~~~/s', ' ', $value) ?? $value;

    $value = preg_replace('/(^|\n)\s*Sources\s*:.*$/is', '$1', $value) ?? $value;
    $value = preg_replace('/(^|\n)\s*Source\s*:.*$/is', '$1', $value) ?? $value;
    $value = preg_replace('/(^|\n)\s*References\s*:.*$/is', '$1', $value) ?? $value;

    $value = preg_replace('/https?:\/\/\S+/i', ' ', $value) ?? $value;
    $value = preg_replace('/www\.\S+/i', ' ', $value) ?? $value;

    $value = preg_replace('/`([^`]*)`/', '$1', $value) ?? $value;

    $lines = preg_split('/\n+/', $value);
    $kept = [];

    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^\s*[-*]\s*https?:\/\//i', $line)) {
                continue;
            }

            if (preg_match('/^\s*(sudo|apt|apt-get|systemctl|journalctl|curl|wget|grep|sed|awk|php|python3?|jq|cat|cp|mv|chmod|chown|mkdir|rm|find|ls|tail|head|nano|vim|openssl)\b/i', $line)) {
                continue;
            }

            if (preg_match('/^\s*[\{\}\[\]",:0-9._-]+\s*$/', $line)) {
                continue;
            }

            if (substr_count($line, '/') >= 4) {
                continue;
            }

            if (substr_count($line, '|') >= 2) {
                continue;
            }

            if (substr_count($line, '\\') >= 2) {
                continue;
            }

            $kept[] = $line;
        }
    }

    $value = implode("\n", $kept);

    $replacements = [
        '/\*\*(.*?)\*\*/' => '$1',
        '/\*(.*?)\*/' => '$1',
        '/__(.*?)__/' => '$1',
        '/_(.*?)_/' => '$1',
        '/#+\s*/' => '',
        '/^\s*[-*]\s+/m' => '',
        '/[<>]+/' => ' ',
        '/[{}\[\]\|~^]+/' => ' ',
        '/[ \t]+/' => ' ',
        '/\n{2,}/' => "\n",
    ];

    foreach ($replacements as $pattern => $replacement) {
        $value = preg_replace($pattern, $replacement, $value) ?? $value;
    }

    $value = trim($value);

    $maxSpeechChars = 1800;

    if (mb_strlen($value, 'UTF-8') > $maxSpeechChars) {
        $value = mb_substr($value, 0, $maxSpeechChars, 'UTF-8');
        $lastDot = mb_strrpos($value, '.', 0, 'UTF-8');

        if ($lastDot !== false && $lastDot > 300) {
            $value = mb_substr($value, 0, $lastDot + 1, 'UTF-8');
        }

        $value = trim($value) . "\nThe spoken answer was shortened.";
    }

    return cleanText($value);
}

function getTtsDir(): string
{
    return '/var/www/html/KUZAI/storage/tts';
}

function ensureTtsDir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        failJson('Unable to create TTS directory', 500);
    }

    if (!is_writable($dir)) {
        failJson('TTS directory is not writable', 500);
    }
}

function cleanupOldTtsFiles(string $dir, int $maxAgeSeconds = 86400): void
{
    $files = glob($dir . '/*.{wav,txt}', GLOB_BRACE);

    if (!is_array($files)) {
        return;
    }

    $now = time();

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $mtime = filemtime($file);

        if ($mtime !== false && ($now - $mtime) > $maxAgeSeconds) {
            @unlink($file);
        }
    }
}

function serveAudio(string $id, bool $headersOnly = false): never
{
    if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
        http_response_code(404);
        exit;
    }

    $path = getTtsDir() . '/' . $id . '.wav';

    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit;
    }

    $size = filesize($path);

    header('Content-Type: audio/wav');
    header('Content-Disposition: inline; filename="' . $id . '.wav"');
    header('Cache-Control: private, max-age=3600');
    header('Accept-Ranges: bytes');

    if ($size !== false) {
        header('Content-Length: ' . $size);
    }

    if ($headersOnly) {
        exit;
    }

    readfile($path);
    exit;
}

function getPiperVoices(): array
{
    return [
        'en_US-lessac-high' => [
            'model' => '/opt/kuzai-tts/piper/models/en_US-lessac-high.onnx',
            'config' => '/opt/kuzai-tts/piper/models/en_US-lessac-high.onnx.json',
        ],
    ];
}

function speedToLengthScale(int $speed): float
{
    if ($speed < 80) {
        $speed = 80;
    }

    if ($speed > 260) {
        $speed = 260;
    }

    $scale = 155 / $speed;

    if ($scale < 0.70) {
        return 0.70;
    }

    if ($scale > 1.45) {
        return 1.45;
    }

    return round($scale, 2);
}

function runProcess(array $cmd, array $env): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes, null, $env, [
        'bypass_shell' => true,
    ]);

    if (!is_resource($process)) {
        return [
            'ok' => false,
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => 'proc_open failed',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'ok' => $exitCode === 0,
        'exit_code' => $exitCode,
        'stdout' => trim((string) $stdout),
        'stderr' => trim((string) $stderr),
    ];
}

function runPiperTts(string $textPath, string $wavPath, string $voice, int $speed, string $ttsDir): array
{
    $piperBin = '/opt/kuzai-tts/piper/venv/bin/piper';
    $voices = getPiperVoices();

    if (!is_file($piperBin) || !is_executable($piperBin)) {
        return [
            'ok' => false,
            'engine' => 'piper',
            'error' => 'Piper binary not found or not executable',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
            'length_scale' => null,
        ];
    }

    if (!isset($voices[$voice])) {
        return [
            'ok' => false,
            'engine' => 'piper',
            'error' => 'Piper voice not configured',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
            'length_scale' => null,
        ];
    }

    $modelPath = $voices[$voice]['model'];
    $configPath = $voices[$voice]['config'];

    if (!is_file($modelPath) || !is_readable($modelPath)) {
        return [
            'ok' => false,
            'engine' => 'piper',
            'error' => 'Piper model file not readable',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
            'length_scale' => null,
        ];
    }

    if (!is_file($configPath) || !is_readable($configPath)) {
        return [
            'ok' => false,
            'engine' => 'piper',
            'error' => 'Piper config file not readable',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
            'length_scale' => null,
        ];
    }

    $lengthScale = speedToLengthScale($speed);

    $cmd = [
        $piperBin,
        '-m', $modelPath,
        '-c', $configPath,
        '-i', $textPath,
        '-f', $wavPath,
        '--length-scale', (string) $lengthScale,
        '--sentence-silence', '0.35',
        '--volume', '0.75',
    ];

    $env = [
        'HOME' => $ttsDir,
        'XDG_CONFIG_HOME' => $ttsDir . '/.config',
        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    ];

    $result = runProcess($cmd, $env);
    $result['engine'] = 'piper';
    $result['voice'] = $voice;
    $result['length_scale'] = $lengthScale;

    return $result;
}

function runEspeakTts(string $textPath, string $wavPath, string $voice, int $speed, string $ttsDir): array
{
    $espeakBin = '/usr/bin/espeak-ng';

    if (!is_file($espeakBin) || !is_executable($espeakBin)) {
        return [
            'ok' => false,
            'engine' => 'espeak',
            'error' => 'espeak-ng binary not found or not executable',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
        ];
    }

    if (!preg_match('/^[a-zA-Z0-9+._-]{2,32}$/', $voice)) {
        return [
            'ok' => false,
            'engine' => 'espeak',
            'error' => 'Invalid eSpeak voice value',
            'exit_code' => -1,
            'stdout' => '',
            'stderr' => '',
        ];
    }

    $cmd = [
        $espeakBin,
        '-v', $voice,
        '-s', (string) $speed,
        '-f', $textPath,
        '-w', $wavPath,
    ];

    $env = [
        'HOME' => $ttsDir,
        'XDG_CONFIG_HOME' => $ttsDir . '/.config',
        'PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
    ];

    $result = runProcess($cmd, $env);
    $result['engine'] = 'espeak';
    $result['voice'] = $voice;
    $result['length_scale'] = null;

    return $result;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    serveAudio((string) ($_GET['id'] ?? ''), false);
}

if ($method === 'HEAD') {
    serveAudio((string) ($_GET['id'] ?? ''), true);
}

if ($method !== 'POST') {
    failJson('Method not allowed', 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '', true);

if (!is_array($data)) {
    failJson('Invalid JSON payload');
}

$rawText = cleanText((string) ($data['text'] ?? ''));

if ($rawText === '') {
    failJson('Text is required');
}

$text = prepareTextForSpeech($rawText);

if ($text === '') {
    failJson('No speakable text after cleanup');
}

$engine = cleanText((string) ($data['engine'] ?? 'piper'));

if (!in_array($engine, ['piper', 'espeak'], true)) {
    failJson('Invalid TTS engine');
}

$speed = (int) ($data['speed'] ?? 145);

if ($speed < 80 || $speed > 260) {
    failJson('Invalid speed value');
}

$requestedVoice = cleanText((string) ($data['voice'] ?? ''));

$piperDefaultVoice = 'en_US-lessac-high';
$espeakFallbackVoice = 'en-us+f4';

if ($engine === 'piper') {
    $piperVoices = getPiperVoices();

    if ($requestedVoice !== '' && isset($piperVoices[$requestedVoice])) {
        $voice = $requestedVoice;
    } else {
        $voice = $piperDefaultVoice;
    }
} else {
    $voice = $requestedVoice !== '' ? $requestedVoice : $espeakFallbackVoice;
}

$ttsDir = getTtsDir();
ensureTtsDir($ttsDir);
cleanupOldTtsFiles($ttsDir);

$configDir = $ttsDir . '/.config';

if (!is_dir($configDir)) {
    @mkdir($configDir, 0700, true);
}

@chown($configDir, 'www-data');
@chgrp($configDir, 'www-data');
@chmod($configDir, 0700);

$id = bin2hex(random_bytes(16));
$textPath = $ttsDir . '/' . $id . '.txt';
$wavPath = $ttsDir . '/' . $id . '.wav';

if (file_put_contents($textPath, $text . "\n") === false) {
    failJson('Unable to write temporary TTS text file', 500);
}

@chmod($textPath, 0640);
@chown($textPath, 'www-data');
@chgrp($textPath, 'www-data');

$fallbackUsed = false;
$primaryResult = null;
$finalResult = null;

if ($engine === 'piper') {
    $primaryResult = runPiperTts($textPath, $wavPath, $voice, $speed, $ttsDir);
    $finalResult = $primaryResult;

    if (
        !$primaryResult['ok']
        || !is_file($wavPath)
        || filesize($wavPath) === 0
    ) {
        @unlink($wavPath);

        $fallbackUsed = true;
        $finalResult = runEspeakTts($textPath, $wavPath, $espeakFallbackVoice, $speed, $ttsDir);
    }
} else {
    $primaryResult = runEspeakTts($textPath, $wavPath, $voice, $speed, $ttsDir);
    $finalResult = $primaryResult;
}

@unlink($textPath);

if (
    !is_array($finalResult)
    || !$finalResult['ok']
    || !is_file($wavPath)
    || filesize($wavPath) === 0
) {
    @unlink($wavPath);

    failJson('TTS generation failed', 500, [
        'engine' => $engine,
        'voice' => $voice,
        'fallback_used' => $fallbackUsed,
        'primary' => [
            'engine' => $primaryResult['engine'] ?? null,
            'exit_code' => $primaryResult['exit_code'] ?? null,
            'error' => $primaryResult['error'] ?? null,
            'stderr' => mb_substr((string) ($primaryResult['stderr'] ?? ''), 0, 1000, 'UTF-8'),
        ],
        'final' => [
            'engine' => $finalResult['engine'] ?? null,
            'exit_code' => $finalResult['exit_code'] ?? null,
            'error' => $finalResult['error'] ?? null,
            'stderr' => mb_substr((string) ($finalResult['stderr'] ?? ''), 0, 1000, 'UTF-8'),
        ],
    ]);
}

@chmod($wavPath, 0640);
@chown($wavPath, 'www-data');
@chgrp($wavPath, 'www-data');

successJson([
    'audio' => [
        'id' => $id,
        'url' => 'api/tts.php?id=' . $id,
        'mime' => 'audio/wav',
        'engine' => (string) ($finalResult['engine'] ?? $engine),
        'requested_engine' => $engine,
        'voice' => (string) ($finalResult['voice'] ?? $voice),
        'requested_voice' => $requestedVoice,
        'fallback_used' => $fallbackUsed,
        'speed' => $speed,
        'length_scale' => $finalResult['length_scale'] ?? null,
        'volume' => $engine === 'piper' ? 0.75 : null,
        'size_bytes' => filesize($wavPath),
        'raw_text_chars' => mb_strlen($rawText, 'UTF-8'),
        'spoken_text_chars' => mb_strlen($text, 'UTF-8'),
    ],
]);
