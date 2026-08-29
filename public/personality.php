<?php

declare(strict_types=1);

$config = require __DIR__ . '/../app/config.php';

$appName = htmlspecialchars((string)($config['app']['name'] ?? 'KUZAI'), ENT_QUOTES, 'UTF-8');
$brandLine = htmlspecialchars((string)($config['app']['brand_line'] ?? 'A KUZ NETWORK SOLUTION'), ENT_QUOTES, 'UTF-8');
$model = htmlspecialchars((string)($config['llm']['model'] ?? 'LOCAL MODEL'), ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $appName ?> - Custom LLM Profile</title>
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
            --muted: #9a9a9a;
            --soft: #d8d8d8;
            --danger: #ff7b9d;
            --success: #67f3ba;
            --radius-xl: 26px;
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", "Inter", Arial, sans-serif;
        }

        body {
            padding: 24px;
        }

        button,
        input,
        textarea,
        select {
            font-family: inherit;
        }

        .page {
            width: min(1600px, 100%);
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 22px 26px;
            background: var(--panel);
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
            color: var(--text);
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
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .btn-primary {
            background: #ffffff;
            color: #000000;
            border-color: #ffffff;
        }

        .btn-danger {
            border-color: rgba(255, 123, 157, 0.58);
            color: #ffdbe4;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 420px;
            gap: 18px;
            align-items: start;
        }

        .panel {
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            background: var(--panel);
            padding: 24px;
        }

        .panel + .panel {
            margin-top: 18px;
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

        h3 {
            font-size: 1rem;
            margin: 26px 0 14px;
            color: var(--soft);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .form-row {
            display: grid;
            gap: 8px;
        }

        .form-row-full {
            grid-column: 1 / -1;
        }

        label {
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 850;
        }

        input,
        textarea,
        select {
            width: 100%;
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            background: var(--panel-2);
            color: var(--text);
            padding: 13px 14px;
            outline: none;
            font-size: 0.96rem;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
            line-height: 1.5;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: rgba(255, 255, 255, 0.68);
        }

        .help {
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.5;
            margin: 0;
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 9px;
            border: 1px solid var(--border-soft);
            border-radius: 12px;
            padding: 10px;
            background: var(--panel-2);
            color: var(--soft);
            font-size: 0.88rem;
        }

        .check-item input {
            width: auto;
            accent-color: #ffffff;
        }

        .side-list {
            display: grid;
            gap: 10px;
        }

        .profile-card {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 14px;
            background: var(--panel-2);
        }

        .profile-card-title {
            margin: 0 0 6px;
            font-weight: 800;
            color: var(--text);
        }

        .profile-card-desc {
            margin: 0;
            color: var(--muted);
            line-height: 1.45;
            font-size: 0.86rem;
        }

        .profile-card-meta {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.76rem;
            word-break: break-word;
        }

        .status {
            margin-top: 14px;
            border: 1px solid #ffffff;
            border-radius: 0 !important;
            padding: 14px;
            background: var(--panel-2);
            color: var(--soft);
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            min-height: 52px;
        }

        .status-ok {
            border-color: #ffffff;
            color: #dffdf2;
        }

        .status-error {
            border-color: #ffffff;
            color: #ffdbe4;
        }

        .json-preview {
            min-height: 440px;
            font-family: "Cascadia Code", "Fira Code", monospace;
            font-size: 0.82rem;
            line-height: 1.45;
        }

        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .check-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
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
                justify-content: stretch;
            }

            .btn {
                width: 100%;
            }

            .form-grid,
            .check-grid {
                grid-template-columns: 1fr;
            }
        }
    
        /* KUZAI_PERSONALITY_BUTTON_FIX_BEGIN */
        .actions .btn,
        .actions a.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            text-decoration: none;
            line-height: 1;
            white-space: nowrap;
        }

        .actions a.btn:link,
        .actions a.btn:visited,
        .actions a.btn:hover,
        .actions a.btn:active {
            color: var(--text);
            text-decoration: none;
        }

        .actions .btn-primary:link,
        .actions .btn-primary:visited,
        .actions .btn-primary:hover,
        .actions .btn-primary:active {
            color: #000000;
            text-decoration: none;
        }
        /* KUZAI_PERSONALITY_BUTTON_FIX_END */

    
        /* KUZAI_PERSONALITY_BUTTON_BOLD_BEGIN */
        button,
        .btn,
        a.btn,
        #previewBtn,
        #saveBtn,
        #saveRunBtn {
            font-weight: 900 !important;
        }

        button *,
        .btn *,
        a.btn * {
            font-weight: 900 !important;
        }
        /* KUZAI_PERSONALITY_BUTTON_BOLD_END */

    
        /* KUZAI_PROFILE_FIELDS_ALIGNMENT_FIX_BEGIN */
        .form-grid {
            align-items: start !important;
        }

        .form-row {
            align-self: start !important;
            align-content: start !important;
            grid-auto-rows: auto !important;
        }

        #profileId,
        #label {
            height: 46px !important;
            min-height: 46px !important;
            max-height: 46px !important;
            line-height: 1.2 !important;
        }

        .form-row:has(#profileId),
        .form-row:has(#label) {
            display: grid !important;
            grid-template-rows: auto 46px auto !important;
            align-content: start !important;
        }

        .form-row:has(#profileId) label,
        .form-row:has(#label) label {
            min-height: 18px !important;
            line-height: 18px !important;
            margin: 0 !important;
        }
        /* KUZAI_PROFILE_FIELDS_ALIGNMENT_FIX_END */

    
        /* KUZAI_PERSONALITY_BORDER_PLUS_1PX_BEGIN */
        .topbar,
        .panel,
        .profile-card,
        .status,
        input,
        textarea,
        select,
        .check-item {
            border-width: 2px !important;
        }

        .btn,
        button,
        a.btn,
        #previewBtn,
        #saveBtn,
        #saveRunBtn {
            border-width: 2px !important;
        }

        .logo {
            border-width: 2px !important;
        }

        .json-preview {
            border-width: 2px !important;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-width: 2px !important;
        }
        /* KUZAI_PERSONALITY_BORDER_PLUS_1PX_END */

    
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
        .profile-card-actions {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 10px !important;
            margin-top: 12px !important;
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

    
        /* KUZAI_PERSONALITY_SERVER_PROFILES_CLEAN_BEGIN */
        #profileList {
            display: grid !important;
            gap: 12px !important;
        }

        #profileList .profile-card {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 16px !important;
            padding: 16px 18px !important;
            border: 2px solid #5f5f5f !important;
            border-radius: 18px !important;
            background: #000000 !important;
        }

        #profileList .profile-card-title {
            margin: 0 !important;
            padding: 0 !important;
            color: #ffffff !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.94rem !important;
            line-height: 1.45 !important;
            font-weight: 900 !important;
            letter-spacing: 0.02em !important;
        }

        #profileList .profile-card-desc,
        #profileList .profile-card-meta,
        #profileList .profile-desc,
        #profileList .profile-meta {
            display: none !important;
        }

        #profileList .profile-card-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 10px !important;
            margin: 0 !important;
        }

        #profileList .server-profile-delete-btn,
        #profileList .delete-profile-btn {
            min-width: 100px !important;
            height: 38px !important;
            min-height: 38px !important;
            border: 2px solid rgba(255, 255, 255, 0.78) !important;
            border-radius: 14px !important;
            background: #000000 !important;
            color: #ffffff !important;
            font-family: "Anta", "Segoe UI", "Inter", Arial, sans-serif !important;
            font-size: 0.72rem !important;
            line-height: 1 !important;
            font-weight: 900 !important;
            letter-spacing: 0.12em !important;
        }

        #profileList .server-profile-delete-btn:hover:not(:disabled),
        #profileList .delete-profile-btn:hover:not(:disabled) {
            border-color: rgba(255, 255, 255, 0.96) !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }

        #profileList .server-profile-delete-btn:disabled,
        #profileList .delete-profile-btn:disabled {
            opacity: 0.35 !important;
            cursor: not-allowed !important;
        }

        .panel:has(#profileList) .status:empty {
            display: none !important;
            border: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        /* KUZAI_PERSONALITY_SERVER_PROFILES_CLEAN_END */

    </style>

    <link rel="stylesheet" href="assets/css/style.css?v=personality-step250">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anta&display=swap" rel="stylesheet">

<style id="personality-shell-step250">

html,
body.personality-page-body {
    width: 100% !important;
    min-width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow-x: hidden !important;
    background: #000000 !important;
}

body.personality-page-body .personality-page-shell {
    width: 90vw !important;
    max-width: 1800px !important;
    min-width: 0 !important;

    margin: 24px auto 0 auto !important;
    padding: 0 !important;

    box-sizing: border-box !important;

    zoom: 1 !important;
    transform: none !important;
}


/* =========================================================
   HEADER
   ========================================================= */

body.personality-page-body
.personality-page-shell
> .topbar.site-style-topbar {
    width: 100% !important;
    max-width: 100% !important;

    margin-left: 0 !important;
    margin-right: 0 !important;

    box-sizing: border-box !important;
}


/* Ensure actual central alignment */

body.personality-page-body
.personality-page-shell
> .topbar.site-style-topbar
.header-title-block {
    text-align: center !important;
}

body.personality-page-body
.personality-page-shell
> .topbar.site-style-topbar
.header-title-block__title,
body.personality-page-body
.personality-page-shell
> .topbar.site-style-topbar
.header-title-block__meta {
    width: 100% !important;
    text-align: center !important;
}


/* =========================================================
   ACTION ROW BELOW HEADER
   Existing commands preserved
   ========================================================= */

body.personality-page-body
.personality-page-shell
> .personality-action-toolbar {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;

    align-items: center !important;
    justify-content: flex-end !important;

    gap: 8px !important;

    width: 100% !important;

    margin: 12px 0 18px 0 !important;
    padding: 0 !important;

    box-sizing: border-box !important;
}

body.personality-page-body
.personality-action-toolbar
.btn {
    min-height: 0 !important;

    margin: 0 !important;
    padding: 4px 10px !important;

    border-radius: 0 !important;

    line-height: 1.1 !important;

    white-space: nowrap !important;
}


/* =========================================================
   BACK HOME
   Same structure as other pages
   ========================================================= */

body.personality-page-body .personality-bottom-nav {
    display: block !important;

    width: 100% !important;

    margin: 40px 0 26px 0 !important;
    padding: 0 !important;

    box-sizing: border-box !important;
}

body.personality-page-body .personality-bottom-back-home {
    position: relative !important;
    isolation: isolate !important;

    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;

    width: 100% !important;

    margin: 0 !important;
    padding: .48rem 1rem !important;

    box-sizing: border-box !important;

    overflow: hidden !important;

    background: #000000 !important;
    color: #ffffff !important;

    border-top: 1px solid #333333 !important;
    border-bottom: 1px solid #333333 !important;
    border-left: 0 !important;
    border-right: 0 !important;
    border-radius: 0 !important;

    font-family: "Anta", "Segoe UI", Arial, sans-serif !important;
    font-size: clamp(1.05rem, 1.25vw, 1.45rem) !important;
    font-weight: 700 !important;

    letter-spacing: .08em !important;
    line-height: 1 !important;

    text-decoration: none !important;
    text-transform: uppercase !important;
}

body.personality-page-body .personality-bottom-back-home::before {
    content: "" !important;

    position: absolute !important;
    inset: 0 !important;

    z-index: 0 !important;

    background: #ffffff !important;

    transform: scaleX(0) !important;
    transform-origin: center center !important;

    transition: transform 240ms cubic-bezier(.16,1,.3,1) !important;
}

body.personality-page-body .personality-bottom-back-home__label,
body.personality-page-body .personality-bottom-back-home__arrow {
    position: relative !important;
    z-index: 1 !important;

    color: inherit !important;
}

body.personality-page-body .personality-bottom-back-home:hover,
body.personality-page-body .personality-bottom-back-home:focus-visible {
    color: #000000 !important;
    outline: none !important;
}

body.personality-page-body .personality-bottom-back-home:hover::before,
body.personality-page-body .personality-bottom-back-home:focus-visible::before {
    transform: scaleX(1) !important;
}


/* Footer uses existing common KUZAI footer CSS */

body.personality-page-body .site-footer.app-site-footer {
    width: 100% !important;
    max-width: 100% !important;

    margin: 0 !important;

    box-sizing: border-box !important;
}

</style>


<style id="personality-section-titles-step251">

/* =========================================================
   KUZAI PERSONALITY — SECTION TITLES
   WHITE / BLACK STATIC
   BLACK CENTER EXPANSION ON HOVER
   ========================================================= */

/*
 * Main section labels:
 * PROFILE EDITOR
 * SERVER PROFILES
 * JSON PREVIEW
 */
body.personality-page-body
.personality-page-shell
.panel > .kicker,

/*
 * Numbered editor sections:
 * 1. PROFILE FILE
 * ...
 * 8. ADDITIONAL FREE INSTRUCTIONS
 */
body.personality-page-body
.personality-page-shell
#profileForm > h3 {

    position: relative !important;
    isolation: isolate !important;

    display: block !important;
    width: max-content !important;
    max-width: 100% !important;

    box-sizing: border-box !important;

    margin-left: 0 !important;
    margin-right: 0 !important;

    padding: 3px 10px !important;

    overflow: hidden !important;

    background: #ffffff !important;
    color: #000000 !important;

    border: 1px solid #ffffff !important;
    border-radius: 0 !important;

    font-family: "Anta", "Segoe UI", Arial, sans-serif !important;
    font-weight: 400 !important;

    letter-spacing: .10em !important;
    line-height: 1 !important;

    text-transform: uppercase !important;

    transition:
        color 180ms ease,
        border-color 180ms ease !important;
}


/* Black layer expanding from center */

body.personality-page-body
.personality-page-shell
.panel > .kicker::before,

body.personality-page-body
.personality-page-shell
#profileForm > h3::before {

    content: "" !important;

    position: absolute !important;
    inset: 0 !important;

    z-index: -1 !important;

    background: #000000 !important;

    transform: scaleX(0) !important;
    transform-origin: center center !important;

    transition:
        transform 240ms cubic-bezier(.16, 1, .3, 1) !important;

    pointer-events: none !important;
}


/* Dynamic state */

body.personality-page-body
.personality-page-shell
.panel > .kicker:hover,

body.personality-page-body
.personality-page-shell
.panel > .kicker:focus-visible,

body.personality-page-body
.personality-page-shell
#profileForm > h3:hover,

body.personality-page-body
.personality-page-shell
#profileForm > h3:focus-visible {

    color: #ffffff !important;

    border-top-color: #ffffff !important;
    border-bottom-color: #ffffff !important;
    border-left-color: transparent !important;
    border-right-color: transparent !important;

    outline: none !important;
}


body.personality-page-body
.personality-page-shell
.panel > .kicker:hover::before,

body.personality-page-body
.personality-page-shell
.panel > .kicker:focus-visible::before,

body.personality-page-body
.personality-page-shell
#profileForm > h3:hover::before,

body.personality-page-body
.personality-page-shell
#profileForm > h3:focus-visible::before {

    transform: scaleX(1) !important;
}

</style>


<style id="personality-section-titles-bold-step252">

/* KUZAI PERSONALITY — ALL SECTION TITLES BOLD */

body.personality-page-body .personality-page-shell .panel > .kicker,
body.personality-page-body .personality-page-shell #profileForm > h3,
body.personality-page-body .personality-page-shell .panel > h2,
body.personality-page-body .personality-page-shell .panel > h3 {
    font-weight: 700 !important;
}

</style>


<style id="personality-containers-square-step253">

/* =========================================================
   KUZAI PERSONALITY
   CONTAINERS = WHITE BORDER / SQUARE CORNERS
   Controls and buttons intentionally untouched.
   ========================================================= */

/* Main page panels */
body.personality-page-body
.personality-page-shell
.panel {
    border: 1px solid #ffffff !important;
    border-radius: 0 !important;
}


/* Server profile cards */
body.personality-page-body
.personality-page-shell
#profileList .profile-card {
    border: 1px solid #ffffff !important;
    border-radius: 0 !important;
}


/* Generic profile containers if present */
body.personality-page-body
.personality-page-shell
.profile,
body.personality-page-body
.personality-page-shell
.profiles,
body.personality-page-body
.personality-page-shell
.profile-list,
body.personality-page-body
.personality-page-shell
.profile-grid {
    border-radius: 0 !important;
}


/* Form visual grouping containers only */
body.personality-page-body
.personality-page-shell
.form-section,
body.personality-page-body
.personality-page-shell
.form-group,
body.personality-page-body
.personality-page-shell
.field-group {
    border-color: #ffffff !important;
    border-radius: 0 !important;
}


/*
 * Explicit protection:
 * do NOT alter controls/buttons in this step.
 */
body.personality-page-body
.personality-page-shell
input,
body.personality-page-body
.personality-page-shell
select,
body.personality-page-body
.personality-page-shell
textarea,
body.personality-page-body
.personality-page-shell
button,
body.personality-page-body
.personality-page-shell
.btn,
body.personality-page-body
.personality-page-shell
.check-item {
    /* intentionally no border override */
}

</style>


<style id="personality-controls-square-step254">

/* =========================================================
   KUZAI PERSONALITY
   ALL CONTROLS / MENUS / BUTTONS = SQUARE CORNERS
   Functional behavior untouched.
   ========================================================= */

/* Text fields */
body.personality-page-body
.personality-page-shell
input[type="text"],

body.personality-page-body
.personality-page-shell
input[type="number"],

body.personality-page-body
.personality-page-shell
input[type="search"],

body.personality-page-body
.personality-page-shell
input[type="email"],

body.personality-page-body
.personality-page-shell
input[type="url"],

body.personality-page-body
.personality-page-shell
input[type="password"] {
    border-radius: 0 !important;
}


/* Menus */
body.personality-page-body
.personality-page-shell
select {
    border-radius: 0 !important;
}


/* Text areas */
body.personality-page-body
.personality-page-shell
textarea {
    border-radius: 0 !important;
}


/* Checkbox option containers */
body.personality-page-body
.personality-page-shell
.check-item {
    border-radius: 0 !important;
}


/* Native buttons */
body.personality-page-body
.personality-page-shell
button {
    border-radius: 0 !important;
}


/* KUZAI buttons / links */
body.personality-page-body
.personality-page-shell
.btn,
body.personality-page-body
.personality-page-shell
a.btn {
    border-radius: 0 !important;
}


/* Server profile delete buttons */
body.personality-page-body
.personality-page-shell
.server-profile-delete-btn,
body.personality-page-body
.personality-page-shell
.delete-profile-btn {
    border-radius: 0 !important;
}


/* Action toolbar */
body.personality-page-body
.personality-page-shell
.personality-action-toolbar
button,
body.personality-page-body
.personality-page-shell
.personality-action-toolbar
a {
    border-radius: 0 !important;
}


/* Back Home */
body.personality-page-body
.personality-page-shell
.personality-bottom-back-home {
    border-radius: 0 !important;
}


/* Checkbox itself */
body.personality-page-body
.personality-page-shell
input[type="checkbox"],
body.personality-page-body
.personality-page-shell
input[type="radio"] {
    border-radius: 0 !important;
}

</style>


<style id="personality-field-labels-step255">

/* =========================================================
   PERSONALITY FIELD LABELS
   WHITE / BOLD / STATIC
   Checkbox option labels intentionally excluded.
   ========================================================= */

body.personality-page-body
.personality-page-shell
#profileForm label:not(.check-item) {
    color: #ffffff !important;

    font-family: "Anta", "Segoe UI", Arial, sans-serif !important;
    font-weight: 700 !important;

    opacity: 1 !important;

    background: transparent !important;

    text-shadow: none !important;
    text-decoration: none !important;

    transition: none !important;
    animation: none !important;
    transform: none !important;
}

body.personality-page-body
.personality-page-shell
#profileForm label:not(.check-item):hover,
body.personality-page-body
.personality-page-shell
#profileForm label:not(.check-item):focus {
    color: #ffffff !important;
    background: transparent !important;
    transform: none !important;
}

</style>


<style id="personality-available-profiles-step258">

body.personality-page-body
.personality-page-shell
.personality-section-title {

    position: relative !important;
    isolation: isolate !important;

    display: inline-block !important;
    width: max-content !important;
    max-width: 100% !important;

    box-sizing: border-box !important;

    margin: 0 0 12px 0 !important;
    padding: 3px 10px !important;

    overflow: hidden !important;

    background: #ffffff !important;
    color: #000000 !important;

    border: 1px solid #ffffff !important;
    border-radius: 0 !important;

    font-family: "Anta", "Segoe UI", Arial, sans-serif !important;
    font-weight: 700 !important;

    letter-spacing: .10em !important;
    line-height: 1 !important;

    text-transform: uppercase !important;

    transition:
        color 180ms ease,
        border-color 180ms ease !important;
}

body.personality-page-body
.personality-page-shell
.personality-section-title::before {

    content: "" !important;

    position: absolute !important;
    inset: 0 !important;

    z-index: -1 !important;

    background: #000000 !important;

    transform: scaleX(0) !important;
    transform-origin: center center !important;

    transition:
        transform 240ms cubic-bezier(.16, 1, .3, 1) !important;

    pointer-events: none !important;
}

body.personality-page-body
.personality-page-shell
.personality-section-title:hover,
body.personality-page-body
.personality-page-shell
.personality-section-title:focus-visible {

    color: #ffffff !important;

    border-left-color: transparent !important;
    border-right-color: transparent !important;

    outline: none !important;
}

body.personality-page-body
.personality-page-shell
.personality-section-title:hover::before,
body.personality-page-body
.personality-page-shell
.personality-section-title:focus-visible::before {

    transform: scaleX(1) !important;
}

</style>


<style id="personality-delete-square-step259">
body.personality-page-body #profileList .server-profile-delete-btn,
body.personality-page-body #profileList .delete-profile-btn {
    border-radius: 0 !important;
    -webkit-border-radius: 0 !important;
    appearance: none !important;
}
</style>


<style id="personality-json-editor-step265">

body.personality-page-body
.personality-page-shell
#jsonPreview {
    cursor: text !important;
    user-select: text !important;
    -webkit-user-select: text !important;

    border-radius: 0 !important;

    outline: none !important;
}

body.personality-page-body
.personality-page-shell
#jsonPreview:focus {
    border-color: #ffffff !important;
    outline: none !important;
}

</style>


<style id="personality-json-header-step272">

body.personality-page-body
.personality-page-shell
#jsonPreviewPanel
.json-preview-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 20px !important;
    width: 100% !important;
    margin: 0 0 12px 0 !important;
}

body.personality-page-body
.personality-page-shell
#jsonPreviewPanel
.json-preview-header h2 {
    margin: 0 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}

body.personality-page-body
.personality-page-shell
#saveModifiedJsonBtn {
    margin-left: auto !important;
    flex: 0 0 auto !important;
    border-radius: 0 !important;
    white-space: nowrap !important;
}

</style>

</head>
<body class="personality-page-body">
<div class="page app-shell personality-page-shell">
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

    <div class="actions personality-action-toolbar" aria-label="Profile editor actions">
        <a class="btn" href="/KUZAI/">BACK TO CHAT</a>
        <a class="btn" href="/KUZAI/profiles.php">BACK TO PROFILES</a>
        <button type="button" class="btn" id="previewBtn">PREVIEW JSON</button>
        <button type="button" class="btn btn-primary" id="saveRunBtn">SAVE AND RUN PROFILE</button>
        <button type="button" class="btn btn-primary" id="saveBtn">SAVE PROFILE</button>
        <button type="button" class="btn" id="clearProfileBtn">CLEAR EDITION</button>
    </div>

    <main class="layout">
        <section class="panel">
            <p class="kicker">PROFILE EDITOR</p>
            <h2>CREATE OR EDIT A KUZAI LLM BEHAVIOR PROFILE</h2>

            <form id="profileForm">
                <h3>1. Profile file</h3>

                <div class="form-grid">
                    <div class="form-row">
                        <label for="profileId">Profile ID</label>
                        <input id="profileId" name="profileId" type="text" value="" placeholder="example-generalist">
                        <p class="help">Lowercase ID used for the JSON filename. Empty ID is generated from label.</p>
                    </div>

                    <div class="form-row">
                        <label for="label">Profile label</label>
                        <input id="label" name="label" type="text" value="KUZAI Generalist" required>
                    </div>

                    <div class="form-row form-row-full">
                        <label for="description">Description</label>
                        <textarea id="description" name="description">Generalist KUZAI profile. Technical when required, but not limited to sysadmin or code.</textarea>
                    </div>
                </div>

                <h3>2. Identity</h3>

                <div class="form-grid">
                    <div class="form-row">
                        <label for="identityName">AI name</label>
                        <input id="identityName" name="identityName" type="text" value="KUZAI">
                    </div>

                    <div class="form-row">
                        <label for="identityType">AI type</label>
                        <select id="identityType" name="identityType">
                            <option value="local generalist AI">Local generalist AI</option>
                            <option value="technical generalist AI">Technical generalist AI</option>
                            <option value="personal assistant AI">Personal assistant AI</option>
                            <option value="analysis and research AI">Analysis and research AI</option>
                            <option value="creative and technical AI">Creative and technical AI</option>
                        </select>
                    </div>

                    <div class="form-row form-row-full">
                        <label for="mainSentence">Main identity sentence</label>
                        <textarea id="mainSentence" name="mainSentence">KUZAI is a local generalist AI assistant designed for technical work, analysis, creation, research, writing, learning and practical decision support.</textarea>
                    </div>
                </div>

                <h3>3. Positioning</h3>

                <div class="check-grid" data-group="positioning">
                    <label class="check-item"><input type="checkbox" value="local generalist assistant" checked> Local generalist</label>
                    <label class="check-item"><input type="checkbox" value="technical assistant" checked> Technical assistant</label>
                    <label class="check-item"><input type="checkbox" value="personal assistant"> Personal assistant</label>
                    <label class="check-item"><input type="checkbox" value="creative assistant" checked> Creative assistant</label>
                    <label class="check-item"><input type="checkbox" value="research assistant" checked> Research assistant</label>
                    <label class="check-item"><input type="checkbox" value="analysis assistant" checked> Analysis assistant</label>
                    <label class="check-item"><input type="checkbox" value="learning assistant"> Learning assistant</label>
                    <label class="check-item"><input type="checkbox" value="professional writing assistant"> Writing assistant</label>
                    <label class="check-item"><input type="checkbox" value="strategy assistant"> Strategy assistant</label>
                </div>

                <h3>4. Priority domains</h3>

                <div class="check-grid" data-group="priorityDomains">
                    <label class="check-item"><input type="checkbox" value="general questions" checked> General questions</label>
                    <label class="check-item"><input type="checkbox" value="linux system administration" checked> Linux sysadmin</label>
                    <label class="check-item"><input type="checkbox" value="network administration" checked> Network</label>
                    <label class="check-item"><input type="checkbox" value="security analysis"> Security</label>
                    <label class="check-item"><input type="checkbox" value="web development" checked> Web development</label>
                    <label class="check-item"><input type="checkbox" value="python development"> Python</label>
                    <label class="check-item"><input type="checkbox" value="php development"> PHP</label>
                    <label class="check-item"><input type="checkbox" value="javascript development"> JavaScript</label>
                    <label class="check-item"><input type="checkbox" value="local AI and LLM" checked> Local AI / LLM</label>
                    <label class="check-item"><input type="checkbox" value="document analysis" checked> Document analysis</label>
                    <label class="check-item"><input type="checkbox" value="professional writing" checked> Professional writing</label>
                    <label class="check-item"><input type="checkbox" value="creative prompts" checked> Creative prompts</label>
                    <label class="check-item"><input type="checkbox" value="culture and general knowledge"> Culture générale</label>
                    <label class="check-item"><input type="checkbox" value="learning and pedagogy"> Learning</label>
                    <label class="check-item"><input type="checkbox" value="project strategy" checked> Project strategy</label>
                </div>

                <h3>5. Personality</h3>

                <div class="check-grid" data-group="traits">
                    <label class="check-item"><input type="checkbox" value="calm" checked> Calm</label>
                    <label class="check-item"><input type="checkbox" value="direct" checked> Direct</label>
                    <label class="check-item"><input type="checkbox" value="precise" checked> Precise</label>
                    <label class="check-item"><input type="checkbox" value="pragmatic" checked> Pragmatic</label>
                    <label class="check-item"><input type="checkbox" value="analytical" checked> Analytical</label>
                    <label class="check-item"><input type="checkbox" value="methodical" checked> Methodical</label>
                    <label class="check-item"><input type="checkbox" value="creative"> Creative</label>
                    <label class="check-item"><input type="checkbox" value="critical"> Critical</label>
                    <label class="check-item"><input type="checkbox" value="adaptive" checked> Adaptive</label>
                    <label class="check-item"><input type="checkbox" value="pedagogical"> Pedagogical</label>
                    <label class="check-item"><input type="checkbox" value="strategic"> Strategic</label>
                    <label class="check-item"><input type="checkbox" value="sober"> Sober</label>
                </div>

                <h3>6. Tone and behavior</h3>

                <div class="form-grid">
                    <div class="form-row">
                        <label for="initiativeLevel">Initiative level</label>
                        <select id="initiativeLevel">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="adaptive" selected>Adaptive</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="responseLength">Response length</label>
                        <select id="responseLength">
                            <option value="short">Short</option>
                            <option value="medium">Medium</option>
                            <option value="long when necessary">Long when necessary</option>
                            <option value="adaptive" selected>Adaptive</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="primaryLanguage">Primary language</label>
                        <select id="primaryLanguage">
                            <option value="user language" selected>User language</option>
                            <option value="French">French</option>
                            <option value="English">English</option>
                            <option value="French by default, English when requested">French default</option>
                            <option value="English by default, French when requested">English default</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="creativityLevel">Creativity level</label>
                        <select id="creativityLevel">
                            <option value="0">0 - None</option>
                            <option value="1">1 - Very sober</option>
                            <option value="2">2 - Moderate</option>
                            <option value="3" selected>3 - Balanced</option>
                            <option value="4">4 - Creative</option>
                            <option value="5">5 - Very creative</option>
                        </select>
                    </div>

                    <div class="form-row form-row-full">
                        <label for="styleExample">Style example</label>
                        <textarea id="styleExample">Precise, practical, structured, without filler. Technical when needed, generalist by default.</textarea>
                    </div>
                </div>

                <h3>7. Technical workflow</h3>

                <div class="form-grid">
                    <div class="form-row">
                        <label for="projectMode">Project mode</label>
                        <select id="projectMode">
                            <option value="step by step for installation, debugging and implementation" selected>Step by step for implementation</option>
                            <option value="direct complete solution">Direct complete solution</option>
                            <option value="always step by step">Always step by step</option>
                            <option value="adaptive according to risk and complexity">Adaptive</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="codeMode">Code generation mode</label>
                        <select id="codeMode">
                            <option value="complete files for corrections when practical, robust patches for large files" selected>Complete files or robust patches</option>
                            <option value="complete files only">Complete files only</option>
                            <option value="patches only">Patches only</option>
                            <option value="always backup before modifications">Backup first</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="explanationLevel">Explanation level</label>
                        <select id="explanationLevel">
                            <option value="minimal">Minimal</option>
                            <option value="normal" selected>Normal</option>
                            <option value="detailed">Detailed</option>
                            <option value="detailed only for complex errors">Detailed only for complex errors</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="voiceStyle">Voice / TTS style</label>
                        <select id="voiceStyle">
                            <option value="neutral">Neutral</option>
                            <option value="calm direct technical" selected>Calm direct technical</option>
                            <option value="narrative">Narrative</option>
                            <option value="personal assistant">Personal assistant</option>
                        </select>
                    </div>
                </div>

                <h3>8. Additional free instructions</h3>

                <div class="form-grid">
                    <div class="form-row form-row-full">
                        <label for="customInstructions">Custom instructions</label>
                        <textarea id="customInstructions" placeholder="Add specific behavioral rules here."></textarea>
                    </div>
                </div>
            </form>
        </section>

        <aside>
            <section class="panel" id="availableProfilesPanel">
<h2 class="personality-section-title">AVAILABLE PROFILES</h2>
                <div class="side-list" id="profileList"></div>
                <div class="status" id="statusBox">Ready.</div>
            </section>

            <section class="panel" id="jsonPreviewPanel">
                <p class="kicker">JSON PREVIEW</p>
                <div class="json-preview-header">
                    <h2>THE GENERATED PROFILE CAN BE MODIFIED HERE</h2>
                    <button type="button"
                            class="btn"
                            id="saveModifiedJsonBtn">
                        SAVE THE MODIFIED JSON
                    </button>
                </div>
                <textarea class="json-preview" id="jsonPreview" spellcheck="false"></textarea>
            </section>
        </aside>
        </main>

    <nav class="personality-bottom-nav" aria-label="Back to main chat">
        <a class="personality-bottom-back-home" href="/KUZAI/">
            <span class="personality-bottom-back-home__label">BACK HOME</span>
            <span class="personality-bottom-back-home__arrow">--&gt;</span>
        </a>
    </nav>

    <footer class="site-footer site-footer--dynamic app-site-footer" aria-label="KUZ Network footer">
        <div class="site-footer__inner">
            <p class="site-footer__text">THE KUZ NETWORK - @2026 / BUILD LOCAL / KEEP CONTROL / OWN THE STACK</p>
        </div>
    </footer>
</div>

<script>
'use strict';

const API_URL = 'api/personality.php';
const ACTIVE_ID_KEY = 'kuzai.customLlm.activeProfileId.session.v1';
const ACTIVE_LABEL_KEY = 'kuzai.customLlm.activeProfileLabel.session.v1';
const LOCKED_KEY = 'kuzai.customLlm.locked.session.v1';

const profileForm = document.getElementById('profileForm');
const saveBtn = document.getElementById('saveBtn');
const saveRunBtn = document.getElementById('saveRunBtn');
const clearProfileBtn = document.getElementById('clearProfileBtn');
const saveModifiedJsonBtn = document.getElementById('saveModifiedJsonBtn');
const previewBtn = document.getElementById('previewBtn');
const statusBox = document.getElementById('statusBox');
const jsonPreview = document.getElementById('jsonPreview');
const profileList = document.getElementById('profileList');

function scrollToAvailableProfiles() {
    window.setTimeout(() => {
        const panel = document.getElementById('availableProfilesPanel');

        if (!panel) {
            return;
        }

        const top =
            panel.getBoundingClientRect().top
            + window.scrollY
            - 24;

        window.scrollTo({
            top: Math.max(0, top),
            behavior: 'smooth'
        });
    }, 100);
}


function setStatus(message, type = '') {
    statusBox.textContent = message;
    statusBox.style.display = 'block';
    statusBox.classList.remove('status-ok', 'status-error');

    if (type === 'ok') {
        statusBox.classList.add('status-ok');
    }

    if (type === 'error') {
        statusBox.classList.add('status-error');
    }
}

function slugify(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9_-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 80);
}

function selectedValues(groupName) {
    const group = document.querySelector(`[data-group="${groupName}"]`);

    if (!group) {
        return [];
    }

    return Array.from(group.querySelectorAll('input[type="checkbox"]:checked'))
        .map((item) => item.value);
}

function textareaLines(value) {
    return String(value || '')
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '');
}

function buildProfileDocument() {
    const label = document.getElementById('label').value.trim();
    const explicitId = document.getElementById('profileId').value.trim();
    const profileId = slugify(explicitId || label);

    const customInstructions = textareaLines(document.getElementById('customInstructions').value);

    return {
        id: profileId,
        label: label,
        description: document.getElementById('description').value.trim(),
        locked: false,
        profile: {
            identity: {
                name: document.getElementById('identityName').value.trim() || 'KUZAI',
                type: document.getElementById('identityType').value,
                main_sentence: document.getElementById('mainSentence').value.trim(),
                positioning: selectedValues('positioning')
            },
            scope: {
                generalist: true,
                technical_only: false,
                priority_domains: selectedValues('priorityDomains'),
                avoided_domains: []
            },
            personality: {
                main_traits: selectedValues('traits'),
                avoided_traits: [
                    'overly talkative',
                    'overly commercial',
                    'overly familiar',
                    'overly specialized by default'
                ],
                initiative_level: document.getElementById('initiativeLevel').value,
                autonomy_level: 'propose better approaches when useful, but require validation before important modifications'
            },
            tone: {
                default_tone: [
                    'formal',
                    'neutral',
                    'direct'
                ],
                response_length: document.getElementById('responseLength').value,
                response_structure: [
                    'simple paragraphs',
                    'numbered steps for implementation',
                    'commands then validation for technical operations'
                ],
                primary_language: document.getElementById('primaryLanguage').value,
                style_example: document.getElementById('styleExample').value.trim()
            },
            technical_behavior: {
                project_mode: document.getElementById('projectMode').value,
                code_generation_mode: document.getElementById('codeMode').value,
                explanation_level: document.getElementById('explanationLevel').value
            },
            general_behavior: {
                supported_tasks: [
                    'general questions',
                    'summaries',
                    'rewriting',
                    'translation',
                    'brainstorming',
                    'critical analysis',
                    'planning',
                    'learning',
                    'professional communication',
                    'creative writing',
                    'technical assistance'
                ],
                visible_reasoning_mode: 'short method summary when useful, never hidden chain of thought',
                creativity_level: Number(document.getElementById('creativityLevel').value),
                custom_instructions: customInstructions
            },
            safety: {
                confidentiality_rules: [
                    'do not expose secrets',
                    'signal visible secrets in logs',
                    'recommend secret rotation when exposed',
                    'redact sensitive values when appropriate'
                ],
                uncertainty_rules: [
                    'state when information is missing',
                    'do not invent command outputs',
                    'separate facts, hypotheses and recommendations'
                ],
                refusal_style: 'short technical refusal with safe alternative'
            },
            web_and_files: {
                web_rules: [
                    'use web results only when provided by the application',
                    'cite source URLs when using web facts',
                    'state when results are weak or irrelevant'
                ],
                file_rules: [
                    'analyze only provided file content',
                    'state if content appears truncated',
                    'preserve exact paths and names',
                    'prioritize blocking errors'
                ]
            },
            voice: {
                tts_compatible: true,
                voice_style: document.getElementById('voiceStyle').value
            }
        }
    };
}


/* KUZAI_PROFILE_EDIT_LOAD_STEP246_BEGIN */

function setFieldValue(id, value) {
    const element = document.getElementById(id);

    if (!element) {
        return;
    }

    element.value = value === undefined || value === null
        ? ''
        : String(value);
}

function setCheckboxGroup(groupName, values) {
    const group = document.querySelector(`[data-group="${groupName}"]`);

    if (!group) {
        return;
    }

    const selected = Array.isArray(values)
        ? values.map((value) => String(value))
        : [];

    group.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.checked = selected.includes(String(checkbox.value));
    });
}

function populateProfileForm(documentData) {
    if (!documentData || typeof documentData !== 'object') {
        throw new Error('Invalid profile document');
    }

    const profile = documentData.profile || {};
    const identity = profile.identity || {};
    const scope = profile.scope || {};
    const personality = profile.personality || {};
    const tone = profile.tone || {};
    const technicalBehavior = profile.technical_behavior || {};
    const generalBehavior = profile.general_behavior || {};
    const voice = profile.voice || {};

    setFieldValue('profileId', documentData.id || '');
    setFieldValue('label', documentData.label || '');
    setFieldValue('description', documentData.description || '');

    setFieldValue('identityName', identity.name || 'KUZAI');
    setFieldValue('identityType', identity.type || '');
    setFieldValue('mainSentence', identity.main_sentence || '');

    setCheckboxGroup('positioning', identity.positioning || []);
    setCheckboxGroup('priorityDomains', scope.priority_domains || []);
    setCheckboxGroup('traits', personality.main_traits || []);

    setFieldValue('initiativeLevel', personality.initiative_level || '');
    setFieldValue('responseLength', tone.response_length || '');
    setFieldValue('primaryLanguage', tone.primary_language || '');
    setFieldValue('styleExample', tone.style_example || '');

    if (generalBehavior.creativity_level !== undefined) {
        setFieldValue('creativityLevel', generalBehavior.creativity_level);
    }

    setFieldValue('projectMode', technicalBehavior.project_mode || '');
    setFieldValue('codeMode', technicalBehavior.code_generation_mode || '');
    setFieldValue('explanationLevel', technicalBehavior.explanation_level || '');

    setFieldValue('voiceStyle', voice.voice_style || '');

    const customInstructions = Array.isArray(generalBehavior.custom_instructions)
        ? generalBehavior.custom_instructions.join('\n')
        : (generalBehavior.custom_instructions || '');

    setFieldValue('customInstructions', customInstructions);

    refreshPreview();
}

async function loadProfileForEditing(profileId) {
    profileId = String(profileId || '').trim();

    if (!profileId) {
        return;
    }

    setStatus(`Loading profile: ${profileId}...`);

    try {
        const response = await fetch(
            `${API_URL}?action=read&id=${encodeURIComponent(profileId)}`,
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            }
        );

        const data = await response.json();

        if (!response.ok || !data.ok || !data.profile_document) {
            throw new Error(data.error || 'Unable to read profile');
        }

        populateProfileForm(data.profile_document);

        setStatus(
            `Profile loaded for editing: ${data.profile?.label || profileId}`,
            'ok'
        );

    } catch (error) {
        setStatus(`Profile edit load error: ${error.message}`, 'error');
    }
}

function loadRequestedProfile() {
    const params = new URLSearchParams(window.location.search);
    const profileId = params.get('profile');

    if (!profileId) {
        return;
    }

    loadProfileForEditing(profileId);
}

/* KUZAI_PROFILE_EDIT_LOAD_STEP246_END */


/* KUZAI_PERSONALITY_CREATE_EMPTY_STEP250_BEGIN */

function resetProfileFormForCreate() {
    if (!profileForm) {
        return;
    }

    profileForm.querySelectorAll('input[type="text"], textarea').forEach((element) => {
        element.value = '';
    });

    profileForm.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((element) => {
        element.checked = false;
    });

    profileForm.querySelectorAll('select').forEach((element) => {
        element.selectedIndex = -1;
    });

    if (jsonPreview) {
        jsonPreview.value = '';
    }

    if (statusBox) {
        statusBox.textContent = '';
        statusBox.style.display = 'none';
        statusBox.classList.remove('status-ok', 'status-error');
    }
}

/* KUZAI_PERSONALITY_CREATE_EMPTY_STEP250_END */

function refreshPreview() {
    const documentData = buildProfileDocument();
    jsonPreview.value = JSON.stringify(documentData, null, 2);
    return documentData;
}


function getActiveProfileIdForDelete() {
    return sessionStorage.getItem(ACTIVE_ID_KEY) || '';
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

    if (sessionStorage.getItem(LOCKED_KEY) === '1' && getActiveProfileIdForDelete() === id) {
        setStatus('This profile is locked in the current discussion. Return to chat and use CLEAR before deleting it.', 'error');
        return;
    }

    const confirmed = window.confirm(`DELETE profile "${label}" ?\n\nThe JSON file will be moved to storage/personality_profiles/.deleted/`);

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

        if (getActiveProfileIdForDelete() === id) {
            sessionStorage.removeItem(ACTIVE_ID_KEY);
            sessionStorage.removeItem(ACTIVE_LABEL_KEY);
            sessionStorage.removeItem(LOCKED_KEY);
        }

        setStatus(`Profile deleted: ${label}`);
        await loadProfiles();

    } catch (error) {
        setStatus(`Delete error: ${error.message}`, 'error');
    }
}



async function kuzaiDeleteServerProfile(id, label) {
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

    if (sessionStorage.getItem(LOCKED_KEY) === '1' && sessionStorage.getItem(ACTIVE_ID_KEY) === id) {
        setStatus('This profile is locked in the current discussion. Return to chat and use CLEAR before deleting it.', 'error');
        return;
    }

    const confirmed = window.confirm(
        `DELETE profile "${label}" ?\n\nThe JSON file will be moved to storage/personality_profiles/.deleted/`
    );

    if (!confirmed) {
        return;
    }

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

        if (sessionStorage.getItem(ACTIVE_ID_KEY) === id) {
            sessionStorage.removeItem(ACTIVE_ID_KEY);
            sessionStorage.removeItem(ACTIVE_LABEL_KEY);
            sessionStorage.removeItem(LOCKED_KEY);
        }

        await loadProfiles();
    } catch (error) {
        setStatus(`Delete error: ${error.message}`, 'error');
    }
}


async function loadProfiles() {
    if (!profileList) {
        return;
    }

    profileList.innerHTML = '<p class="help">Loading profiles...</p>';

    try {
        const response = await fetch(`${API_URL}?action=list`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Unable to list profiles');
        }

        profileList.innerHTML = '';

        if (!Array.isArray(data.profiles) || data.profiles.length === 0) {
            profileList.innerHTML = '<p class="help">No profile found.</p>';
            return;
        }

        for (const profile of data.profiles) {
            const id = String(profile.id || '');
            const label = String(profile.label || id);
            const protectedProfile = id === 'default-generalist' || profile.protected === true;

            const card = document.createElement('div');
            card.className = 'profile-card';

            card.innerHTML = `
                <div class="profile-card-main">
                    <p class="profile-card-title">${escapeHtml(label)}</p>
                </div>
                <div class="profile-card-actions">
                    <button
                        type="button"
                        class="btn server-profile-delete-btn"
                        data-id="${escapeHtml(id)}"
                        data-label="${escapeHtml(label)}"
                        ${protectedProfile ? 'disabled title="Default profile is protected"' : 'title="Delete profile"'}
                    >
                        DELETE
                    </button>
                </div>
            `;

            profileList.appendChild(card);
        }

        kuzaiHideReadyStatus();
    } catch (error) {
        profileList.innerHTML = '';
        setStatus(`Profile load error: ${error.message}`, 'error');
    }
}


function runProfileAndReturn(id, label) {
    if (!id) {
        setStatus('Profile saved but profile ID is empty. Unable to run profile.', 'error');
        return;
    }

    sessionStorage.setItem(ACTIVE_ID_KEY, id);
    sessionStorage.setItem(ACTIVE_LABEL_KEY, label || id);
    sessionStorage.removeItem(LOCKED_KEY);

    setStatus(`Profile loaded: ${label || id}. Returning to chat...`, 'ok');

    window.setTimeout(() => {
        window.location.href = '/KUZAI/';
    }, 450);
}


/* KUZAI_JSON_EDITOR_SAVE_STEP265_BEGIN */

function getJsonDocumentForSave() {
    const raw = String(jsonPreview?.value || '').trim();

    /*
     * Empty JSON editor:
     * build from current form so existing workflow remains valid.
     */
    if (raw === '') {
        return refreshPreview();
    }

    let documentData;

    try {
        documentData = JSON.parse(raw);
    } catch (error) {
        throw new Error(`Invalid JSON: ${error.message}`);
    }

    if (
        !documentData ||
        typeof documentData !== 'object' ||
        Array.isArray(documentData)
    ) {
        throw new Error('JSON root must be an object.');
    }

    return documentData;
}

/* KUZAI_JSON_EDITOR_SAVE_STEP265_END */

async function saveProfile(runAfterSave = false) {
    let documentData;

    try {
        documentData = getJsonDocumentForSave();
    } catch (error) {
        setStatus(error.message, 'error');
        scrollToAvailableProfiles();
        return;
    }

    if (!documentData.id) {
        setStatus('Profile ID is empty. Fill the label or profile ID.', 'error');
        scrollToAvailableProfiles();
        return;
    }

    if (!documentData.label) {
        setStatus('Profile label is required.', 'error');
        scrollToAvailableProfiles();
        return;
    }

    setStatus('Saving profile...');

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'save',
                profile_document: documentData
            })
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Unable to save profile');
        }

        setStatus(`Profile saved: ${data.profile.file}`, 'ok');
        await loadProfiles();
        scrollToAvailableProfiles();

        if (runAfterSave) {
            runProfileAndReturn(data.profile.id, data.profile.label);
        }
    } catch (error) {
        setStatus(`Save error: ${error.message}`, 'error');
        scrollToAvailableProfiles();
    }
}

function escapeHtml(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

previewBtn.addEventListener('click', () => {
    refreshPreview();
    setStatus('JSON preview refreshed.', 'ok');

    const previewPanel = document.getElementById('jsonPreviewPanel');

    if (previewPanel) {
        previewPanel.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
});

saveBtn.addEventListener('click', () => {
    saveProfile(false);
});

saveModifiedJsonBtn.addEventListener('click', () => {
    saveProfile(false);
});


profileList.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLButtonElement)) {
        return;
    }

    if (!target.classList.contains('delete-profile-btn')) {
        return;
    }

    deleteProfile(target.dataset.id || '', target.dataset.label || target.dataset.id || '');
});


/* KUZAI_SERVER_PROFILE_DELETE_LISTENER_BEGIN */
if (profileList) {
    profileList.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLButtonElement)) {
            return;
        }

        if (!target.classList.contains('server-profile-delete-btn')) {
            return;
        }

        kuzaiDeleteServerProfile(
            target.dataset.id || '',
            target.dataset.label || target.dataset.id || ''
        );
    });
}
/* KUZAI_SERVER_PROFILE_DELETE_LISTENER_END */

saveRunBtn.addEventListener('click', () => {
    saveProfile(true);
});

clearProfileBtn.addEventListener('click', () => {
    resetProfileFormForCreate();

    const cleanUrl = new URL(window.location.href);
    cleanUrl.searchParams.delete('profile');

    window.history.replaceState(
        {},
        '',
        cleanUrl.pathname + cleanUrl.search + cleanUrl.hash
    );

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

profileForm.addEventListener('input', () => {
    refreshPreview();
});

loadProfiles();

const kuzaiRequestedProfileId =
    new URLSearchParams(window.location.search).get('profile');

if (kuzaiRequestedProfileId) {
    loadRequestedProfile();
} else {
    resetProfileFormForCreate();
}

function kuzaiHideReadyStatus() {
    document.querySelectorAll('.status').forEach((element) => {
        const value = String(element.textContent || '').trim().toLowerCase();

        if (value === 'ready' || value === 'ready.') {
            element.textContent = '';
            element.style.display = 'none';
            element.style.border = '0';
            element.style.padding = '0';
            element.style.margin = '0';
        }
    });
}

const kuzaiReadyStatusObserver = new MutationObserver(() => {
    kuzaiHideReadyStatus();
});

kuzaiReadyStatusObserver.observe(document.body, {
    childList: true,
    subtree: true,
    characterData: true,
});

kuzaiHideReadyStatus();

</script>
</body>
</html>
