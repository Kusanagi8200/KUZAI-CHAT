#### KUZAI - INSTALLATION AND APPLICATION DEVELOPMENT PROCESS

#### GLOBAL SYNOPTIC

```text
[ USER / BROWSER ]
        |
        v
[ APACHE2 / PHP ]
  http://<SERVER_IP>/KUZAI/
        |
        v
[ KUZAI WEB APP ]
  /var/www/html/KUZAI
  - public/index.php
  - public/assets/css/style.css
  - public/assets/js/app.js
  - app/config.php
        |
        +------------------------------+
        |                              |
        v                              v
[ CHAT API ]                     [ STATUS API ]
public/api/chat.php              public/api/status.php
        |                              |
        |                              v
        |                       llama.cpp health/model check
        |
        +---------------------> [ LLAMA.CPP SERVER ]
        |                       http://127.0.0.1:8080
        |                       model: qwen3-8b-q5km
        |
        +------------------------------+
        |                              |
        v                              v
[ UPLOAD API ]                   [ WEB SEARCH API ]
public/api/upload.php            public/api/web-search.php
        |                              |
        v                              v
[ STORAGE ]                      [ SEARXNG LOCAL ]
storage/uploads                  http://127.0.0.1:8888
storage/conversations            systemd: searxng.service
                                 /opt/searxng/src
                                 /opt/searxng/venv
                                 /etc/searxng/settings.yml
                                        |
                                        v
                                 [ SEARCH ENGINES ]
                                 Google / DuckDuckGo / Startpage
                                        |
                                        v
                                 [ WEB RESULTS ]
                                        |
                                        v
                              injected into chat.php
                                        |
                                        v
                              enriched local LLM answer
```

#### PROJECT PRESENTATION

KUZAI is a standalone PHP web application that provides access to a local AI assistant through a custom interface.

The project follows a simple principle: keep inference, files, searches, and services under local control. The application is not a dependency of KUZCHAT LLM DUO. It runs as a standalone application served by Apache2.

The LLM backend is provided by `llama.cpp` on `127.0.0.1:8080`. The model used in this installation is `qwen3-8b-q5km`, based on a Qwen3-8B GGUF file quantized in Q5_K_M.

Web search is provided by a native SearXNG installation, exposed only locally on `127.0.0.1:8888`. The PHP application queries SearXNG through `web-search.php`, then injects the results into `chat.php` to enrich the context sent to the local model.

Upload support allows text files, source code, logs, or JSON files to be sent to the server. Their content is extracted server-side and then injected into the prompt for analysis by the LLM.

#### VALIDATED FEATURES

```text
Local chat through llama.cpp
Custom PHP interface
Status API
Upload of analyzable files
Web search through native SearXNG
Web result injection into the prompt
Automatic addition of web sources in the response
STOP button in the interface
WEB / UPLOAD / SEND / CLEAR / REMOVE buttons
Dynamic response container
Services managed by systemd
Validation through PHP linting, curl, jq, and apache2ctl configtest
```

#### TECHNICAL SPECIFICATIONS

```text
Application: KUZAI
Type: standalone PHP web application
Web server: Apache2
Backend language: PHP 8.3+
Frontend: HTML / CSS / JavaScript
LLM server: llama.cpp server
LLM endpoint: http://127.0.0.1:8080/v1/chat/completions
Model alias: qwen3-8b-q5km
Model file: /opt/llm/models/Qwen3-8B-Q5_K_M.gguf
Web search: SearXNG native install
SearXNG endpoint: http://127.0.0.1:8888/search
Search service: searxng.service
Application path: /var/www/html/KUZAI
Upload storage: /var/www/html/KUZAI/storage/uploads
Conversation storage: /var/www/html/KUZAI/storage/conversations
```

#### TARGET DIRECTORY TREE

```text
/var/www/html/KUZAI
├── app
│   └── config.php
├── public
│   ├── index.php
│   ├── api
│   │   ├── status.php
│   │   ├── chat.php
│   │   ├── upload.php
│   │   └── web-search.php
│   └── assets
│       ├── css
│       │   └── style.css
│       └── js
│           └── app.js
└── storage
    ├── conversations
    └── uploads
```

#### STEP 1 - SYSTEM PREREQUISITES

Install the required packages for Apache2, PHP, curl, jq, Python, venv, and SearXNG dependencies.

```bash
apt update
apt install -y \
  apache2 \
  php \
  php-cli \
  php-curl \
  php-mbstring \
  php-json \
  curl \
  jq \
  git \
  python3 \
  python3-dev \
  python3-venv \
  python3-pip \
  build-essential \
  libxslt1-dev \
  zlib1g-dev \
  libffi-dev \
  libssl-dev \
  libyaml-dev \
  libjpeg-dev \
  libxml2-dev \
  ca-certificates \
  uwsgi \
  uwsgi-plugin-python3 \
  redis-server
```

Check the required PHP modules.

```bash
php -m | grep -Ei 'curl|json|mbstring'
```

#### STEP 2 - KUZAI DIRECTORY PREPARATION

Create the application directory tree.

```bash
mkdir -p /var/www/html/KUZAI/app
mkdir -p /var/www/html/KUZAI/public/api
mkdir -p /var/www/html/KUZAI/public/assets/css
mkdir -p /var/www/html/KUZAI/public/assets/js
mkdir -p /var/www/html/KUZAI/storage/uploads
mkdir -p /var/www/html/KUZAI/storage/conversations

chown -R www-data:www-data /var/www/html/KUZAI
find /var/www/html/KUZAI -type d -exec chmod 755 {} \;
find /var/www/html/KUZAI -type f -exec chmod 644 {} \;
chmod 750 /var/www/html/KUZAI/storage
chmod 750 /var/www/html/KUZAI/storage/uploads
chmod 750 /var/www/html/KUZAI/storage/conversations
```

#### STEP 3 - MAIN APPLICATION CONFIGURATION

Create `/var/www/html/KUZAI/app/config.php`.

```bash
cat > /var/www/html/KUZAI/app/config.php <<'PHP'
<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'KUZAI',
        'brand_line' => 'A KUZ NETWORK SOLUTION - Beta 01.2026',
        'subtitle' => 'LOCAL AI WEB CHAT',
        'version' => '0.1.0',
        'timezone' => 'Europe/Paris',
    ],

    'llm' => [
        'api_base' => 'http://127.0.0.1:8080',
        'chat_endpoint' => 'http://127.0.0.1:8080/v1/chat/completions',
        'models_endpoint' => 'http://127.0.0.1:8080/v1/models',
        'model' => 'qwen3-8b-q5km',

        'temperature' => 0.4,
        'top_p' => 0.9,
        'top_k' => 40,
        'repeat_penalty' => 1.05,
        'max_tokens' => 768,
        'timeout' => 180,

        'no_think' => true,

        'system_prompt' => 'You are KUZAI, a local technical assistant running on a private llama.cpp server. Reply in clear plain text. Be precise, practical, and concise. Do not reveal hidden reasoning. Do not output reasoning_content. Do not use markdown tables unless requested. Do not answer with ellipsis only.',
    ],

    'limits' => [
        'max_user_message_chars' => 8000,
        'max_history_messages' => 20,
        'max_upload_bytes' => 5242880,
        'max_uploaded_text_chars' => 60000,
    ],

    'storage' => [
        'uploads_dir' => '/var/www/html/KUZAI/storage/uploads',
        'conversations_dir' => '/var/www/html/KUZAI/storage/conversations',
    ],

    'web_search' => [
        'endpoint' => 'http://127.0.0.1:8888/search',
        'timeout' => 25,
        'max_results' => 8,
    ],
];
PHP

chown www-data:www-data /var/www/html/KUZAI/app/config.php
chmod 644 /var/www/html/KUZAI/app/config.php
```

#### STEP 4 - MAIN PHP INTERFACE

Create `/var/www/html/KUZAI/public/index.php`.

```bash
cat > /var/www/html/KUZAI/public/index.php <<'PHP'
<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config.php';

$appName = htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8');
$brandLine = htmlspecialchars($config['app']['brand_line'], ENT_QUOTES, 'UTF-8');
$subtitle = htmlspecialchars($config['app']['subtitle'], ENT_QUOTES, 'UTF-8');
$model = htmlspecialchars($config['llm']['model'], ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $appName ?> - <?= $brandLine ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="application-name" content="<?= $appName ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=21">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <div class="logo-box">KZ</div>
                <div>
                    <p class="brand-line"><?= $brandLine ?></p>
                    <h1><?= $appName ?></h1>
                    <p class="subtitle"><?= $subtitle ?></p>
                </div>
            </div>

            <div class="topbar-meta">
                <div class="meta-pill" id="serverState">
                    <span class="meta-dot"></span>
                    <span class="meta-label">SERVER</span>
                    <span class="meta-value">CHECKING</span>
                </div>
            </div>
        </header>

        <main class="layout">
            <section class="chat-panel">
                <div class="chat-header">
                    <div>
                        <p class="section-kicker">KUZAI CHAT</p>
                        <h2>Local conversation</h2>
                    </div>
                    <div class="chat-actions">
                        <button type="button" class="btn btn-secondary" id="clearBtn">CLEAR</button>
                    </div>
                </div>

                <div class="messages" id="messages">
                    <div class="message message-assistant">
                        <div class="message-role">KUZAI</div>
                        <div class="message-content">
                            Local KUZAI session ready. Use WEB for web search, UPLOAD for file analysis, then SEND.
                        </div>
                    </div>
                </div>

                <form class="composer" id="chatForm">
                    <textarea id="promptInput" name="message" placeholder="Write a message..." autocomplete="off"></textarea>

                    <div class="attachments attachments-empty" id="attachments"></div>

                    <div class="web-results web-results-empty" id="webResults"></div>

                    <input class="file-input" id="fileInput" type="file" accept=".txt,.md,.log,.json,.yml,.yaml,.php,.py,.js,.css,.html,.conf,.ini,.sh,.sql,.xml,.csv">

                    <div class="composer-footer">
                        <p class="hint">Ctrl + Enter to send. Escape or STOP to interrupt.</p>
                        <div class="composer-actions">
                            <button type="button" class="btn btn-secondary" id="webBtn">WEB</button>
                            <button type="button" class="btn btn-secondary" id="uploadBtn">UPLOAD</button>
                            <button type="button" class="btn btn-danger btn-hidden" id="stopBtn">STOP</button>
                            <button type="submit" class="btn btn-primary" id="sendBtn">SEND</button>
                        </div>
                    </div>
                </form>
            </section>

            <aside class="side-panel">
                <div class="side-card">
                    <p class="section-kicker">MODEL</p>
                    <h3 id="modelName"><?= $model ?></h3>
                    <p class="small-text">Local model served by llama.cpp.</p>
                </div>

                <div class="side-card">
                    <p class="section-kicker">FEATURES</p>
                    <ul class="feature-list">
                        <li>Local LLM chat</li>
                        <li>File upload analysis</li>
                        <li>Web search via SearXNG</li>
                        <li>Source URL injection</li>
                        <li>Dynamic response area</li>
                    </ul>
                </div>

                <div class="side-card">
                    <p class="section-kicker">ENDPOINTS</p>
                    <div class="endpoint-list">
                        <span>chat.php</span>
                        <span>status.php</span>
                        <span>upload.php</span>
                        <span>web-search.php</span>
                    </div>
                </div>
            </aside>
        </main>
    </div>

    <script src="assets/js/app.js?v=21"></script>
</body>
</html>
PHP

chown www-data:www-data /var/www/html/KUZAI/public/index.php
chmod 644 /var/www/html/KUZAI/public/index.php
```

#### STEP 5 - STATUS API

Create `/var/www/html/KUZAI/public/api/status.php`.

```bash
cat > /var/www/html/KUZAI/public/api/status.php <<'PHP'
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

function httpGetJson(string $url, int $timeout = 5): array
{
    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'ok' => false,
            'http_code' => 0,
            'data' => null,
            'error' => 'curl_init failed',
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: KUZAI-Status/0.1',
        ],
    ]);

    $body = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $body === '') {
        return [
            'ok' => false,
            'http_code' => $httpCode,
            'data' => null,
            'error' => $error ?: 'empty response',
        ];
    }

    $data = json_decode($body, true);

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300 && is_array($data),
        'http_code' => $httpCode,
        'data' => is_array($data) ? $data : null,
        'error' => is_array($data) ? null : 'invalid json',
    ];
}

$health = httpGetJson($config['llm']['api_base'] . '/health');
$models = httpGetJson($config['llm']['models_endpoint']);

$activeModel = null;

if (is_array($models['data'])) {
    if (isset($models['data']['data'][0]['id'])) {
        $activeModel = (string) $models['data']['data'][0]['id'];
    } elseif (isset($models['data']['models'][0]['name'])) {
        $activeModel = (string) $models['data']['models'][0]['name'];
    }
}

$error = null;

if (!$health['ok']) {
    $error = 'llama.cpp health endpoint unavailable';
} elseif (!$models['ok']) {
    $error = 'llama.cpp models endpoint unavailable';
}

echo json_encode([
    'ok' => $error === null,
    'app' => [
        'name' => $config['app']['name'],
        'brand_line' => $config['app']['brand_line'],
        'version' => $config['app']['version'],
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
    ],
    'llm' => [
        'api_base' => $config['llm']['api_base'],
        'configured_model' => $config['llm']['model'],
        'active_model' => $activeModel,
        'health_http_code' => $health['http_code'],
        'models_http_code' => $models['http_code'],
    ],
    'error' => $error,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
PHP

chown www-data:www-data /var/www/html/KUZAI/public/api/status.php
chmod 644 /var/www/html/KUZAI/public/api/status.php
```

#### STEP 6 - UPLOAD API

Create `/var/www/html/KUZAI/public/api/upload.php`.

```bash
cat > /var/www/html/KUZAI/public/api/upload.php <<'PHP'
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function successJson(array $data): never
{
    echo json_encode(array_merge([
        'ok' => true,
    ], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalizeText(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    $text = preg_replace('/\n{4,}/', "\n\n\n", $text) ?? $text;

    return trim($text);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson('Method not allowed', 405);
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    failJson('No file uploaded');
}

$file = $_FILES['file'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    failJson('Upload failed', 400, [
        'upload_error' => $file['error'] ?? null,
    ]);
}

$maxBytes = (int) ($config['limits']['max_upload_bytes'] ?? 5242880);
$size = (int) ($file['size'] ?? 0);

if ($size <= 0) {
    failJson('Uploaded file is empty');
}

if ($size > $maxBytes) {
    failJson('Uploaded file is too large', 413, [
        'max_bytes' => $maxBytes,
    ]);
}

$originalName = (string) ($file['name'] ?? 'upload.txt');
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$allowed = [
    'txt', 'md', 'log', 'json', 'yml', 'yaml', 'php', 'py', 'js', 'css', 'html',
    'conf', 'ini', 'sh', 'sql', 'xml', 'csv', 'env', 'service', 'cfg'
];

if (!in_array($extension, $allowed, true)) {
    failJson('Unsupported file extension', 415, [
        'extension' => $extension,
        'allowed' => $allowed,
    ]);
}

$tmpName = (string) ($file['tmp_name'] ?? '');

if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    failJson('Invalid upload temporary file');
}

$content = file_get_contents($tmpName);

if ($content === false) {
    failJson('Unable to read uploaded file', 500);
}

$text = normalizeText($content);
$maxChars = (int) ($config['limits']['max_uploaded_text_chars'] ?? 60000);
$truncated = false;

if (mb_strlen($text, 'UTF-8') > $maxChars) {
    $text = mb_substr($text, 0, $maxChars, 'UTF-8');
    $truncated = true;
}

$id = bin2hex(random_bytes(16));
$uploadsDir = rtrim((string) $config['storage']['uploads_dir'], '/');

if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0750, true) && !is_dir($uploadsDir)) {
    failJson('Unable to create uploads directory', 500);
}

$storedPath = $uploadsDir . '/' . $id . '.json';

$record = [
    'id' => $id,
    'original_name' => $originalName,
    'extension' => $extension,
    'size_bytes' => $size,
    'text_chars' => mb_strlen($text, 'UTF-8'),
    'truncated' => $truncated,
    'created_at' => date('c'),
    'text' => $text,
];

if (file_put_contents($storedPath, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n") === false) {
    failJson('Unable to store uploaded file metadata', 500);
}

@chmod($storedPath, 0640);
@chown($storedPath, 'www-data');
@chgrp($storedPath, 'www-data');

successJson([
    'file' => [
        'id' => $id,
        'original_name' => $originalName,
        'extension' => $extension,
        'size_bytes' => $size,
        'text_chars' => mb_strlen($text, 'UTF-8'),
        'truncated' => $truncated,
        'preview' => mb_substr($text, 0, 1200, 'UTF-8'),
    ],
]);
PHP

chown www-data:www-data /var/www/html/KUZAI/public/api/upload.php
chmod 644 /var/www/html/KUZAI/public/api/upload.php
```

#### STEP 7 - WEB SEARCH API

Create `/var/www/html/KUZAI/public/api/web-search.php`.

```bash
cat > /var/www/html/KUZAI/public/api/web-search.php <<'PHP'
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function successJson(array $data): never
{
    echo json_encode(array_merge([
        'ok' => true,
    ], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

$maxResults = (int) ($config['web_search']['max_results'] ?? 8);

if ($limit > $maxResults) {
    $limit = $maxResults;
}

$endpoint = (string) ($config['web_search']['endpoint'] ?? 'http://127.0.0.1:8888/search');
$searxngUrl = $endpoint . '?' . http_build_query([
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
    CURLOPT_TIMEOUT => (int) ($config['web_search']['timeout'] ?? 25),
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
PHP

chown www-data:www-data /var/www/html/KUZAI/public/api/web-search.php
chmod 644 /var/www/html/KUZAI/public/api/web-search.php
```

#### STEP 8 - CHAT API

Create `/var/www/html/KUZAI/public/api/chat.php`.

```bash
cat > /var/www/html/KUZAI/public/api/chat.php <<'PHP'
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../../app/config.php';

function failJson(string $message, int $code = 400, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(array_merge([
        'ok' => false,
        'error' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cleanMessageContent(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
    $value = preg_replace('/\n{3,}/', "\n\n", $value) ?? $value;

    return trim($value);
}

function loadUploadedFileContext(array $config, array $attachments): string
{
    $uploadsDir = rtrim((string) ($config['storage']['uploads_dir'] ?? '/var/www/html/KUZAI/storage/uploads'), '/');
    $blocks = [];
    $count = 0;

    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        $id = (string) ($attachment['id'] ?? '');

        if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
            continue;
        }

        $path = $uploadsDir . '/' . $id . '.json';

        if (!is_file($path)) {
            continue;
        }

        $raw = file_get_contents($path);

        if ($raw === false) {
            continue;
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            continue;
        }

        $name = cleanMessageContent((string) ($data['original_name'] ?? $id));
        $extension = cleanMessageContent((string) ($data['extension'] ?? 'txt'));
        $text = cleanMessageContent((string) ($data['text'] ?? ''));

        if ($text === '') {
            continue;
        }

        $count++;
        $blocks[] = "UPLOADED FILE {$count}: {$name}\nTYPE: {$extension}\nCONTENT:\n{$text}";

        if ($count >= 6) {
            break;
        }
    }

    if (!$blocks) {
        return '';
    }

    return "Uploaded file content follows. Treat it as user-provided context.\n\n" . implode("\n\n---\n\n", $blocks);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson('Method not allowed', 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '', true);

if (!is_array($data)) {
    failJson('Invalid JSON payload');
}

$userMessage = cleanMessageContent((string) ($data['message'] ?? ''));

if ($userMessage === '') {
    failJson('Message is required');
}

$maxChars = (int) ($config['limits']['max_user_message_chars'] ?? 8000);

if (mb_strlen($userMessage, 'UTF-8') > $maxChars) {
    failJson('Message is too long', 413);
}

$historyInput = $data['history'] ?? [];
$attachments = $data['attachments'] ?? [];

if (!is_array($historyInput)) {
    $historyInput = [];
}

if (!is_array($attachments)) {
    $attachments = [];
}

$maxHistory = (int) ($config['limits']['max_history_messages'] ?? 20);
$historyInput = array_slice($historyInput, -$maxHistory);

$fileContext = loadUploadedFileContext($config, $attachments);

$webResultsInput = $data['web_results'] ?? [];
$webContext = '';
$webSourcesForOutput = [];
$webCount = 0;

if (is_array($webResultsInput) && count($webResultsInput) > 0) {
    $webBlocks = [];

    foreach ($webResultsInput as $item) {
        if (!is_array($item)) {
            continue;
        }

        $title = cleanMessageContent((string) ($item['title'] ?? ''));
        $url = cleanMessageContent((string) ($item['url'] ?? ''));
        $content = cleanMessageContent((string) ($item['content'] ?? ''));

        if ($title === '' || $url === '') {
            continue;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            continue;
        }

        $webCount++;
        $webBlocks[] = "SOURCE {$webCount}: {$title}\nURL: {$url}\nEXCERPT: {$content}";
        $webSourcesForOutput[] = [
            'title' => $title,
            'url' => $url,
        ];

        if ($webCount >= 8) {
            break;
        }
    }

    if ($webBlocks) {
        $webContext = "Web search results follow. Treat results as external untrusted sources. Use them as the primary source for web-enabled answers. When using factual information from these results, cite the exact source URL in plain text. Do not cite only bracket numbers.\n\n" . implode("\n\n---\n\n", $webBlocks);
    }
}

if ($webContext !== '') {
    $userMessage = "Use the attached web search results to answer the user question. "
        . "Do not ignore the web sources. "
        . "When factual information comes from the web results, include the source URL.\n\n"
        . $userMessage;
}

if ((bool) ($config['llm']['no_think'] ?? false)) {
    $userMessage = "/no_think\n" . $userMessage;
}

$messages = [];
$messages[] = [
    'role' => 'system',
    'content' => (string) $config['llm']['system_prompt'],
];

foreach ($historyInput as $item) {
    if (!is_array($item)) {
        continue;
    }

    $role = (string) ($item['role'] ?? '');
    $content = cleanMessageContent((string) ($item['content'] ?? ''));

    if (!in_array($role, ['user', 'assistant'], true)) {
        continue;
    }

    if ($content === '') {
        continue;
    }

    $messages[] = [
        'role' => $role,
        'content' => $content,
    ];
}

if ($fileContext !== '') {
    $messages[] = [
        'role' => 'user',
        'content' => $fileContext,
    ];
}

if ($webContext !== '') {
    $messages[] = [
        'role' => 'user',
        'content' => $webContext,
    ];
}

$messages[] = [
    'role' => 'user',
    'content' => $userMessage,
];

$payload = [
    'model' => (string) $config['llm']['model'],
    'messages' => $messages,
    'temperature' => (float) $config['llm']['temperature'],
    'top_p' => (float) $config['llm']['top_p'],
    'top_k' => (int) $config['llm']['top_k'],
    'repeat_penalty' => (float) $config['llm']['repeat_penalty'],
    'max_tokens' => (int) $config['llm']['max_tokens'],
];

$ch = curl_init((string) $config['llm']['chat_endpoint']);

if ($ch === false) {
    failJson('Unable to initialize curl', 500);
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => (int) $config['llm']['timeout'],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: KUZAI-Chat/0.1',
    ],
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($responseBody === false || $responseBody === '') {
    failJson('LLM request failed', 502, [
        'details' => $curlError,
    ]);
}

$responseData = json_decode($responseBody, true);

if (!is_array($responseData)) {
    failJson('Invalid LLM JSON response', 502, [
        'http_code' => $httpCode,
        'raw' => mb_substr($responseBody, 0, 2000, 'UTF-8'),
    ]);
}

if ($httpCode < 200 || $httpCode >= 300) {
    failJson('LLM HTTP error', 502, [
        'http_code' => $httpCode,
        'response' => $responseData,
    ]);
}

$content = '';

if (isset($responseData['choices'][0]['message']['content'])) {
    $content = cleanMessageContent((string) $responseData['choices'][0]['message']['content']);
}

if ($content === '') {
    $content = '[Empty model response]';
}

if (!empty($webSourcesForOutput)) {
    $sourceLines = [];

    foreach ($webSourcesForOutput as $source) {
        if (!is_array($source)) {
            continue;
        }

        $sourceTitle = cleanMessageContent((string) ($source['title'] ?? ''));
        $sourceUrl = cleanMessageContent((string) ($source['url'] ?? ''));

        if ($sourceTitle === '' || $sourceUrl === '') {
            continue;
        }

        $sourceLines[] = '- ' . $sourceTitle . ': ' . $sourceUrl;
    }

    if ($sourceLines) {
        $content .= "\n\nSources:\n" . implode("\n", $sourceLines);
    }
}

echo json_encode([
    'ok' => true,
    'model' => (string) $config['llm']['model'],
    'message' => [
        'role' => 'assistant',
        'content' => $content,
    ],
    'usage' => $responseData['usage'] ?? null,
    'finish_reason' => $responseData['choices'][0]['finish_reason'] ?? null,
    'attachments_used' => count($attachments),
    'web_results_used' => $webCount,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
PHP

chown www-data:www-data /var/www/html/KUZAI/public/api/chat.php
chmod 644 /var/www/html/KUZAI/public/api/chat.php
```

#### STEP 9 - JAVASCRIPT FRONTEND

Create `/var/www/html/KUZAI/public/assets/js/app.js`.

```bash
cat > /var/www/html/KUZAI/public/assets/js/app.js <<'JS'
'use strict';

const messagesEl = document.getElementById('messages');
const chatForm = document.getElementById('chatForm');
const promptInput = document.getElementById('promptInput');
const sendBtn = document.getElementById('sendBtn');
const stopBtn = document.getElementById('stopBtn');
const clearBtn = document.getElementById('clearBtn');
const uploadBtn = document.getElementById('uploadBtn');
const webBtn = document.getElementById('webBtn');
const fileInput = document.getElementById('fileInput');
const attachmentsEl = document.getElementById('attachments');
const webResultsEl = document.getElementById('webResults');
const serverState = document.getElementById('serverState');
const modelName = document.getElementById('modelName');

const STORAGE_KEY = 'kuzai.history.v1';

let history = loadHistory();
let busy = false;
let uploading = false;
let searching = false;
let currentController = null;
let currentPendingMessage = null;
let attachments = [];
let webResults = [];

function loadHistory() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed.filter((item) => {
            return item
                && typeof item.role === 'string'
                && typeof item.content === 'string'
                && item.content.trim() !== '';
        });
    } catch (error) {
        return [];
    }
}

function saveHistory() {
    const trimmed = history.slice(-40);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(trimmed));
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function renderContent(content) {
    const safe = escapeHtml(content);
    return safe.replace(/\n/g, '<br>');
}

function appendMessage(role, content, persist = true) {
    const wrapper = document.createElement('div');
    wrapper.className = `message message-${role}`;

    const roleLabel = role === 'user' ? 'USER' : 'KUZAI';

    wrapper.innerHTML = `
        <div class="message-role">${roleLabel}</div>
        <div class="message-content">${renderContent(content)}</div>
    `;

    messagesEl.appendChild(wrapper);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (persist) {
        history.push({ role, content });
        history = history.slice(-40);
        saveHistory();
    }

    return wrapper;
}

function updateMessage(wrapper, content) {
    if (!wrapper) {
        return;
    }

    const contentEl = wrapper.querySelector('.message-content');

    if (contentEl) {
        contentEl.innerHTML = renderContent(content);
    }
}

function renderInitialHistory() {
    if (history.length === 0) {
        return;
    }

    messagesEl.innerHTML = '';

    for (const item of history) {
        appendMessage(item.role, item.content, false);
    }
}

function formatBytes(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

function renderAttachments() {
    attachmentsEl.innerHTML = '';

    if (attachments.length === 0) {
        attachmentsEl.classList.add('attachments-empty');
        return;
    }

    attachmentsEl.classList.remove('attachments-empty');

    for (const item of attachments) {
        const chip = document.createElement('div');
        chip.className = 'attachment-chip';

        const truncated = item.truncated ? ' · truncated' : '';
        const size = typeof item.size_bytes === 'number' ? ` · ${formatBytes(item.size_bytes)}` : '';

        chip.innerHTML = `
            <div class="attachment-main">
                <span class="attachment-name">${escapeHtml(item.original_name)}</span>
                <span class="attachment-meta">${escapeHtml(item.extension)}${size}${truncated}</span>
            </div>
            <button type="button" class="attachment-remove" data-id="${escapeHtml(item.id)}" title="Remove attachment" aria-label="Remove attachment">REMOVE</button>
        `;

        attachmentsEl.appendChild(chip);
    }
}

function renderWebResults() {
    webResultsEl.innerHTML = '';

    if (webResults.length === 0) {
        webResultsEl.classList.add('web-results-empty');
        return;
    }

    webResultsEl.classList.remove('web-results-empty');

    for (const item of webResults) {
        const chip = document.createElement('div');
        chip.className = 'web-result-chip';

        chip.innerHTML = `
            <div class="web-result-main">
                <span class="web-result-title">${escapeHtml(item.title)}</span>
                <span class="web-result-url">${escapeHtml(item.url)}</span>
            </div>
            <button type="button" class="web-result-remove" data-url="${escapeHtml(item.url)}" title="Remove web source" aria-label="Remove web source">REMOVE</button>
        `;

        webResultsEl.appendChild(chip);
    }
}

function setBusy(state) {
    busy = state;

    sendBtn.disabled = state || uploading || searching;
    promptInput.disabled = state;
    uploadBtn.disabled = state || uploading || searching;

    if (webBtn) {
        webBtn.disabled = state || uploading || searching;
    }

    sendBtn.textContent = state ? 'GENERATING...' : 'SEND';

    if (stopBtn) {
        stopBtn.classList.toggle('btn-hidden', !state);
        stopBtn.disabled = !state;
    }
}

function setUploading(state) {
    uploading = state;

    uploadBtn.disabled = state || busy || searching;
    sendBtn.disabled = state || busy || searching;

    if (webBtn) {
        webBtn.disabled = state || busy || searching;
    }

    uploadBtn.textContent = state ? 'UPLOADING...' : 'UPLOAD';
}

function setSearching(state) {
    searching = state;

    if (webBtn) {
        webBtn.disabled = state || busy || uploading;
        webBtn.textContent = state ? 'SEARCHING...' : 'WEB';
    }

    uploadBtn.disabled = state || busy || uploading;
    sendBtn.disabled = state || busy || uploading;
}

function setServerState(ok, label) {
    serverState.classList.toggle('state-ok', ok);
    serverState.classList.toggle('state-down', !ok);

    const value = serverState.querySelector('.meta-value');

    if (value) {
        value.textContent = label;
    }
}

async function checkServer() {
    try {
        const response = await fetch('api/status.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (response.ok && data.ok) {
            setServerState(true, 'ONLINE');

            if (data.llm && typeof data.llm.active_model === 'string' && data.llm.active_model !== '') {
                modelName.textContent = data.llm.active_model;
            }

            return;
        }

        setServerState(false, 'ERROR');
    } catch (error) {
        setServerState(false, 'OFFLINE');
    }
}

async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    const response = await fetch('api/upload.php', {
        method: 'POST',
        body: formData,
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
        const error = data && data.error ? data.error : 'Upload failed';
        throw new Error(error);
    }

    if (!data.file || typeof data.file.id !== 'string') {
        throw new Error('Invalid upload response');
    }

    return data.file;
}

async function searchWeb(query) {
    const response = await fetch('api/web-search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            query,
            limit: 5,
        }),
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
        const error = data && data.error ? data.error : 'Web search failed';
        throw new Error(error);
    }

    if (!Array.isArray(data.results)) {
        throw new Error('Invalid web search response');
    }

    return data.results;
}

async function sendMessage(message, signal) {
    const payloadHistory = history.slice(-20);

    const response = await fetch('api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        signal,
        body: JSON.stringify({
            message,
            history: payloadHistory,
            attachments: attachments.map((item) => ({
                id: item.id,
            })),
            web_results: webResults.map((item) => ({
                title: item.title,
                url: item.url,
                content: item.content,
                engine: item.engine,
            })),
        }),
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
        const error = data && data.error ? data.error : 'API error';
        throw new Error(error);
    }

    if (!data.message || typeof data.message.content !== 'string') {
        throw new Error('Invalid response');
    }

    return data.message.content;
}

function stopCurrentGeneration() {
    if (!busy || !currentController) {
        return;
    }

    currentController.abort();
}

function buildUserDisplayMessage(message) {
    const lines = [message];

    if (attachments.length > 0) {
        lines.push('');
        lines.push('Attached files:');

        for (const item of attachments) {
            lines.push(`- ${item.original_name}`);
        }
    }

    if (webResults.length > 0) {
        lines.push('');
        lines.push('Web sources:');

        for (const item of webResults) {
            lines.push(`- ${item.title}`);
        }
    }

    return lines.join('\n');
}

chatForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (busy || uploading || searching) {
        return;
    }

    const message = promptInput.value.trim();

    if (!message && attachments.length === 0 && webResults.length === 0) {
        return;
    }

    const finalMessage = message || 'Analyze the attached context.';

    promptInput.value = '';
    appendMessage('user', buildUserDisplayMessage(finalMessage), true);

    currentPendingMessage = appendMessage('assistant', 'Generating...', false);
    currentController = new AbortController();

    setBusy(true);

    try {
        const answer = await sendMessage(finalMessage, currentController.signal);
        updateMessage(currentPendingMessage, answer);

        history.push({
            role: 'assistant',
            content: answer,
        });

        history = history.slice(-40);
        saveHistory();

        attachments = [];
        webResults = [];

        renderAttachments();
        renderWebResults();

        setServerState(true, 'ONLINE');
    } catch (error) {
        if (error.name === 'AbortError') {
            updateMessage(currentPendingMessage, '[Generation stopped by user]');
        } else {
            const messageError = `Error: ${error.message}`;
            updateMessage(currentPendingMessage, messageError);
            setServerState(false, 'ERROR');
        }
    } finally {
        currentController = null;
        currentPendingMessage = null;
        setBusy(false);
        promptInput.focus();
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
});

if (webBtn) {
    webBtn.addEventListener('click', async () => {
        if (busy || uploading || searching) {
            return;
        }

        const query = promptInput.value.trim();

        if (!query) {
            appendMessage('assistant', 'Web search requires text in the message field.', false);
            promptInput.focus();
            return;
        }

        setSearching(true);

        try {
            const results = await searchWeb(query);

            if (results.length === 0) {
                appendMessage('assistant', 'Web search returned no results.', false);
                return;
            }

            webResults = results;
            renderWebResults();
            setServerState(true, 'ONLINE');
        } catch (error) {
            appendMessage('assistant', `Web search error: ${error.message}`, false);
            setServerState(false, 'ERROR');
        } finally {
            setSearching(false);
            promptInput.focus();
        }
    });
}

if (uploadBtn && fileInput) {
    uploadBtn.addEventListener('click', () => {
        if (busy || uploading || searching) {
            return;
        }

        fileInput.value = '';
        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

        if (!file) {
            return;
        }

        setUploading(true);

        try {
            const uploaded = await uploadFile(file);

            attachments.push({
                id: uploaded.id,
                original_name: uploaded.original_name,
                extension: uploaded.extension,
                size_bytes: uploaded.size_bytes,
                text_chars: uploaded.text_chars,
                truncated: uploaded.truncated,
            });

            renderAttachments();
            setServerState(true, 'ONLINE');
        } catch (error) {
            appendMessage('assistant', `Upload error: ${error.message}`, false);
            setServerState(false, 'ERROR');
        } finally {
            setUploading(false);
            promptInput.focus();
        }
    });
}

if (attachmentsEl) {
    attachmentsEl.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (!target.classList.contains('attachment-remove')) {
            return;
        }

        const id = target.getAttribute('data-id');

        if (!id) {
            return;
        }

        attachments = attachments.filter((item) => item.id !== id);
        renderAttachments();
    });
}

if (webResultsEl) {
    webResultsEl.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (!target.classList.contains('web-result-remove')) {
            return;
        }

        const url = target.getAttribute('data-url');

        if (!url) {
            return;
        }

        webResults = webResults.filter((item) => item.url !== url);
        renderWebResults();
    });
}

if (stopBtn) {
    stopBtn.addEventListener('click', () => {
        stopCurrentGeneration();
    });
}

promptInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && event.ctrlKey) {
        event.preventDefault();
        chatForm.requestSubmit();
    }

    if (event.key === 'Escape' && busy) {
        event.preventDefault();
        stopCurrentGeneration();
    }
});

clearBtn.addEventListener('click', () => {
    if (busy) {
        stopCurrentGeneration();
    }

    history = [];
    attachments = [];
    webResults = [];

    localStorage.removeItem(STORAGE_KEY);

    messagesEl.innerHTML = '';

    appendMessage(
        'assistant',
        'Conversation cleared. New KUZAI session ready.',
        false
    );

    renderAttachments();
    renderWebResults();
    promptInput.focus();
});

renderInitialHistory();
renderAttachments();
renderWebResults();
checkServer();
JS

chown www-data:www-data /var/www/html/KUZAI/public/assets/js/app.js
chmod 644 /var/www/html/KUZAI/public/assets/js/app.js
```

#### STEP 10 - COMPLETE CSS

Create `/var/www/html/KUZAI/public/assets/css/style.css`.

```bash
cat > /var/www/html/KUZAI/public/assets/css/style.css <<'CSS'
:root {
    --bg-main: #06101f;
    --bg-soft: #0c1a30;
    --bg-panel: rgba(10, 22, 42, 0.88);
    --bg-panel-strong: rgba(10, 22, 42, 0.96);
    --bg-input: rgba(4, 14, 28, 0.94);
    --text-main: #f7fbff;
    --text-soft: #dcecff;
    --text-dim: #96abc6;
    --accent: #7bf2ff;
    --accent-2: #9fd6ff;
    --danger: #ff7b9d;
    --success: #67f3ba;
    --border: rgba(159, 214, 255, 0.22);
    --border-strong: rgba(123, 242, 255, 0.48);
    --shadow: 0 18px 60px rgba(0, 0, 0, 0.34);
    --radius-xl: 22px;
    --radius-lg: 16px;
}

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    min-height: 100%;
    background:
        radial-gradient(circle at 10% 0%, rgba(123, 242, 255, 0.14), transparent 28%),
        radial-gradient(circle at 90% 0%, rgba(159, 214, 255, 0.12), transparent 30%),
        linear-gradient(180deg, #050b16 0%, #071326 55%, #0b1c36 100%);
    color: var(--text-main);
    font-family: "Segoe UI", "Inter", Arial, sans-serif;
}

body {
    min-height: 100vh;
}

button,
textarea {
    font-family: inherit;
}

.app-shell {
    width: min(1500px, calc(100% - 28px));
    margin: 0 auto;
    padding: 20px 0 36px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 16px 18px;
    margin-bottom: 18px;
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    background: rgba(8, 20, 39, 0.86);
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
}

.brand {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logo-box {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    border: 1px solid var(--border-strong);
    background: linear-gradient(135deg, rgba(13, 49, 85, 0.95), rgba(7, 21, 43, 0.96));
    color: var(--accent);
    font-weight: 900;
    letter-spacing: 0.08em;
    box-shadow: 0 0 22px rgba(123, 242, 255, 0.12);
}

.brand-line {
    margin: 0 0 4px;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: 0.14em;
    font-size: 0.72rem;
    font-weight: 800;
}

.brand h1 {
    margin: 0;
    font-size: 1.85rem;
    line-height: 1.05;
}

.subtitle {
    margin: 4px 0 0;
    color: var(--text-dim);
    font-size: 0.88rem;
    letter-spacing: 0.08em;
}

.topbar-meta {
    display: flex;
    align-items: center;
}

.meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 13px;
    border: 1px solid var(--border);
    border-radius: 999px;
    background: rgba(8, 23, 42, 0.84);
    color: var(--text-soft);
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.06em;
}

.meta-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--text-dim);
    box-shadow: 0 0 10px rgba(150, 171, 198, 0.5);
}

.state-ok .meta-dot {
    background: var(--success);
    box-shadow: 0 0 12px rgba(103, 243, 186, 0.85);
}

.state-down .meta-dot {
    background: var(--danger);
    box-shadow: 0 0 12px rgba(255, 123, 157, 0.8);
}

.layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 18px;
    align-items: start;
}

.chat-panel,
.side-card {
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    background: var(--bg-panel);
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
}

.chat-panel {
    align-self: start;
    display: grid;
    grid-template-rows: auto auto auto;
    overflow: visible;
}

.chat-header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 16px;
    min-height: 58px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
}

.chat-header > div:first-child {
    min-width: 0;
}

.section-kicker {
    margin: 0 0 3px;
    color: var(--accent);
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.chat-header h2,
.side-card h3 {
    margin: 0;
    color: var(--text-main);
}

.chat-header h2 {
    font-size: 0.95rem;
}

.chat-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}

.messages {
    height: auto;
    min-height: 220px;
    max-height: none;
    overflow: visible;
    display: grid;
    align-content: start;
    gap: 10px;
    padding: 18px 20px;
    scroll-behavior: smooth;
}

.message {
    max-width: 100%;
    border: 1px solid rgba(159, 214, 255, 0.16);
    border-radius: 16px;
    padding: 12px 14px;
    background: rgba(7, 18, 34, 0.72);
}

.message-user {
    margin-left: auto;
    border-color: rgba(123, 242, 255, 0.24);
    background: rgba(14, 47, 74, 0.66);
}

.message-assistant {
    margin-right: auto;
    border-color: rgba(159, 214, 255, 0.2);
    background: rgba(9, 26, 49, 0.78);
}

.message-role {
    color: var(--accent);
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    margin-bottom: 7px;
}

.message-content {
    color: var(--text-soft);
    font-size: 0.94rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.composer {
    display: grid;
    gap: 8px;
    padding: 12px 16px 14px;
    border-top: 1px solid var(--border);
    background: rgba(5, 16, 30, 0.72);
}

textarea {
    width: 100%;
    min-height: 72px;
    max-height: 130px;
    resize: vertical;
    padding: 10px 12px;
    border: 1px solid rgba(159, 214, 255, 0.24);
    border-radius: var(--radius-lg);
    outline: none;
    background: var(--bg-input);
    color: var(--text-main);
    line-height: 1.45;
    font-size: 0.9rem;
}

textarea:focus {
    border-color: var(--border-strong);
    box-shadow: 0 0 0 3px rgba(123, 242, 255, 0.08);
}

textarea:disabled {
    opacity: 0.65;
}

.file-input {
    display: none;
}

.attachments,
.web-results {
    display: grid;
    gap: 7px;
}

.attachments-empty,
.web-results-empty {
    display: none;
}

.attachment-chip,
.web-result-chip {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 12px;
    padding: 8px 12px;
    border: 1px solid rgba(123, 242, 255, 0.22);
    border-radius: 12px;
    background: rgba(14, 40, 66, 0.82);
}

.attachment-main,
.web-result-main {
    display: grid;
    gap: 3px;
    min-width: 0;
}

.attachment-name,
.web-result-title {
    color: var(--text-main);
    font-size: 0.86rem;
    font-weight: 800;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attachment-meta,
.web-result-url {
    color: var(--text-dim);
    font-size: 0.7rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attachment-remove,
.web-result-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: auto;
    min-width: 66px;
    height: 28px;
    min-height: 28px;
    padding: 0 11px;
    border-radius: 999px;
    border: 1px solid rgba(255, 123, 157, 0.38);
    background: rgba(82, 18, 38, 0.24);
    color: #ffd6e1;
    cursor: pointer;
    font-size: 0.66rem;
    font-weight: 850;
    letter-spacing: 0.07em;
    line-height: 1;
    transform: none;
}

.attachment-remove:hover,
.web-result-remove:hover {
    background: rgba(255, 123, 157, 0.18);
    border-color: rgba(255, 123, 157, 0.68);
}

.composer-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}

.composer-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.hint,
.small-text {
    color: var(--text-dim);
    font-size: 0.82rem;
    line-height: 1.5;
}

.btn {
    border: none;
    cursor: pointer;
    font-weight: 900;
    letter-spacing: 0.08em;
    transition: transform 0.18s ease, opacity 0.18s ease, border-color 0.18s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.58;
    cursor: not-allowed;
    transform: none;
}

.btn-primary,
.btn-secondary,
.btn-danger {
    min-height: 34px;
    height: 34px;
    padding: 0 14px;
    border-radius: 12px;
    font-size: 0.72rem;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-primary {
    min-width: 68px;
    color: #031124;
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
}

.btn-secondary {
    color: var(--text-main);
    background: rgba(12, 31, 58, 0.88);
    border: 1px solid rgba(159, 214, 255, 0.28);
}

.btn-danger {
    color: #ffe6ee;
    background: rgba(82, 18, 38, 0.55);
    border: 1px solid rgba(255, 123, 157, 0.4);
}

.btn-hidden {
    display: none !important;
}

#clearBtn {
    min-width: 74px;
    width: auto;
}

#webBtn {
    min-width: 64px;
}

#uploadBtn {
    min-width: 82px;
}

#sendBtn {
    min-width: 68px;
}

.side-panel {
    display: grid;
    gap: 18px;
}

.side-card {
    padding: 18px;
}

.feature-list {
    margin: 10px 0 0;
    padding-left: 18px;
    color: var(--text-soft);
    line-height: 1.7;
    font-size: 0.92rem;
}

.endpoint-list {
    display: grid;
    gap: 8px;
    margin-top: 10px;
}

.endpoint-list span {
    display: block;
    padding: 8px 10px;
    border-radius: 10px;
    border: 1px solid rgba(159, 214, 255, 0.16);
    background: rgba(5, 16, 30, 0.58);
    color: var(--text-soft);
    font-size: 0.82rem;
}

@media (max-width: 1100px) {
    .layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .app-shell {
        width: min(100% - 18px, 1500px);
        padding-top: 12px;
    }

    .topbar,
    .brand,
    .composer-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .brand {
        align-items: flex-start;
    }

    .brand h1 {
        font-size: 1.55rem;
    }

    .message {
        max-width: 100%;
    }

    .attachment-chip,
    .web-result-chip {
        grid-template-columns: 1fr;
    }

    .composer-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .btn {
        width: 100%;
    }
}
CSS

chown www-data:www-data /var/www/html/KUZAI/public/assets/css/style.css
chmod 644 /var/www/html/KUZAI/public/assets/css/style.css
```

#### STEP 11 - LLAMA.CPP SERVICE FOR QWEN

Create or adapt `/etc/systemd/system/llama-server-a.service`.

```bash
cat > /etc/systemd/system/llama-server-a.service <<'EOF'
[Unit]
Description=llama.cpp server - KUZAI local model
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=llm
Group=llm
WorkingDirectory=/opt/src/llama.cpp
ExecStart=/opt/src/llama.cpp/build/bin/llama-server \
  -m /opt/llm/models/Qwen3-8B-Q5_K_M.gguf \
  --alias qwen3-8b-q5km \
  --host 0.0.0.0 \
  --port 8080
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now llama-server-a.service
```

Check the llama.cpp server.

```bash
systemctl status llama-server-a.service --no-pager
curl -s http://127.0.0.1:8080/health
curl -s http://127.0.0.1:8080/v1/models | jq .
```

Direct model test.

```bash
curl -s http://127.0.0.1:8080/v1/chat/completions \
  -H 'Content-Type: application/json' \
  -d '{
    "model": "qwen3-8b-q5km",
    "messages": [
      {
        "role": "user",
        "content": "Answer only: QWEN_OK"
      }
    ],
    "temperature": 0.1,
    "max_tokens": 32
  }' | jq .
```

#### STEP 12 - NATIVE SEARXNG INSTALLATION

Create the system user.

```bash
id searxng 2>/dev/null || useradd --system --home-dir /opt/searxng --shell /usr/sbin/nologin searxng
mkdir -p /opt/searxng
chown -R searxng:searxng /opt/searxng
```

Clone SearXNG.

```bash
if [ ! -d /opt/searxng/src/.git ]; then
  sudo -u searxng git clone https://github.com/searxng/searxng.git /opt/searxng/src
else
  sudo -u searxng git -C /opt/searxng/src pull --ff-only
fi
```

Create the Python venv and install dependencies.

```bash
sudo -u searxng python3 -m venv /opt/searxng/venv
sudo -u searxng /opt/searxng/venv/bin/python -m pip install --upgrade pip setuptools wheel
sudo -u searxng /opt/searxng/venv/bin/python -m pip install \
  -r /opt/searxng/src/requirements.txt \
  -r /opt/searxng/src/requirements-server.txt
sudo -u searxng /opt/searxng/venv/bin/python -m pip install \
  -e /opt/searxng/src \
  --no-build-isolation

/opt/searxng/venv/bin/python - <<'PY'
import msgspec
import searx
print('MSG_SPEC_OK')
print('SEARX_IMPORT_OK')
PY
```

#### STEP 13 - SEARXNG CONFIGURATION

Create `/etc/searxng/settings.yml`.

```bash
mkdir -p /etc/searxng
chown root:searxng /etc/searxng
chmod 750 /etc/searxng

SECRET_KEY="$(openssl rand -hex 32)"

cat > /etc/searxng/settings.yml <<'SEARXCONF'
use_default_settings: true

server:
  bind_address: "127.0.0.1"
  port: 8888
  secret_key: "CHANGE_ME_WITH_OPENSSL_RAND_HEX_32"
  base_url: false
  limiter: false
  image_proxy: false

search:
  safe_search: 0
  autocomplete: ""
  default_lang: "en"
  formats:
    - html
    - json

ui:
  static_use_hash: true

redis:
  url: false

plugins:
  searx.plugins.tracker_url_remover.SXNGPlugin:
    active: false

engines:
  - name: wikidata
    inactive: true

  - name: ahmia
    inactive: true

  - name: torch
    inactive: true

  - name: karmasearch
    inactive: true

  - name: karmasearch images
    inactive: true

  - name: karmasearch videos
    inactive: true

  - name: karmasearch news
    inactive: true

  - name: brave
    inactive: true

  - name: brave.images
    inactive: true

  - name: brave.videos
    inactive: true

  - name: brave.news
    inactive: true
SEARXCONF

sed -i "s/CHANGE_ME_WITH_OPENSSL_RAND_HEX_32/${SECRET_KEY}/" /etc/searxng/settings.yml
chown root:searxng /etc/searxng/settings.yml
chmod 640 /etc/searxng/settings.yml
```

This configuration enables `json`, disables the `tracker_url_remover` plugin that generated a runtime error, and disables noisy or unnecessary engines for this installation: `wikidata`, `ahmia`, `torch`, `karmasearch`, and `brave`.

#### STEP 14 - SEARXNG SYSTEMD SERVICE

Create `/etc/systemd/system/searxng.service`.

```bash
cat > /etc/systemd/system/searxng.service <<'SERVICE'
[Unit]
Description=SearXNG local metasearch engine
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=searxng
Group=searxng
WorkingDirectory=/opt/searxng/src
Environment=SEARXNG_SETTINGS_PATH=/etc/searxng/settings.yml
ExecStart=/opt/searxng/venv/bin/python /opt/searxng/src/searx/webapp.py
Restart=always
RestartSec=5
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=full
ProtectHome=true
ReadWritePaths=/opt/searxng /tmp

[Install]
WantedBy=multi-user.target
SERVICE

systemctl daemon-reload
systemctl enable --now searxng.service
sleep 5
systemctl status searxng.service --no-pager
```

Test SearXNG directly.

```bash
curl -s --max-time 25 "http://127.0.0.1:8888/search?q=OpenAI&format=json" | jq '.query, (.results[0] // null)'
```

#### STEP 15 - APACHE CONFIGURATION

The application is accessible under `/KUZAI/` if Apache serves `/var/www/html` as the default root.

Check Apache.

```bash
apache2ctl -S
apache2ctl configtest
systemctl reload apache2
```

If a dedicated configuration is required, create `/etc/apache2/conf-available/kuzai.conf`.

```bash
cat > /etc/apache2/conf-available/kuzai.conf <<'APACHECONF'
Alias /KUZAI /var/www/html/KUZAI/public

<Directory /var/www/html/KUZAI/public>
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>

<Directory /var/www/html/KUZAI/storage>
    Require all denied
</Directory>
APACHECONF

a2enconf kuzai
apache2ctl configtest
systemctl reload apache2
```

If the application is already accessible through `/KUZAI/`, this dedicated configuration is not mandatory.

#### STEP 16 - FINAL PERMISSIONS

Apply final permissions.

```bash
chown -R www-data:www-data /var/www/html/KUZAI
find /var/www/html/KUZAI -type d -exec chmod 755 {} \;
find /var/www/html/KUZAI -type f -exec chmod 644 {} \;
chmod 750 /var/www/html/KUZAI/storage
chmod 750 /var/www/html/KUZAI/storage/uploads
chmod 750 /var/www/html/KUZAI/storage/conversations
```

#### STEP 17 - PHP SYNTAX VALIDATION

Check all PHP files.

```bash
php -l /var/www/html/KUZAI/app/config.php
php -l /var/www/html/KUZAI/public/index.php
php -l /var/www/html/KUZAI/public/api/status.php
php -l /var/www/html/KUZAI/public/api/chat.php
php -l /var/www/html/KUZAI/public/api/upload.php
php -l /var/www/html/KUZAI/public/api/web-search.php
```

#### STEP 18 - APACHE, LLAMA.CPP, SEARXNG VALIDATION

```bash
apache2ctl configtest
systemctl is-active apache2
systemctl is-active llama-server-a.service
systemctl is-active searxng.service

curl -s http://127.0.0.1:8080/health
curl -s http://127.0.0.1:8080/v1/models | jq .
curl -s "http://127.0.0.1:8888/search?q=OpenAI&format=json" | jq '.query, (.results[0] // null)'
```

#### STEP 19 - KUZAI STATUS API TEST

```bash
curl -s http://127.0.0.1/KUZAI/api/status.php | jq .
```

Expected result.

```json
{
  "ok": true,
  "app": {
    "name": "KUZAI",
    "brand_line": "A KUZ NETWORK SOLUTION - Beta 01.2026",
    "version": "0.1.0"
  },
  "llm": {
    "api_base": "http://127.0.0.1:8080",
    "configured_model": "qwen3-8b-q5km",
    "active_model": "qwen3-8b-q5km",
    "health_http_code": 200,
    "models_http_code": 200
  },
  "error": null
}
```

#### STEP 20 - CHAT API TEST

```bash
curl -s http://127.0.0.1/KUZAI/api/chat.php \
  -H 'Content-Type: application/json' \
  -d '{"message":"Answer only: KUZAI_OK","history":[]}' | jq .
```

Expected result.

```json
{
  "ok": true,
  "model": "qwen3-8b-q5km",
  "message": {
    "role": "assistant",
    "content": "KUZAI_OK"
  }
}
```

#### STEP 21 - FILE UPLOAD TEST

Create a test file.

```bash
cat > /tmp/kuzai_upload_test.txt <<'UPLOADTEST'
This is a KUZAI upload test file.

Application:
KUZAI

Expected behavior:
The assistant must read this uploaded content and summarize it.
UPLOADTEST
```

Upload the file.

```bash
curl -s -X POST http://127.0.0.1/KUZAI/api/upload.php \
  -F "file=@/tmp/kuzai_upload_test.txt" | tee /tmp/kuzai_upload_response.json | jq .
```

Test the chat with the uploaded file.

```bash
FILE_ID="$(jq -r '.file.id' /tmp/kuzai_upload_response.json)"

curl -s http://127.0.0.1/KUZAI/api/chat.php \
  -H 'Content-Type: application/json' \
  -d "{
    \"message\": \"Summarize the uploaded file in one short paragraph.\",
    \"history\": [],
    \"attachments\": [
      {
        \"id\": \"${FILE_ID}\"
      }
    ]
  }" | jq .
```

#### STEP 22 - WEB SEARCH API TEST

```bash
curl -s http://127.0.0.1/KUZAI/api/web-search.php \
  -H 'Content-Type: application/json' \
  -d '{"query":"OpenAI","limit":3}' | jq .
```

Expected result:

```json
{
  "ok": true,
  "query": "OpenAI",
  "count": 3,
  "results": [
    {
      "title": "OpenAI | OpenAI",
      "url": "https://openai.com/",
      "content": "...",
      "engine": "google"
    }
  ]
}
```

#### STEP 23 - CHAT TEST WITH INJECTED WEB RESULTS

```bash
SEARCH_JSON="$(curl -s http://127.0.0.1/KUZAI/api/web-search.php \
  -H 'Content-Type: application/json' \
  -d '{"query":"who is OpenAI","limit":4}')"

WEB_RESULTS="$(echo "$SEARCH_JSON" | jq -c '.results')"

curl -s http://127.0.0.1/KUZAI/api/chat.php \
  -H 'Content-Type: application/json' \
  -d "{
    \"message\": \"Who is OpenAI? Answer briefly and cite source URLs.\",
    \"history\": [],
    \"web_results\": ${WEB_RESULTS}
  }" | jq .
```

Expected result:

```text
ok: true
web_results_used: 4
message.content contains a Sources section with URLs
```

#### STEP 24 - BROWSER UI TEST

Open:

```text
http://<SERVER_IP>/KUZAI/?v=21
```

Manual tests:

```text
1. Send a simple message.
2. Check the model response.
3. Test STOP during a long generation.
4. Upload a .txt or .log file.
5. Ask for a file analysis.
6. Write a web query.
7. Click WEB.
8. Check that the sources appear below the input field.
9. Click SEND.
10. Check that the response contains a Sources section.
11. Click CLEAR.
12. Check that the local history is cleared.
```

#### STEP 25 - SEARXNG LOG CLEANUP

Check that there are no recent errors.

```bash
journalctl -u searxng.service --since "5 minutes ago" --no-pager | grep -Ei "ERROR|wikidata|tracker|ahmia|torch|karmasearch|brave|X-Forwarded|X-Real-IP" || true
```

The provided configuration disables the engines that generated errors or rate limits.

#### STEP 26 - `.BK` BACKUPS OF CRITICAL FILES

Create timestamped backups.

```bash
TS="$(date +%Y%m%d-%H%M%S)"

FILES="
/var/www/html/KUZAI/app/config.php
/var/www/html/KUZAI/public/index.php
/var/www/html/KUZAI/public/api/chat.php
/var/www/html/KUZAI/public/api/upload.php
/var/www/html/KUZAI/public/api/web-search.php
/var/www/html/KUZAI/public/api/status.php
/var/www/html/KUZAI/public/assets/js/app.js
/var/www/html/KUZAI/public/assets/css/style.css
/etc/searxng/settings.yml
/etc/systemd/system/searxng.service
/etc/systemd/system/llama-server-a.service
/etc/apache2/conf-available/kuzai.conf
/etc/apache2/conf-enabled/kuzai.conf
"

for f in $FILES; do
    if [ -f "$f" ]; then
        cp -a "$f" "$f.bk-$TS"
        echo "BK_OK $f.bk-$TS"
    else
        echo "SKIP_NOT_FOUND $f"
    fi
done

echo "BACKUP_TIMESTAMP=$TS"
```

Check the backups.

```bash
TS="$(ls -1 /var/www/html/KUZAI/public/index.php.bk-* 2>/dev/null | sed 's/.*\.bk-//' | sort | tail -n 1)"

echo "===== LAST BACKUP TIMESTAMP ====="
echo "$TS"

echo
find /var/www/html/KUZAI -type f -name "*.bk-$TS" -ls | sort

echo
find /etc/searxng /etc/systemd/system /etc/apache2 -maxdepth 3 -type f -name "*.bk-$TS" -ls 2>/dev/null | sort
```

#### STEP 27 - COMPLETE FINAL VALIDATION

```bash
php -l /var/www/html/KUZAI/public/index.php
php -l /var/www/html/KUZAI/public/api/chat.php
php -l /var/www/html/KUZAI/public/api/upload.php
php -l /var/www/html/KUZAI/public/api/web-search.php
php -l /var/www/html/KUZAI/public/api/status.php
apache2ctl configtest
systemctl is-active searxng.service
systemctl is-active llama-server-a.service
curl -s http://127.0.0.1/KUZAI/api/status.php | jq .
curl -s http://127.0.0.1/KUZAI/api/web-search.php \
  -H 'Content-Type: application/json' \
  -d '{"query":"OpenAI","limit":2}' | jq .
```

Expected result:

```text
No syntax errors detected
Syntax OK
active
active
status.php ok true
web-search.php ok true
```

#### FINAL RESULT

The KUZAI installation provides a local AI application usable through a browser, with:

```text
Standalone PHP web interface
Local connection to llama.cpp
qwen3-8b-q5km model
File upload and analysis
Local web search with SearXNG
Web source injection into responses
Source URLs added automatically
Browser-side STOP control for generation
Dynamic response container
Services supervised by systemd
Complete validation through system and API commands
```

#### POSSIBLE IMPROVEMENTS

```text
PDF support with text extraction
Persistent server-side history
Local memory per conversation
Document indexing
Local RAG
Multi-profile system management
GPU supervision in the interface
HTTP authentication
Application logging
Markdown export of conversations
```
