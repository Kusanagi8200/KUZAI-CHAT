<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config.php';

$appName = htmlspecialchars((string)($config['app']['name'] ?? 'KUZAI'), ENT_QUOTES, 'UTF-8');
$brandLine = htmlspecialchars((string)($config['app']['brand_line'] ?? 'A KUZ NETWORK SOLUTION'), ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $appName ?> - Run Custom LLM Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="application-name" content="<?= $appName ?>">
    <style>
        :root {
            --bg: #000000;
            --panel: #050505;
            --panel-2: #090909;
            --border: rgba(255, 255, 255, 0.32);
            --border-soft: rgba(255, 255, 255, 0.18);
            --text: #f5f5f5;
            --soft: #d8d8d8;
            --muted: #8f8f8f;
            --ok: #67f3ba;
            --danger: #ff7b9d;
            --radius-xl: 26px;
            --radius-lg: 18px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", "Inter", Arial, sans-serif;
        }

        body {
            padding: 24px;
        }

        a {
            color: inherit;
        }

        button {
            font-family: inherit;
        }

        .page {
            width: min(1500px, 100%);
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            background: var(--panel);
            padding: 22px 26px;
            margin-bottom: 18px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 74px;
            height: 74px;
            border: 1px solid var(--border-soft);
            border-radius: 18px;
            display: grid;
            place-items: center;
            letter-spacing: 0.18em;
            font-weight: 900;
        }

        .brand-title {
            margin: 0;
            font-size: 2rem;
            letter-spacing: 0.38em;
            font-weight: 600;
        }

        .brand-line {
            margin: 8px 0 0;
            color: var(--soft);
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .subtitle {
            margin: 8px 0 0;
            color: var(--muted);
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 22px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            cursor: pointer;
            font-size: 0.84rem;
            font-weight: 850;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            text-decoration: none;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-primary {
            background: #ffffff;
            color: #000000;
            border-color: #ffffff;
        }

        .panel {
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            background: var(--panel);
            padding: 24px;
        }

        .kicker {
            margin: 0 0 8px;
            color: var(--muted);
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-weight: 900;
            font-size: 0.82rem;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            font-weight: 700;
        }

        h2 {
            font-size: 1.35rem;
            margin-bottom: 18px;
        }

        .status {
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--border-soft);
            border-radius: 16px;
            background: var(--panel-2);
            color: var(--soft);
            line-height: 1.5;
        }

        .status-ok {
            border-color: rgba(103, 243, 186, 0.55);
            color: #dffdf2;
        }

        .status-error {
            border-color: rgba(255, 123, 157, 0.58);
            color: #ffdbe4;
        }

        .profiles {
            display: grid;
            gap: 14px;
        }

        .profile {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            background: var(--panel-2);
            padding: 18px;
        }

        .profile-active {
            border-color: rgba(103, 243, 186, 0.65);
        }

        .profile-title {
            margin: 0 0 8px;
            color: var(--text);
            font-size: 1.05rem;
            font-weight: 850;
            letter-spacing: 0.04em;
        }

        .profile-desc {
            margin: 0 0 10px;
            color: var(--soft);
            line-height: 1.45;
        }

        .profile-meta {
            color: var(--muted);
            font-size: 0.82rem;
            overflow-wrap: anywhere;
        }

        .empty {
            border: 1px solid var(--border-soft);
            border-radius: 18px;
            background: var(--panel-2);
            padding: 18px;
            color: var(--soft);
        }

        @media (max-width: 820px) {
            body {
                padding: 12px;
            }

            .topbar,
            .brand {
                flex-direction: column;
                align-items: flex-start;
            }

            .actions {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr;
            }

            .profile {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
            }
        }
    
        /* KUZAI_PROFILES_BUTTON_BOLD_BEGIN */
        button,
        .btn,
        a.btn,
        .run-profile-btn {
            font-weight: 900 !important;
        }

        button *,
        .btn *,
        a.btn * {
            font-weight: 900 !important;
        }
        /* KUZAI_PROFILES_BUTTON_BOLD_END */

    
        /* KUZAI_PROFILES_BORDER_PLUS_1PX_BEGIN */
        .topbar,
        .panel,
        .profile,
        .status,
        .empty,
        .logo,
        .btn,
        button,
        a.btn,
        .run-profile-btn {
            border-width: 2px !important;
        }

        .btn:focus-visible,
        button:focus-visible,
        a.btn:focus-visible {
            border-width: 2px !important;
        }
        /* KUZAI_PROFILES_BORDER_PLUS_1PX_END */

    
        /* KUZAI_MANUAL_LOGO_FIX_BEGIN */
        .logo.logo-image {
            display: grid !important;
            place-items: center !important;
            width: 74px !important;
            height: 74px !important;
            padding: 0 !important;
            overflow: hidden !important;
            background: transparent !important;
            border-radius: 18px !important;
        }

        .logo.logo-image img {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
        }
        /* KUZAI_MANUAL_LOGO_FIX_END */

    
        /* KUZAI_SHARED_PAGE_WIDTH_SYNC_BEGIN */
        :root {
            --kuzai-shared-app-width: 1500px;
            --kuzai-shared-app-margin: 28px;
            --kuzai-shared-scale: 1.20;
            --kuzai-shared-radius-xl: 22px;
            --kuzai-shared-border: #5f5f5f;
            --kuzai-shared-border-soft: #323232;
        }

        html,
        body {
            overflow-x: hidden !important;
        }

        .page {
            width: min(var(--kuzai-shared-app-width), calc(100% - var(--kuzai-shared-app-margin))) !important;
            max-width: min(var(--kuzai-shared-app-width), calc(100% - var(--kuzai-shared-app-margin))) !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding: 8px 0 16px !important;
            box-sizing: border-box !important;
        }

        @supports (zoom: 1) {
            .page {
                zoom: var(--kuzai-shared-scale) !important;
            }
        }

        @supports not (zoom: 1) {
            .page {
                transform: scale(var(--kuzai-shared-scale)) !important;
                transform-origin: top center !important;
                width: calc((100% - var(--kuzai-shared-app-margin)) / var(--kuzai-shared-scale)) !important;
                max-width: calc(var(--kuzai-shared-app-width) / var(--kuzai-shared-scale)) !important;
            }
        }

        .topbar {
            width: 100% !important;
            max-width: 100% !important;
            min-height: 104px !important;
            margin: 0 0 14px 0 !important;
            padding: 12px 16px !important;
            border: 2px solid var(--kuzai-shared-border) !important;
            border-radius: var(--kuzai-shared-radius-xl) !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }

        .panel,
        .layout,
        .profiles,
        .profile-list,
        .profile-grid,
        .form-grid {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        .panel {
            border-width: 2px !important;
            border-radius: var(--kuzai-shared-radius-xl) !important;
        }

        .layout {
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .layout > *,
        .panel > *,
        .profiles > *,
        .profile-list > *,
        .profile-grid > * {
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        @media (max-width: 760px) {
            .page {
                width: min(100% - 20px, var(--kuzai-shared-app-width)) !important;
                max-width: min(100% - 20px, var(--kuzai-shared-app-width)) !important;
                padding-top: 12px !important;
            }

            @supports not (zoom: 1) {
                .page {
                    width: calc((100% - 20px) / var(--kuzai-shared-scale)) !important;
                    max-width: calc((100% - 20px) / var(--kuzai-shared-scale)) !important;
                }
            }

            .topbar {
                width: 100% !important;
                max-width: 100% !important;
            }
        }
        /* KUZAI_SHARED_PAGE_WIDTH_SYNC_END */

    
        /* KUZAI_LOGO_MATCH_INDEX_NO_FRAME_BEGIN */
        .logo,
        .logo.logo-image {
            width: 78px !important;
            height: 78px !important;
            min-width: 78px !important;
            max-width: 78px !important;
            min-height: 78px !important;
            max-height: 78px !important;
            flex: 0 0 78px !important;
            display: grid !important;
            place-items: center !important;
            padding: 0 !important;
            margin: 0 !important;
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            background-color: transparent !important;
            border-radius: 0 !important;
            overflow: visible !important;
            line-height: 0 !important;
        }

        .logo.logo-image img,
        .logo img {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            object-fit: contain !important;
            object-position: center center !important;
            transform: scale(1.25) !important;
            transform-origin: center center !important;
            border: 0 !important;
            outline: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
        }

        .brand {
            gap: 26px !important;
        }
        /* KUZAI_LOGO_MATCH_INDEX_NO_FRAME_END */

    
        /* KUZAI_TOPBAR_VERTICAL_ALIGN_BEGIN */
        .page {
            padding-top: 18px !important;
        }

        @media (max-width: 760px) {
            .page {
                padding-top: 22px !important;
            }
        }
        /* KUZAI_TOPBAR_VERTICAL_ALIGN_END */

    
        /* KUZAI_SECONDARY_TOPBAR_REALIGN_BEGIN */
        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
        }

        body {
            min-height: 100vh !important;
        }

        .page {
            margin-top: 0 !important;
            padding-top: 18px !important;
        }

        .topbar {
            margin-top: 0 !important;
        }

        @media (max-width: 760px) {
            .page {
                padding-top: 22px !important;
            }
        }
        /* KUZAI_SECONDARY_TOPBAR_REALIGN_END */

    
        /* KUZAI_ANTA_FONT_SYNC_BEGIN */
        html,
        body,
        body *,
        button,
        .btn,
        a.btn,
        input,
        textarea,
        select,
        option,
        label,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        span,
        strong,
        small,
        div,
        section,
        article,
        header,
        main,
        aside,
        nav {
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
        }

        button,
        .btn,
        a.btn,
        input[type="button"],
        input[type="submit"],
        input[type="reset"] {
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-weight: 900 !important;
            letter-spacing: 0.12em !important;
        }

        .brand h1,
        .brand-line,
        .subtitle,
        .section-kicker,
        .profile-title,
        .profile-meta,
        .panel h2,
        .panel h3,
        .topbar h1,
        .topbar p {
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
        }
        /* KUZAI_ANTA_FONT_SYNC_END */

    
        /* KUZAI_TITLE_SIZE_MATCH_INDEX_BEGIN */
        .topbar .brand h1,
        .brand h1,
        .topbar h1,
        .brand-title {
            margin: 0 !important;
            color: #ffffff !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 2rem !important;
            line-height: 1.1 !important;
            letter-spacing: 0.12em !important;
            font-weight: 400 !important;
            text-transform: none !important;
        }

        .brand-line {
            margin: 5px 0 0 !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.82rem !important;
            line-height: 1.2 !important;
            letter-spacing: 0.18em !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
        }

        .subtitle {
            margin: 5px 0 0 !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.78rem !important;
            line-height: 1.2 !important;
            letter-spacing: 0.12em !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
        }
        /* KUZAI_TITLE_SIZE_MATCH_INDEX_END */

    
        /* KUZAI_ALL_TEXT_BOLD_FINAL_BEGIN */
        html,
        body,
        body *,
        .page,
        .page *,
        .topbar,
        .topbar *,
        .brand,
        .brand *,
        .panel,
        .panel *,
        .layout,
        .layout *,
        .profile,
        .profile *,
        .profile-card,
        .profile-card *,
        .profiles,
        .profiles *,
        .form-grid,
        .form-grid *,
        .status,
        .status *,
        .json-preview,
        .json-preview *,
        button,
        .btn,
        a.btn,
        input,
        textarea,
        select,
        option,
        label,
        span,
        strong,
        small,
        p,
        div,
        section,
        article,
        header,
        main,
        aside,
        nav,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        li,
        ul,
        ol {
            font-weight: 900 !important;
        }

        .brand h1,
        .topbar h1,
        .brand-title,
        .brand-line,
        .subtitle,
        .section-kicker,
        .panel h2,
        .panel h3,
        .profile-title,
        .profile-meta,
        .profile-id,
        .profile-label {
            font-weight: 900 !important;
        }

        button,
        .btn,
        a.btn,
        input[type="button"],
        input[type="submit"],
        input[type="reset"],
        #previewBtn,
        #saveBtn,
        #saveRunBtn,
        .run-profile-btn {
            font-weight: 900 !important;
        }
        /* KUZAI_ALL_TEXT_BOLD_FINAL_END */

    
        /* KUZAI_FORCE_TITLE_MATCH_INDEX_FINAL_BEGIN */
        .topbar .brand h1,
        .brand h1,
        .topbar h1,
        h1.brand-title {
            margin: 0 !important;
            padding: 0 !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 2rem !important;
            line-height: 1.1 !important;
            letter-spacing: 0.12em !important;
            font-weight: 900 !important;
            font-style: normal !important;
            text-transform: none !important;
            color: #ffffff !important;
            transform: none !important;
            text-rendering: geometricPrecision !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
        }

        .topbar .brand,
        .brand {
            align-items: center !important;
        }

        .topbar .brand > div:not(.logo):not(.logo-image),
        .brand > div:not(.logo):not(.logo-image) {
            display: block !important;
            line-height: 1 !important;
        }
        /* KUZAI_FORCE_TITLE_MATCH_INDEX_FINAL_END */

    
        /* KUZAI_DELETE_PROFILE_UI_BEGIN */
        .profile-actions,
        .profile-card-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 10px !important;
        }

        .delete-profile-btn {
            border: 2px solid rgba(255, 255, 255, 0.78) !important;
            background: #000000 !important;
            color: #ffffff !important;
            font-weight: 900 !important;
        }

        .delete-profile-btn:hover:not(:disabled) {
            border-color: rgba(255, 255, 255, 0.96) !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }

        .delete-profile-btn:disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
        }
        /* KUZAI_DELETE_PROFILE_UI_END */

    
        /* KUZAI_SECONDARY_VALUE_TEXT_SIZE_MATCH_INDEX_BEGIN */
        .panel p:not(.section-kicker),
        .panel div:not(.section-kicker),
        .profile-title,
        .profile-desc,
        .profile-meta,
        .profile-card-title,
        .profile-card-desc,
        .profile-card-meta,
        .help,
        .status,
        .empty,
        .side-list,
        .side-list *,
        .json-preview,
        .json-preview *,
        input,
        textarea,
        select,
        option {
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.94rem !important;
            line-height: 1.45 !important;
            font-weight: 900 !important;
            letter-spacing: 0.02em !important;
        }

        .form-row input,
        .form-row textarea,
        .form-row select,
        .form-row .help,
        .form-row p,
        .form-row div:not(.section-kicker) {
            font-size: 0.94rem !important;
            line-height: 1.45 !important;
            font-weight: 900 !important;
            letter-spacing: 0.02em !important;
        }

        .profile-desc,
        .profile-card-desc,
        .help {
            color: #d7d7d7 !important;
        }

        .profile-meta,
        .profile-card-meta {
            color: #8f8f8f !important;
            font-size: 0.86rem !important;
            line-height: 1.4 !important;
        }

        label,
        .section-kicker,
        .panel .section-kicker,
        .form-row label,
        .profile-section-title,
        .card-section-title {
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.78rem !important;
            line-height: 1.3 !important;
            letter-spacing: 0.22em !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            color: #8f8f8f !important;
        }

        .panel h2,
        .panel h3 {
            font-size: 1rem !important;
            line-height: 1.45 !important;
            font-weight: 900 !important;
            letter-spacing: 0.02em !important;
        }
        /* KUZAI_SECONDARY_VALUE_TEXT_SIZE_MATCH_INDEX_END */

    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anta&display=swap" rel="stylesheet">
</head>
<body>
<div class="page">
    <header class="topbar">
        <div class="brand">
            <div class="logo logo-image"><img src="assets/img/kuzai-logo.png" alt="KUZAI logo"></div>
            <div>
                <h1 class="brand-title"><?= $appName ?></h1>
                <p class="brand-line"><?= $brandLine ?></p>
                <p class="subtitle">/ RUN CUSTOM LLM PROFILE /</p>
            </div>
        </div>

        <div class="actions">
            <a class="btn" href="/KUZAI/">BACK</a>
            <a class="btn" href="/KUZAI/personality.php">CREATE NEW PROFILE</a>
        </div>
    </header>

    <main class="panel">
        <p class="kicker">CUSTOM LLM</p>
        <h2>Select a profile and run it for the current KUZAI session</h2>

        <div class="status" id="statusBox">
            Loading profiles...
        </div>

        <div class="profiles" id="profilesList"></div>
    </main>
</div>

<script>
/* KUZAI_PROFILES_JS_REPAIR_BEGIN */
const API_URL = 'api/personality.php';
const ACTIVE_ID_KEY = 'kuzai.customLlm.activeProfileId.session.v1';
const ACTIVE_LABEL_KEY = 'kuzai.customLlm.activeProfileLabel.session.v1';
const LOCKED_KEY = 'kuzai.customLlm.locked.session.v1';

const statusEl = document.getElementById('status') || document.querySelector('.status');
const profilesList = document.getElementById('profilesList');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setStatus(message, type = 'ok') {
    if (!statusEl) {
        return;
    }

    statusEl.classList.remove('status-ok', 'status-error', 'ok', 'error');

    if (type !== 'error') {
        statusEl.textContent = '';
        statusEl.style.display = 'none';
        return;
    }

    statusEl.textContent = message;
    statusEl.style.display = 'block';
    statusEl.classList.add('status-error');
}

function getActiveProfileId() {
    return sessionStorage.getItem(ACTIVE_ID_KEY) || '';
}

function isLocked() {
    return sessionStorage.getItem(LOCKED_KEY) === '1';
}

function clearActiveProfileIfNeeded(id) {
    if (getActiveProfileId() === id) {
        sessionStorage.removeItem(ACTIVE_ID_KEY);
        sessionStorage.removeItem(ACTIVE_LABEL_KEY);
        sessionStorage.removeItem(LOCKED_KEY);
    }
}

function runProfile(id, label) {
    id = String(id || '').trim();
    label = String(label || id).trim();

    if (!id) {
        setStatus('Invalid profile ID.', 'error');
        return;
    }

    if (isLocked() && getActiveProfileId() && getActiveProfileId() !== id) {
        setStatus('Profile is locked during the current discussion. Return to chat and use CLEAR before changing profile.', 'error');
        return;
    }

    sessionStorage.setItem(ACTIVE_ID_KEY, id);
    sessionStorage.setItem(ACTIVE_LABEL_KEY, label || id);
    sessionStorage.removeItem(LOCKED_KEY);

    setStatus(`Profile selected: ${label}`);

    window.setTimeout(() => {
        window.location.href = '/KUZAI/';
    }, 250);
}

async function deleteProfile(id, label) {
    id = String(id || '').trim();
    label = String(label || id).trim();

    if (!id) {
        setStatus('Invalid profile ID.', 'error');
        return;
    }

    if (id === 'default-generalist') {
        setStatus('default-generalist is protected and cannot be deleted.', 'error');
        return;
    }

    if (isLocked() && getActiveProfileId() === id) {
        setStatus('This profile is locked in the current discussion. Return to chat and use CLEAR before deleting it.', 'error');
        return;
    }

    const confirmed = window.confirm(
        `DELETE profile "${label}" ?\n\nThe JSON file will be moved to storage/personality_profiles/.deleted/`
    );

    if (!confirmed) {
        return;
    }

    setStatus(`Deleting profile: ${label}...`);

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                id,
            }),
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Unable to delete profile');
        }

        clearActiveProfileIfNeeded(id);
        setStatus(`Profile deleted: ${label}`);
        renderProfiles(data.profiles || []);
    } catch (error) {
        setStatus(`Delete error: ${error.message}`, 'error');
    }
}

function renderProfiles(profiles) {
    if (!profilesList) {
        setStatus('profilesList container not found.', 'error');
        return;
    }

    profilesList.innerHTML = '';

    if (!Array.isArray(profiles) || profiles.length === 0) {
        profilesList.innerHTML = '<div class="empty">No profile found. Create a new profile first.</div>';
        setStatus('No profile available.', 'error');
        return;
    }

    const activeId = getActiveProfileId();

    for (const profile of profiles) {
        const id = String(profile.id || '');
        const label = String(profile.label || id);
        const description = String(profile.description || '');
        const updatedAt = String(profile.updated_at || '');
        const active = id && activeId === id;
        const isDefaultProfile = id === 'default-generalist' || profile.protected === true;
        const deleteDisabled = isDefaultProfile || (isLocked() && active);

        const item = document.createElement('div');
        item.className = `profile${active ? ' profile-active' : ''}`;

        item.innerHTML = `
            <div>
                <p class="profile-title">${escapeHtml(label)}${active ? ' / ACTIVE' : ''}</p>
                <p class="profile-desc">${escapeHtml(description)}</p>
                <div class="profile-meta">${escapeHtml(id)}${updatedAt ? ' / ' + escapeHtml(updatedAt) : ''}</div>
            </div>
            <div class="profile-actions">
                <button type="button" class="btn btn-primary run-profile-btn" data-id="${escapeHtml(id)}" data-label="${escapeHtml(label)}">
                    RUN PROFILE
                </button>
                <button type="button" class="btn delete-profile-btn" data-id="${escapeHtml(id)}" data-label="${escapeHtml(label)}" ${deleteDisabled ? 'disabled' : ''} title="${isDefaultProfile ? 'Default profile is protected' : 'Delete profile'}">
                    DELETE
                </button>
            </div>
        `;

        profilesList.appendChild(item);
    }

    if (isLocked()) {
        setStatus('A profile is already locked for the current discussion. Use CLEAR in chat before changing it.');
    } else {
        setStatus('Select RUN PROFILE to apply a profile to the current session.');
    }
}

async function loadProfiles() {
    if (!profilesList) {
        setStatus('profilesList container not found.', 'error');
        return;
    }

    profilesList.innerHTML = '<div class="empty">Loading profiles...</div>';
    setStatus('Loading profiles...');

    try {
        const response = await fetch(`${API_URL}?action=list`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Unable to load profiles');
        }

        renderProfiles(data.profiles || []);
    } catch (error) {
        profilesList.innerHTML = '';
        setStatus(`Profile load error: ${error.message}`, 'error');
    }
}

if (profilesList) {
    profilesList.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLButtonElement)) {
            return;
        }

        const id = target.dataset.id || '';
        const label = target.dataset.label || id;

        if (target.classList.contains('run-profile-btn')) {
            runProfile(id, label);
            return;
        }

        if (target.classList.contains('delete-profile-btn')) {
            deleteProfile(id, label);
        }
    });
}

loadProfiles();
/* KUZAI_PROFILES_JS_REPAIR_END */
</script>
</body>
</html>
