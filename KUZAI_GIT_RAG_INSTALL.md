# KUZAI — INSTALLATION OF THE LOCAL GIT-RAG SYSTEM

Generation date --> 2026-06-14  
Project --> KUZAI Local AI — Beta-0.01.2026  
Module --> local GIT-RAG for Git repositories cloned on the machine.

This document is a reinstallation base. It describes the validated system, the steps, paths, services, endpoints, test commands, and the code blocks created or added on the KUZAI side. For a strictly byte-identical restoration, also use the section **Exact export of the installed code**.

KUZAI rule --> always create a backup before modifying an existing file.

---

## 1. FUNCTIONAL SYNOPTIC

```text
USER
  |
  | Clicks GIT-RAG in KUZAI
  v
KUZAI WEB INTERFACE
  |-- local Git repository selection
  |-- repository file list
  |-- file reading
  |-- file editing
  |-- SAVE with server-side backup
  |-- STATUS / DIFF / COMMIT / PUSH / PULL
  |-- REINDEX
  |
  v
KUZAI PHP ENDPOINTS
  |-- api/git-rag-repos.php
  |-- api/git-rag-files.php
  |-- api/git-rag-file-read.php
  |-- api/git-rag-file-write.php
  |-- api/git-rag-query.php
  |-- api/git-rag-index.php
  |-- api/git-rag-git-status.php
  |-- api/git-rag-git-diff.php
  |-- api/git-rag-git-commit.php
  |-- api/git-rag-git-push.php
  |-- api/git-rag-git-pull.php
  |
  v
LOCAL PYTHON SERVICE
  |-- kuzai-git-rag.service
  |-- 127.0.0.1:8890
  |-- user kuzrag
  |-- reads /srv/kuzai-git-rag/repos
  |-- writes /var/lib/kuzai-git-rag/indexes
  |-- executes Git server-side
  |
  +----------------------------+
  |                            |
  v                            v
LOCAL LLAMA.CPP                LOCAL GIT REPOSITORY
127.0.0.1:8080                 /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2
/v1/embeddings                 branch main
qwen3-8b-q5km                  GitHub SSH
  |                            |
  v                            v
LOCAL VECTOR INDEX             GITHUB
chunks.jsonl                   git@github.com:Kusanagi8200/KUZAI.ORG-VS2.git
manifest.json
```

RAG flow -->

```text
User question
  -> app.js adds git_rag_repo_id
  -> chat.php receives the active repo
  -> chat.php calls the local GIT-RAG backend
  -> the service vectorizes the question through llama.cpp /v1/embeddings
  -> comparison with chunks.jsonl
  -> relevant chunks are injected into the prompt
  -> the LLM response is enriched by the repository context
```

Edit/Git flow -->

```text
open file
  -> EDIT
  -> modify text
  -> SAVE
  -> backup in /var/lib/kuzai-git-rag/file-backups
  -> STATUS
  -> DIFF
  -> COMMIT
  -> PUSH
  -> REINDEX
  -> final STATUS clean
```

---

## 2. VALIDATED STATE

```text
Repo ID              KUZAI.ORG-VS2
Repo path            /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2
Remote               git@github.com:Kusanagi8200/KUZAI.ORG-VS2.git
Branch               main
Git user.name         Kusanagi8200
Git user.email        admin@kuzai.org
Service              kuzai-git-rag.service
Service port          127.0.0.1:8890
LLM server            127.0.0.1:8080
Model alias           qwen3-8b-q5km
Embeddings endpoint   /v1/embeddings
Embedding dimension   4096
Validated index       60 chunks / skipped 0 / about 16-17s
Latest UI commit      c4d820aa48788a41ef983f346678ac3f80d30026
```

Validated functions -->

```text
Repository selection         OK
File list                    OK
File reading                 OK
File editing                 OK
SAVE with backup             OK
STATUS                       OK
DIFF                         OK
COMMIT                       OK
PUSH                         OK
PULL --ff-only               OK
REINDEX                      OK
RAG injection in chat.php     OK
GIT-RAG UI overlay            OK
```

---

## 3. INSTALLED DIRECTORY TREE

```text
/opt/kuzai-git-rag/
└── app/server.py

/var/lib/kuzai-git-rag/
├── indexes/KUZAI.ORG-VS2/
│   ├── chunks.jsonl
│   └── manifest.json
└── file-backups/KUZAI.ORG-VS2/

/srv/kuzai-git-rag/repos/
└── KUZAI.ORG-VS2/

/var/www/html/KUZAI/app/
└── git-rag.repos.php

/var/www/html/KUZAI/public/api/
├── git-rag-status.php
├── git-rag-repos.php
├── git-rag-files.php
├── git-rag-query.php
├── git-rag-index.php
├── git-rag-file-read.php
├── git-rag-file-write.php
├── git-rag-git-status.php
├── git-rag-git-diff.php
├── git-rag-git-commit.php
├── git-rag-git-push.php
└── git-rag-git-pull.php

/var/www/html/KUZAI/public/api/chat.php
/var/www/html/KUZAI/public/assets/js/app.js
/var/www/html/KUZAI/public/assets/css/style.css
/var/www/html/KUZAI/public/index.php

/etc/systemd/system/kuzai-git-rag.service
/etc/systemd/system/llama-server-a.service.d/override.conf
```

---

## 4. STEP 1 — PREPARE USER, GROUPS, AND DIRECTORIES

Brief --> the GIT-RAG service runs with the `kuzrag` system user. Apache/PHP runs with `www-data`. Permissions must allow UI reading and controlled writes.

```bash
sudo useradd --system --home /opt/kuzai-git-rag --shell /usr/sbin/nologin kuzrag || true
sudo usermod -aG www-data kuzrag

sudo mkdir -p /opt/kuzai-git-rag/app
sudo mkdir -p /var/lib/kuzai-git-rag/indexes
sudo mkdir -p /var/lib/kuzai-git-rag/file-backups
sudo mkdir -p /srv/kuzai-git-rag/repos

sudo chown -R kuzrag:www-data /opt/kuzai-git-rag
sudo chown -R kuzrag:www-data /var/lib/kuzai-git-rag
sudo chown -R kuzrag:www-data /srv/kuzai-git-rag

sudo chmod -R 750 /opt/kuzai-git-rag
sudo chmod -R 770 /var/lib/kuzai-git-rag
sudo chmod -R 770 /srv/kuzai-git-rag
```

Checks -->

```bash
id kuzrag
ls -ld /opt/kuzai-git-rag /var/lib/kuzai-git-rag /srv/kuzai-git-rag/repos
```

---

## 5. STEP 2 — CLONE THE LOCAL GIT REPOSITORY

Brief --> the repository is manipulated locally. GitHub is only used for `pull` and `push` through SSH.

```bash
sudo -u kuzrag git clone git@github.com:Kusanagi8200/KUZAI.ORG-VS2.git /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2

cd /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2
sudo -u kuzrag git checkout main
sudo -u kuzrag git config user.name "Kusanagi8200"
sudo -u kuzrag git config user.email "admin@kuzai.org"
```

Validated permissions -->

```bash
sudo chown -R kuzrag:www-data /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2
sudo find /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2 -type d -exec chmod 770 {} \;
sudo find /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2 -type f -exec chmod 660 {} \;
```

Important validated correction --> `www-data` must be able to read `.git/config`; otherwise `git-rag-repos.php` returns `remote_ok=false` and the repository appears as `NOT READY`.

```bash
cd /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2
cp -a .git/config ".git/config.bak-before-www-data-read-$(date +%Y%m%d-%H%M%S)"
sudo chown kuzrag:www-data .git/config
sudo chmod 660 .git/config
```

Test -->

```bash
sudo -u www-data bash -lc 'test -r /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2/.git/config && echo readable || echo not-readable'
sudo -u kuzrag git -C /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2 remote get-url origin
sudo -u kuzrag git -C /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2 branch --show-current
sudo -u kuzrag git -C /srv/kuzai-git-rag/repos/KUZAI.ORG-VS2 rev-parse HEAD
```

---

## 6. STEP 3 — PHP REPOSITORY WHITELIST

Created file --> `/var/www/html/KUZAI/app/git-rag.repos.php`

```php
<?php
declare(strict_types=1);
return [
    'KUZAI.ORG-VS2' => [
        'name' => 'KUZAI.ORG-VS2',
        'path' => '/srv/kuzai-git-rag/repos/KUZAI.ORG-VS2',
        'remote' => 'git@github.com:Kusanagi8200/KUZAI.ORG-VS2.git',
        'active_branch' => 'main',
        'enabled' => true,
    ],
];
```

Installation with backup -->

```bash
sudo mkdir -p /var/www/html/KUZAI/app
if [ -f /var/www/html/KUZAI/app/git-rag.repos.php ]; then
  sudo cp -a /var/www/html/KUZAI/app/git-rag.repos.php "/var/www/html/KUZAI/app/git-rag.repos.php.bak-$(date +%Y%m%d-%H%M%S)"
fi
sudo nano /var/www/html/KUZAI/app/git-rag.repos.php
sudo chown www-data:www-data /var/www/html/KUZAI/app/git-rag.repos.php
sudo chmod 640 /var/www/html/KUZAI/app/git-rag.repos.php
php -l /var/www/html/KUZAI/app/git-rag.repos.php
```

---

## 7. STEP 4 — ENABLE LLAMA.CPP EMBEDDINGS

Brief --> the local RAG uses the llama.cpp `/v1/embeddings` endpoint. The options `--embeddings --pooling mean` are mandatory.

File --> `/etc/systemd/system/llama-server-a.service.d/override.conf`

```ini
[Service]
ExecStart=
ExecStart=/opt/src/llama.cpp/build/bin/llama-server -m /opt/llm/models/Qwen3-8B-Q5_K_M.gguf --alias qwen3-8b-q5km --host 0.0.0.0 --port 8080 -c 8192 --embeddings --pooling mean
```

Commands -->

```bash
sudo mkdir -p /etc/systemd/system/llama-server-a.service.d
sudo cp -a /etc/systemd/system/llama-server-a.service.d/override.conf "/etc/systemd/system/llama-server-a.service.d/override.conf.bak-$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true
sudo nano /etc/systemd/system/llama-server-a.service.d/override.conf
sudo systemctl daemon-reload
sudo systemctl restart llama-server-a.service
```

Test -->

```bash
curl -sS http://127.0.0.1:8080/v1/embeddings   -H 'Content-Type: application/json'   -d '{"model":"qwen3-8b-q5km","input":"KUZAI embedding test"}' | jq '.data[0].embedding | length'
```

Expected result -->

```text
4096
```

---

## 8. STEP 5 — GIT-RAG PYTHON SERVICE

Created file --> `/opt/kuzai-git-rag/app/server.py`

Service functions -->

```text
GET  /health
GET  /repos
GET  /files?repo=...
POST /index
POST /query
GET  /git-status?repo=...
GET  /git-diff?repo=...
POST /git-commit
POST /git-push
POST /git-pull
```

Validated parameters -->

```text
HOST = 127.0.0.1
PORT = 8890
CHUNK_CHARS = 1200
CHUNK_OVERLAP = 120
INDEX_ROOT = /var/lib/kuzai-git-rag/indexes
LLAMA_EMBEDDINGS_URL = http://127.0.0.1:8080/v1/embeddings
LLAMA_MODEL = qwen3-8b-q5km
```

Reproducible service pseudo-code -->

```python
# /opt/kuzai-git-rag/app/server.py
# Role: local HTTP service for indexing, RAG query, and Git actions.

# 1. Load the repository whitelist.
# 2. Check repo enabled + path + .git + branch.
# 3. List text files in the repository.
# 4. Index: read files, split into chunks, call llama.cpp /v1/embeddings.
# 5. Write chunks.jsonl + manifest.json.
# 6. Query: vectorize question, cosine similarity, return top_k chunks.
# 7. Git status/diff: git -C repo status/diff.
# 8. Git commit: block empty commits, git add --all, git commit -m.
# 9. Git push: git push origin main.
# 10. Git pull: block if dirty, git pull --ff-only origin main.
```

Note --> to reinstall the exact validated version, recover the real file through the exact export in section 19.

Permissions -->

```bash
sudo chown kuzrag:www-data /opt/kuzai-git-rag/app/server.py
sudo chmod 750 /opt/kuzai-git-rag/app/server.py
```

---

## 9. STEP 6 — SYSTEMD SERVICE

Created file --> `/etc/systemd/system/kuzai-git-rag.service`

```ini
[Unit]
Description=KUZAI GIT-RAG local service
After=network.target llama-server-a.service

[Service]
Type=simple
User=kuzrag
Group=www-data
WorkingDirectory=/opt/kuzai-git-rag/app
ExecStart=/usr/bin/python3 /opt/kuzai-git-rag/app/server.py
Restart=on-failure
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Commands -->

```bash
sudo cp -a /etc/systemd/system/kuzai-git-rag.service "/etc/systemd/system/kuzai-git-rag.service.bak-$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true
sudo nano /etc/systemd/system/kuzai-git-rag.service
sudo systemctl daemon-reload
sudo systemctl enable --now kuzai-git-rag.service
```

Tests -->

```bash
systemctl status kuzai-git-rag.service --no-pager
curl -sS http://127.0.0.1:8890/health | jq .
curl -sS http://127.0.0.1:8890/repos | jq .
```

---

## 10. STEP 7 — CREATED PHP ENDPOINTS

Brief --> PHP endpoints are bridges between the KUZAI interface and the local Python service. They must remain local and return JSON.

### ENDPOINT LIST

```text
/var/www/html/KUZAI/public/api/git-rag-status.php
/var/www/html/KUZAI/public/api/git-rag-repos.php
/var/www/html/KUZAI/public/api/git-rag-files.php
/var/www/html/KUZAI/public/api/git-rag-query.php
/var/www/html/KUZAI/public/api/git-rag-index.php
/var/www/html/KUZAI/public/api/git-rag-file-read.php
/var/www/html/KUZAI/public/api/git-rag-file-write.php
/var/www/html/KUZAI/public/api/git-rag-git-status.php
/var/www/html/KUZAI/public/api/git-rag-git-diff.php
/var/www/html/KUZAI/public/api/git-rag-git-commit.php
/var/www/html/KUZAI/public/api/git-rag-git-push.php
/var/www/html/KUZAI/public/api/git-rag-git-pull.php
```

### VALIDATED PAYLOADS

```text
git-rag-file-read.php       POST { repo, path }
git-rag-file-write.php      POST { repo, path, content }
git-rag-index.php           POST { repo }
git-rag-query.php           POST { repo, query, top_k }
git-rag-git-status.php      POST { repo }
git-rag-git-diff.php        POST { repo }
git-rag-git-commit.php      POST { repo, message }
git-rag-git-push.php        POST { repo }
git-rag-git-pull.php        POST { repo }
```

### VALIDATED PHP SECURITY CONTROLS

```text
normalizeRepoId
normalizeRepoFilePath
path traversal blocked
.git forbidden for UI read/write
binary file blocked
content > 512000 bytes blocked
backup before replacing an existing file
non-whitelisted repo blocked
POST required for sensitive actions
```

### COMMON BASE CODE USED IN ENDPOINTS

```php
<?php
declare(strict_types=1);

function jsonOut(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function readJsonInput(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($data) ? $data : [];
}

function normalizeRepoId(string $repoId): string
{
    $repoId = trim($repoId);
    if ($repoId === '' || !preg_match('/^[A-Za-z0-9._-]{1,120}$/', $repoId)) {
        return '';
    }
    return $repoId;
}

function normalizeRepoFilePath(string $filePath): string
{
    $filePath = trim(str_replace('\\', '/', $filePath));
    if ($filePath === '' || str_starts_with($filePath, '/') || str_contains($filePath, "\0")) {
        return '';
    }
    foreach (explode('/', $filePath) as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            return '';
        }
    }
    if ($filePath === '.git' || str_starts_with($filePath, '.git/')) {
        return '';
    }
    return $filePath;
}
```

### TYPICAL LOCAL SERVICE BRIDGE

```php
function callGitRagService(string $method, string $path, ?array $payload = null): array
{
    $url = 'http://127.0.0.1:8890' . $path;
    $headers = "Accept: application/json\r\n";
    $content = null;

    if ($payload !== null) {
        $headers .= "Content-Type: application/json\r\n";
        $content = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => $headers,
            'content' => $content,
            'timeout' => 240,
            'ignore_errors' => true,
        ],
    ]);

    $body = file_get_contents($url, false, $context);
    if ($body === false) {
        return ['ok' => false, 'error' => 'Unable to reach GIT-RAG service'];
    }

    $data = json_decode($body, true);
    return is_array($data) ? $data : ['ok' => false, 'error' => 'Invalid JSON from GIT-RAG service'];
}
```

### GIT STATUS ENDPOINT EXAMPLE

```php
<?php
declare(strict_types=1);
require __DIR__ . '/git-rag-common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['ok' => false, 'error' => 'POST required'], 405);
}

$data = readJsonInput();
$repoId = normalizeRepoId((string) ($data['repo'] ?? ''));

if ($repoId === '') {
    jsonOut(['ok' => false, 'error' => 'Missing or invalid repository id'], 400);
}

$result = callGitRagService('GET', '/git-status?repo=' . rawurlencode($repoId));
jsonOut($result, ($result['ok'] ?? false) ? 200 : 500);
```

### FILE-WRITE ENDPOINT EXAMPLE

```php
<?php
declare(strict_types=1);
require __DIR__ . '/git-rag-common.php';

$data = readJsonInput();
$repoId = normalizeRepoId((string) ($data['repo'] ?? ''));
$filePath = normalizeRepoFilePath((string) ($data['path'] ?? ''));
$content = (string) ($data['content'] ?? '');

if ($repoId === '' || $filePath === '') {
    jsonOut(['ok' => false, 'error' => 'Missing or invalid file path'], 400);
}

if (strlen($content) > 512000 || str_contains($content, "\0")) {
    jsonOut(['ok' => false, 'error' => 'Binary or oversized content is not allowed'], 400);
}

$repos = require __DIR__ . '/../../app/git-rag.repos.php';
if (!isset($repos[$repoId])) {
    jsonOut(['ok' => false, 'error' => 'Unknown repository'], 404);
}

$repoPath = rtrim((string) $repos[$repoId]['path'], '/');
$absolutePath = $repoPath . '/' . $filePath;
$backupDir = '/var/lib/kuzai-git-rag/file-backups/' . $repoId . '/' . date('Ymd-His');

if (!is_dir($backupDir) && !mkdir($backupDir, 0770, true)) {
    jsonOut(['ok' => false, 'error' => 'Unable to create backup directory'], 500);
}

$backupPath = null;
if (file_exists($absolutePath)) {
    $backupPath = $backupDir . '/' . str_replace('/', '__', $filePath);
    if (!copy($absolutePath, $backupPath)) {
        jsonOut(['ok' => false, 'error' => 'Unable to backup existing file'], 500);
    }
}

$tmpPath = $absolutePath . '.tmp-' . bin2hex(random_bytes(6));
file_put_contents($tmpPath, $content, LOCK_EX);
chmod($tmpPath, 0660);
rename($tmpPath, $absolutePath);

jsonOut([
    'ok' => true,
    'repo' => $repoId,
    'path' => $filePath,
    'size_bytes' => strlen($content),
    'backup_path' => $backupPath,
]);
```

Syntax check -->

```bash
find /var/www/html/KUZAI/public/api -maxdepth 1 -name 'git-rag-*.php' -print -exec php -l {} \;
```

---

## 11. STEP 8 — `CHAT.PHP` INTEGRATION

Brief --> `chat.php` receives `git_rag_repo_id`, retrieves relevant chunks, then adds a context block to the prompt sent to the LLM.

Validated headers -->

```text
X-KUZAI-Git-Rag-Repo-Requested
X-KUZAI-Git-Rag-Repo-Ready
X-KUZAI-Git-Rag-Injected
X-KUZAI-Git-Rag-Chunks
X-KUZAI-Git-Rag-Mode
```

Injection pseudo-code -->

```php
$gitRagRepoId = isset($input['git_rag_repo_id']) ? trim((string) $input['git_rag_repo_id']) : '';

if ($gitRagRepoId !== '') {
    $rag = gitRagCallLocalQueryService($gitRagRepoId, $userText, 6);

    if (($rag['ok'] ?? false) === true && isset($rag['chunks']) && is_array($rag['chunks'])) {
        $contextBlock = gitRagBuildContextBlock($rag['chunks']);
        $messages[] = [
            'role' => 'system',
            'content' => "Use the following local repository context when relevant.\n" . $contextBlock,
        ];
    }
}
```

Context block -->

```text
[KUZAI_GIT_RAG_CONTEXT_BEGIN]
SOURCE 1 / public/index.php / score 0.7421
...
---
SOURCE 2 / app/pages.php / score 0.7312
...
[KUZAI_GIT_RAG_CONTEXT_END]
```

Validated test --> LLM response using files `app/pages.php`, `public/index.php`, `README.md`.

---

## 12. STEP 9 — `APP.JS` INTEGRATION

Brief --> adds the GIT-RAG UI, repository selection, file menu, viewer, editor, and Git actions.

Validated blocks -->

```js
// KUZAI_GIT_RAG_GIT_UI_STEP1
async function gitRagPostJson(endpoint, payload) {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok || !data || data.ok !== true) {
        throw new Error(data && data.error ? data.error : 'GIT-RAG Git action failed');
    }

    return data;
}
```

```js
async function runGitRepoAction(action) {
    const activeRepo = getActiveRepo();
    if (!activeRepo) { return; }

    let endpoint = '';
    let payload = { repo: activeRepo.id };
    let label = String(action || '').toUpperCase();

    if (action === 'status') {
        endpoint = 'api/git-rag-git-status.php';
    } else if (action === 'diff') {
        endpoint = 'api/git-rag-git-diff.php';
    } else if (action === 'pull') {
        endpoint = 'api/git-rag-git-pull.php';
        if (!window.confirm('Run git pull --ff-only on active repository?')) { return; }
    } else if (action === 'push') {
        endpoint = 'api/git-rag-git-push.php';
        if (!window.confirm('Run git push on active repository?')) { return; }
    } else if (action === 'commit') {
        endpoint = 'api/git-rag-git-commit.php';
        const message = window.prompt('Commit message:');
        if (!message || !message.trim()) { return; }
        payload = { repo: activeRepo.id, message: message.trim() };
    } else {
        return;
    }

    gitRagSetGitButtonsDisabled(true);
    gitRagShowGitResult(`${label} running...`, '', false);

    try {
        const data = await gitRagPostJson(endpoint, payload);
        gitRagShowGitResult(`${label} OK`, data, false);
        if (action === 'commit' || action === 'push' || action === 'pull') {
            await fetchRepos();
        }
    } catch (error) {
        gitRagShowGitResult(`${label} ERROR: ${error.message}`, String(error.message), true);
    } finally {
        gitRagSetGitButtonsDisabled(false);
    }
}
```

Validated file editing -->

```js
// KUZAI_GIT_RAG_FILE_EDIT_UI_STEP1
async function saveRepoFile(filePath, content) {
    const activeRepo = getActiveRepo();
    if (!activeRepo || !filePath) { return null; }

    return gitRagPostJson('api/git-rag-file-write.php', {
        repo: activeRepo.id,
        path: filePath,
        content: content,
    });
}

function setRepoFileEditMode(enabled) {
    const viewer = document.getElementById('gitRagFileViewer');
    const editor = document.getElementById('gitRagFileEditor');
    const editBtn = document.getElementById('gitRagFileEditBtn');
    const saveBtn = document.getElementById('gitRagFileSaveBtn');
    const cancelBtn = document.getElementById('gitRagFileCancelBtn');

    if (viewer) { viewer.hidden = enabled; }
    if (editor) { editor.hidden = !enabled; if (enabled) { editor.focus(); } }
    if (editBtn) { editBtn.hidden = enabled; }
    if (saveBtn) { saveBtn.hidden = !enabled; }
    if (cancelBtn) { cancelBtn.hidden = !enabled; }
}
```

File buttons -->

```html
<button data-git-rag-action="status">STATUS</button>
<button data-git-rag-action="diff">DIFF</button>
<button data-git-rag-action="pull">PULL</button>
<button data-git-rag-action="commit">COMMIT</button>
<button data-git-rag-action="push">PUSH</button>
<button id="gitRagReindexBtn">REINDEX</button>
```

Exposed global object -->

```js
window.KUZAI_GIT_RAG = {
    getActiveRepoId: function () { return activeRepoId || ''; },
    getActiveRepoName: function () {
        const repo = getActiveRepo();
        return repo ? (repo.name || repo.id || '') : '';
    },
};
```

---

## 13. STEP 10 — FINAL UI CSS

Brief --> GIT-RAG window as an overlay, width aligned with the top banner, top placed over the panels, defined bottom, internal scrolling.

CSS blocks to keep at the end of `style.css` -->

```css
/* KUZAI_GIT_RAG_FILE_VIEW_BEGIN */
.git-rag-file-viewer {
    width: 100%;
    max-height: min(58vh, 620px);
    overflow: auto;
    margin: 14px 0 0;
    padding: 16px;
    border: 1px solid var(--border-soft);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.03);
    color: var(--text-main);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 0.76rem;
    font-weight: 600;
    line-height: 1.55;
    white-space: pre;
    tab-size: 4;
}
/* KUZAI_GIT_RAG_FILE_VIEW_END */

/* KUZAI_GIT_RAG_FILE_EDIT_BEGIN */
.git-rag-file-editor {
    width: 100%;
    min-height: min(58vh, 620px);
    max-height: min(58vh, 620px);
    overflow: auto;
    resize: vertical;
    margin: 14px 0 0;
    padding: 16px;
    border: 1px solid var(--border-strong);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-main);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: 0.76rem;
    font-weight: 600;
    line-height: 1.55;
    white-space: pre;
    tab-size: 4;
    outline: none;
}

.git-rag-git-output--error {
    border-color: var(--danger, #ff6b6b);
}
/* KUZAI_GIT_RAG_FILE_EDIT_END */
```

Final overlay -->

```css
/* KUZAI_GIT_RAG_BANNER_ALIGN2_BEGIN */
.git-rag-menu {
    position: fixed !important;
    top: 148px !important;
    left: 48px !important;
    right: 48px !important;
    bottom: 24px !important;
    transform: none !important;
    width: auto !important;
    max-width: none !important;
    height: auto !important;
    max-height: none !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}

.git-rag-files-list {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: auto !important;
}

.git-rag-file-viewer,
.git-rag-file-editor {
    flex: 1 1 auto !important;
    min-height: 0 !important;
    max-height: none !important;
    overflow: auto !important;
}

@media (max-width: 900px) {
    .git-rag-menu {
        top: 126px !important;
        left: 14px !important;
        right: 14px !important;
        bottom: 14px !important;
        width: auto !important;
        max-width: none !important;
    }
}
/* KUZAI_GIT_RAG_BANNER_ALIGN2_END */
```

Validated top adjustment added later -->

```css
/* KUZAI_GIT_RAG_TOP_ADJUST_BEGIN */
.git-rag-menu {
    top: 136px !important;
}

@media (max-width: 900px) {
    .git-rag-menu {
        top: 118px !important;
    }
}
/* KUZAI_GIT_RAG_TOP_ADJUST_END */
```

---

## 14. STEP 11 — `INDEX.PHP` CACHE BUSTING

Brief --> each CSS/JS patch must change the query string to avoid browser cache problems.

Validated examples -->

```html
<link rel="stylesheet" href="assets/css/style.css?v=clean-4-bold-all-git-rag-banner-align2">
<script src="assets/js/app.js?v=custom-llm-runtime-1-git-rag-file-edit-ui-1"></script>
```

After modification -->

```text
Ctrl + Shift + R
```

---

## 15. REPRODUCIBLE BACKEND TESTS

Service -->

```bash
curl -sS http://127.0.0.1:8890/health | jq .
curl -sS http://127.0.0.1:8890/repos | jq .
```

Repos through PHP -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-repos.php | jq .
```

Expected result -->

```json
{
  "ok": true,
  "repos": [
    {
      "id": "KUZAI.ORG-VS2",
      "ready": true,
      "remote_ok": true,
      "branch_ok": true,
      "git_repo_ok": true
    }
  ]
}
```

Index -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-index.php   -H 'Content-Type: application/json'   -d '{"repo":"KUZAI.ORG-VS2"}' | jq .
```

Query -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-query.php   -H 'Content-Type: application/json'   -d '{"repo":"KUZAI.ORG-VS2","query":"Which files structure the site?","top_k":6}' | jq .
```

File read -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-file-read.php   -H 'Content-Type: application/json'   -d '{"repo":"KUZAI.ORG-VS2","path":"README.md"}' | jq .
```

File write test -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-file-write.php   -H 'Content-Type: application/json'   -d '{"repo":"KUZAI.ORG-VS2","path":"tmp-kuzai-write-test.txt","content":"KUZAI GIT-RAG write test modified\n"}' | jq .
```

Git actions -->

```bash
curl -sS http://127.0.0.1/KUZAI/api/git-rag-git-status.php -H 'Content-Type: application/json' -d '{"repo":"KUZAI.ORG-VS2"}' | jq .
curl -sS http://127.0.0.1/KUZAI/api/git-rag-git-diff.php   -H 'Content-Type: application/json' -d '{"repo":"KUZAI.ORG-VS2"}' | jq .
curl -sS http://127.0.0.1/KUZAI/api/git-rag-git-commit.php -H 'Content-Type: application/json' -d '{"repo":"KUZAI.ORG-VS2","message":"Test KUZAI GIT-RAG"}' | jq .
curl -sS http://127.0.0.1/KUZAI/api/git-rag-git-push.php   -H 'Content-Type: application/json' -d '{"repo":"KUZAI.ORG-VS2"}' | jq .
curl -sS http://127.0.0.1/KUZAI/api/git-rag-git-pull.php   -H 'Content-Type: application/json' -d '{"repo":"KUZAI.ORG-VS2"}' | jq .
```

---

## 16. VALIDATED UI TESTS

```text
1. Ctrl + Shift + R
2. Click GIT-RAG
3. Select KUZAI.ORG-VS2
4. Click the active repository
5. Check the file list
6. Open tmp-kuzai-write-test.txt
7. Click EDIT
8. Modify the file
9. Click SAVE
10. Check displayed backup
11. Return to FILES
12. STATUS: modified file visible
13. DIFF: change visible
14. COMMIT: commit created
15. PUSH: GitHub push OK
16. REINDEX: index updated
17. Final STATUS: clean
```

Expected final result -->

```json
{
  "ok": true,
  "repo": "KUZAI.ORG-VS2",
  "branch": "main",
  "working_tree_clean": true,
  "status_short": [],
  "status_branch": [
    "## main...origin/main"
  ]
}
```

---

## 17. APPLIED SECURITY CONTROLS

```text
Whitelisted repositories only
Path traversal blocked
.git access blocked for UI file read/write
Binary files blocked
File size limited to 512000 bytes
Server backup before replacing an existing file
Empty commit blocked
Empty commit message blocked
Pull blocked if working tree is not clean
Pull runs with --ff-only
Git executed through the kuzrag service
Local llama.cpp embeddings
No external API for embeddings
```

Important operational point --> do not validate the GitHub SSH key from a root shell. The GitHub connection must be known by the `kuzrag` user.

---

## 18. STANDARD BACKUPS

Before PHP patch -->

```bash
stamp="$(date +%Y%m%d-%H%M%S)"
sudo cp -a /var/www/html/KUZAI/public/api/chat.php "/var/www/html/KUZAI/public/api/chat.php.bak-$stamp"
```

Before UI patch -->

```bash
stamp="$(date +%Y%m%d-%H%M%S)" && cp -a /var/www/html/KUZAI/public/assets/js/app.js "/var/www/html/KUZAI/public/assets/js/app.js.bak-$stamp" && cp -a /var/www/html/KUZAI/public/assets/css/style.css "/var/www/html/KUZAI/public/assets/css/style.css.bak-$stamp" && cp -a /var/www/html/KUZAI/public/index.php "/var/www/html/KUZAI/public/index.php.bak-$stamp"
```

Before service patch -->

```bash
stamp="$(date +%Y%m%d-%H%M%S)"
sudo cp -a /opt/kuzai-git-rag/app/server.py "/opt/kuzai-git-rag/app/server.py.bak-$stamp"
sudo cp -a /etc/systemd/system/kuzai-git-rag.service "/etc/systemd/system/kuzai-git-rag.service.bak-$stamp"
```

---

## 19. EXACT EXPORT OF THE INSTALLED CODE

This command creates a complete archive of the files actually installed. This is the reference to use for a bit-by-bit reinstallation.

```bash
sudo tar -czf "/root/kuzai-git-rag-code-export-$(date +%Y%m%d-%H%M%S).tar.gz"   /opt/kuzai-git-rag   /var/www/html/KUZAI/app/git-rag.repos.php   /var/www/html/KUZAI/public/api/git-rag-*.php   /var/www/html/KUZAI/public/api/chat.php   /var/www/html/KUZAI/public/assets/js/app.js   /var/www/html/KUZAI/public/assets/css/style.css   /var/www/html/KUZAI/public/index.php   /etc/systemd/system/kuzai-git-rag.service   /etc/systemd/system/llama-server-a.service.d/override.conf
```

Restore -->

```bash
sudo tar -xzf /root/kuzai-git-rag-code-export-YYYYMMDD-HHMMSS.tar.gz -C /
sudo systemctl daemon-reload
sudo systemctl restart llama-server-a.service
sudo systemctl restart kuzai-git-rag.service
sudo systemctl reload apache2
```

---

## 20. RECOMMENDED FUTURE CONTINUATION

The GIT-RAG system is stable at this stage. The next logical phase is LLM-assisted editing -->

```text
open file
ask the LLM for a modification
propose a patch
preview diff
APPLY TO FILE
STATUS
COMMIT
PUSH
REINDEX
```

Before this phase, keep the current module as a stable restore point.
