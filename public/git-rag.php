<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config.php';

$appName = htmlspecialchars($config['app']['name'], ENT_QUOTES, 'UTF-8');
$brandLine = htmlspecialchars($config['app']['brand_line'], ENT_QUOTES, 'UTF-8');
$model = htmlspecialchars($config['llm']['model'], ENT_QUOTES, 'UTF-8');
$assetVersion = (string) time();

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GIT-RAG - <?= $appName ?> - <?= $brandLine ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="application-name" content="<?= $appName ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=step222-gitrag-system-title-20260823-120605">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anta&display=swap" rel="stylesheet">

    <style id="git-rag-page-critical-step223">
        body.git-rag-page-body .git-rag-system-title-line {
            display: block !important;
            width: auto !important;
            margin: 0 0 4px 0 !important;
            padding: 0 !important;
            background: transparent !important;
            color: inherit !important;
            border: 0 !important;
            line-height: 1 !important;
        }

        body.git-rag-page-body #gitRagSystemTitleChip {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: auto !important;
            max-width: max-content !important;
            margin: 0 !important;
            padding: 2pt 8px !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            border: 1px solid #ffffff !important;
            border-radius: 0 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
            text-transform: uppercase !important;
            box-shadow: none !important;
            text-shadow: none !important;
            cursor: default !important;
            pointer-events: none !important;
            transition: none !important;
            transform: none !important;
        }

        body.git-rag-page-body #gitRagSystemTitleChip:hover {
            background: #ffffff !important;
            color: #000000 !important;
        }
    </style>

</head>
<body class="git-rag-page-body" data-page="git-rag">
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

        <main class="layout git-rag-page-layout">
            <section class="chat-panel git-rag-page-panel">
                <div class="chat-header git-rag-page-header">
                    <div>
                        <p class="git-rag-system-title-line"><span id="gitRagSystemTitleChip">GIT-RAG SYSTEM</span></p>
                        <h2>/Repository management/</h2>
                    </div>

                    <div class="chat-actions git-rag-page-top-actions" aria-hidden="true"></div>
                </div>

                <section class="git-rag-page-content" id="gitRagToolbar" aria-label="GIT-RAG repository manager">
                    <div class="git-rag-page-controls">
                        <button type="button" class="btn btn-secondary git-rag-main-btn" id="gitRagBtn" title="Back to GIT-RAG repositories list" hidden>GIT-RAG / BACK TO REPOS LIST</button>
                    </div>

                    <div class="git-rag-menu git-rag-menu-repos" id="gitRagRepoMenu" hidden></div>
                    <div class="git-rag-menu git-rag-menu-files" id="gitRagFilesMenu" hidden></div>
                </section>
            </section>
        </main>

        <nav class="git-rag-bottom-nav" aria-label="Back to main chat">
            <a class="git-rag-bottom-back-home" href="index.php">
                <span class="git-rag-bottom-back-home__label">BACK HOME</span>
                <span class="git-rag-bottom-back-home__arrow">--&gt;</span>
            </a>
        </nav>

        <footer class="site-footer site-footer--dynamic app-site-footer" aria-label="KUZ Network footer">
            <div class="site-footer__inner">
                <p class="site-footer__text">THE KUZ NETWORK - @2026 / BUILD LOCAL / KEEP CONTROL / OWN THE STACK</p>
            </div>
        </footer>
    </div>

    <script src="assets/js/git-rag-page.js?v=git-rag-page-<?= $assetVersion ?>"></script>
</body>
</html>
