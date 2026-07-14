<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config.php';

$appName = htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8');
$brandLine = htmlspecialchars($config['app']['brand_line'], ENT_QUOTES, 'UTF-8');
$subtitle = htmlspecialchars($config['app']['subtitle'], ENT_QUOTES, 'UTF-8');
$version = htmlspecialchars($config['app']['version'], ENT_QUOTES, 'UTF-8');
$model = htmlspecialchars($config['llm']['model'], ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $appName ?> - <?= $brandLine ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="application-name" content="<?= $appName ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=header-meta-minus-1pt-1784036677">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anta&display=swap" rel="stylesheet">

</head>
<body>
    <div class="app-shell">
        <header class="topbar site-style-topbar" aria-label="Main header">
            <a class="brand-mark" href="./" aria-label="KUZAI home">
                <img
                    class="brand-mark__logo"
                    src="assets/img/kuz_network_logo_transparent.png"
                    alt="KUZ Network logo"
                    width="112"
                    height="112"
                >
            </a>

            <div class="header-title-block" aria-label="Project title">
                <h1 class="header-title-block__title">KUZAI - THE LOCAL AI</h1>
                <p class="header-title-block__meta">A KUZ NETWORK SOLUTION - BETA-0.03.2026</p>
            </div>

            <div class="topbar-meta site-header-runtime" aria-label="Runtime status">
                <div class="meta-pill site-header-runtime__pill">
                    <span class="meta-label">MODEL</span>
                    <span class="meta-value" id="modelName"><?= $model ?></span>
                </div>
                <div class="meta-pill site-header-runtime__pill" id="serverState">
                    <span class="state-dot meta-dot"></span>
                    <span class="meta-value">CHECKING</span>
                </div>
            </div>
        </header>

        <main class="layout">
            <section class="chat-panel">
                <div class="chat-header">
                    <div>
                        <p class="section-kicker">MEMORIES SYSTEM UP</p>
                        <h2>/Start a new process/</h2>
                    </div>
                    <div class="chat-actions">
                        <div class="git-rag-toolbar" id="gitRagToolbar">
                            <button type="button" class="btn btn-secondary git-rag-active-repo-btn" id="gitRagActiveRepoBtn" title="No GIT-RAG repository selected">NO REPO</button>
                            <button type="button" class="btn btn-secondary git-rag-main-btn" id="gitRagBtn" title="Select GIT-RAG repository">GIT-RAG</button>
                            <div class="git-rag-menu git-rag-menu-repos" id="gitRagRepoMenu" hidden></div>
                            <div class="git-rag-menu git-rag-menu-files" id="gitRagFilesMenu" hidden></div>
                        </div>
                        <button type="button" class="btn btn-secondary custom-llm-main-btn" id="customLlmBtn" title="Select KUZAI behavior profile">CUSTOM LLM</button>
                        <button type="button" class="btn btn-secondary" id="clearBtn">CLEAR</button>
                    </div>
                </div>

                <div class="messages" id="messages">
                    <div class="message message-assistant">
                        <div class="message-role">KUZAI</div>
                        <div class="message-content">
                            /Ready for new instructions/
                        </div>
                    </div>
                </div>

                <form class="composer" id="chatForm">
                    <textarea
                        id="promptInput"
                        name="prompt"
                        rows="4"
                        maxlength="8000"
                        placeholder="Write a message..."
                        autocomplete="off"
                        spellcheck="false"
                    ></textarea>

                    <input
                        type="file"
                        id="fileInput"
                        class="file-input"
                        accept=".txt,.md,.log,.csv,.json,.xml,.yaml,.yml,.php,.py,.js,.css,.html,.htm,.sh,.conf,.ini,.service"
                    >

                    <div class="attachments" id="attachments"></div>

                    <div class="web-results" id="webResults"></div>

                    <div class="composer-footer">
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
                    <p class="section-kicker">BACKEND</p>
                    <h3>llama.cpp</h3>
                    <div class="kv">
                        <span>Endpoint</span>
                        <strong>/v1/chat/completions</strong>
                    </div>
                    <div class="kv">
                        <span>Model in use</span>
                        <strong><?= $model ?></strong>
                    </div>
                    <div class="kv">
                        <span>Generation mode</span>
                        <strong>/no_think</strong>
                    </div>
                </div>

                <div class="side-card">
                    <p class="section-kicker">UPLOAD</p>
                    <h3>File analysis</h3>
                    <div class="kv">
                        <span>Supported first</span>
                        <strong>text, code, config, logs, JSON, CSV</strong>
                    </div>
                    <div class="kv">
                        <span>Max size</span>
                        <strong>2 MB per file</strong>
                    </div>
                    <div class="kv">
                        <span>Processing</span>
                        <strong>server-side text extraction</strong>
                    </div>
                </div>

                <div class="side-card">
                    <p class="section-kicker">SETTINGS</p>
                    <h3>Generation</h3>
                    <div class="kv">
                        <span>Temperature</span>
                        <strong><?= htmlspecialchars((string) $config['llm']['temperature'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="kv">
                        <span>Top P</span>
                        <strong><?= htmlspecialchars((string) $config['llm']['top_p'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="kv">
                        <span>Max tokens</span>
                        <strong><?= htmlspecialchars((string) $config['llm']['max_tokens'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
</aside>
        </main>

        <footer class="site-footer site-footer--dynamic app-site-footer" aria-label="KUZ Network footer">
            <div class="site-footer__inner">
                <p class="site-footer__text">THE KUZ NETWORK - @2026 / BUILD LOCAL / KEEP CONTROL / OWN THE STACK</p>
            </div>
        </footer>
    </div>

    <script src="assets/js/app.js?v=custom-llm-runtime-1-git-rag-file-edit-ui-1"></script>
</body>
</html>
