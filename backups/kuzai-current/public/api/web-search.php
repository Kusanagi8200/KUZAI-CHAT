<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(
        array_merge([
            'ok' => false,
            'error' => $message,
        ], $extra),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function successJson(array $data): never
{
    echo json_encode(
        array_merge([
            'ok' => true,
        ], $data),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function cleanText(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

    return trim($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson('Method not allowed', 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '', true);

if (!is_array($data)) {
    failJson('Invalid JSON payload');
}

$query = cleanText((string) ($data['query'] ?? ''));

if ($query === '') {
    failJson('Search query is required');
}

if (mb_strlen($query, 'UTF-8') > 500) {
    failJson('Search query is too long', 413);
}

$limit = (int) ($data['limit'] ?? 5);

if ($limit < 1) {
    $limit = 1;
}

if ($limit > 8) {
    $limit = 8;
}

$searxngUrl = 'http://127.0.0.1:8888/search?' . http_build_query([
    'q' => $query,
    'format' => 'json',
    'language' => 'en',
    'safesearch' => 0,
]);

$ch = curl_init($searxngUrl);

if ($ch === false) {
    failJson('Unable to initialize curl', 500);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: KUZAI-WebSearch/0.1',
        'X-Real-IP: 127.0.0.1',
        'X-Forwarded-For: 127.0.0.1',
    ],
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($responseBody === false || $responseBody === '') {
    failJson('SearXNG request failed', 502, [
        'details' => $curlError,
    ]);
}

$responseData = json_decode($responseBody, true);

if (!is_array($responseData)) {
    failJson('Invalid SearXNG JSON response', 502, [
        'http_code' => $httpCode,
        'raw' => mb_substr($responseBody, 0, 2000, 'UTF-8'),
    ]);
}

if ($httpCode < 200 || $httpCode >= 300) {
    failJson('SearXNG HTTP error', 502, [
        'http_code' => $httpCode,
        'response' => $responseData,
    ]);
}

$rawResults = $responseData['results'] ?? [];

if (!is_array($rawResults)) {
    $rawResults = [];
}

$results = [];

foreach ($rawResults as $item) {
    if (!is_array($item)) {
        continue;
    }

    $title = cleanText((string) ($item['title'] ?? ''));
    $url = cleanText((string) ($item['url'] ?? ''));
    $content = cleanText((string) ($item['content'] ?? ''));

    if ($title === '' || $url === '') {
        continue;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        continue;
    }

    $results[] = [
        'title' => $title,
        'url' => $url,
        'content' => mb_substr($content, 0, 800, 'UTF-8'),
        'engine' => cleanText((string) ($item['engine'] ?? '')),
    ];

    if (count($results) >= $limit) {
        break;
    }
}

successJson([
    'query' => $query,
    'count' => count($results),
    'results' => $results,
]);
