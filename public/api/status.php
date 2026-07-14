<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$healthUrl = rtrim((string) $config['llm']['api_base'], '/') . '/health';
$modelsUrl = (string) $config['llm']['models_endpoint'];

$health = null;
$models = null;
$error = null;
$modelsHttp = 0;

$ch = curl_init($healthUrl);

if ($ch === false) {
    jsonResponse([
        'ok' => false,
        'error' => 'Unable to initialize curl',
    ], 500);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 5,
]);

$healthBody = curl_exec($ch);
$healthHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$healthCurlError = curl_error($ch);
curl_close($ch);

if ($healthBody === false || $healthBody === '') {
    $error = $healthCurlError ?: 'Empty health response';
} else {
    $health = json_decode($healthBody, true);
}

$ch = curl_init($modelsUrl);

if ($ch !== false) {
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 5,
    ]);

    $modelsBody = curl_exec($ch);
    $modelsHttp = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $modelsCurlError = curl_error($ch);
    curl_close($ch);

    if ($modelsBody !== false && $modelsBody !== '') {
        $models = json_decode($modelsBody, true);
    } elseif ($error === null) {
        $error = $modelsCurlError ?: 'Empty models response';
    }
}

$modelName = null;

if (is_array($models) && isset($models['data'][0]['id']) && is_string($models['data'][0]['id'])) {
    $modelName = $models['data'][0]['id'];
} elseif (is_array($models) && isset($models['models'][0]['name']) && is_string($models['models'][0]['name'])) {
    $modelName = $models['models'][0]['name'];
}

$ok = $healthHttp >= 200
    && $healthHttp < 300
    && is_array($health)
    && ($health['status'] ?? null) === 'ok'
    && $modelsHttp >= 200
    && $modelsHttp < 300;

jsonResponse([
    'ok' => $ok,
    'app' => [
        'name' => $config['app']['name'],
        'brand_line' => $config['app']['brand_line'],
        'version' => $config['app']['version'],
    ],
    'llm' => [
        'api_base' => $config['llm']['api_base'],
        'configured_model' => $config['llm']['model'],
        'active_model' => $modelName,
        'health_http_code' => $healthHttp,
        'models_http_code' => $modelsHttp,
    ],
    'error' => $ok ? null : $error,
]);
