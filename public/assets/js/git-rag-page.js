
/* KUZAI_GIT_RAG_PAGE_STATUS_BEGIN */
(function () {
    'use strict';

    const serverState = document.getElementById('serverState');

    if (!serverState) {
        return;
    }

    function setState(label) {
        serverState.innerHTML = '<span class="state-dot meta-dot"></span><span class="meta-value">' + label + '</span>';
    }

    fetch('api/status.php', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    }).then((response) => {
        if (!response.ok) {
            throw new Error('status failed');
        }

        setState('ONLINE');
    }).catch(() => {
        setState('OFFLINE');
    });
}());
/* KUZAI_GIT_RAG_PAGE_STATUS_END */

/* KUZAI_GIT_RAG_UI_BEGIN */
(function () {
    'use strict';

    const ACTIVE_REPO_KEY = 'kuzai.gitRag.activeRepo.v1';

    const toolbar = document.getElementById('gitRagToolbar');
    const gitRagBtn = document.getElementById('gitRagBtn');
    const repoMenu = document.getElementById('gitRagRepoMenu');
    const filesMenu = document.getElementById('gitRagFilesMenu');

    if (!toolbar || !gitRagBtn || !repoMenu || !filesMenu) {
        return;
    }

    let repos = [];
    let activeRepoId = '';
    let filesLoading = false;

    activeRepoId = loadActiveRepoId();

    function gitRagEscapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function gitRagFormatBytes(value) {
        const bytes = Number(value);

        if (!Number.isFinite(bytes) || bytes < 0) {
            return '-';
        }

        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
    }

    function gitRagFileExtension(file) {
        const ext = String(file && file.extension ? file.extension : '').trim();

        if (ext !== '') {
            return ext.toUpperCase();
        }

        return 'NO EXT';
    }

    function gitRagFileName(file) {
        const path = String(file && file.path ? file.path : '');
        return path.split('/').pop() || path || '-';
    }

    function gitRagFileDir(file) {
        const dir = String(file && file.dir ? file.dir : '').trim();
        return dir === '' ? './' : dir + '/';
    }

    function loadActiveRepoId() {
        try {
            return localStorage.getItem(ACTIVE_REPO_KEY) || '';
        } catch (error) {
            return '';
        }
    }

    function saveActiveRepoId(repoId) {
        activeRepoId = String(repoId || '');

        try {
            if (activeRepoId) {
                localStorage.setItem(ACTIVE_REPO_KEY, activeRepoId);
            } else {
                localStorage.removeItem(ACTIVE_REPO_KEY);
            }
        } catch (error) {
            return;
        }
    }

    function getActiveRepo() {
        return repos.find((repo) => repo && repo.id === activeRepoId) || null;
    }

    function hideMenus() {
        repoMenu.hidden = true;
        filesMenu.hidden = true;
    }

    function toggleRepoMenu() {
        filesMenu.hidden = true;
        repoMenu.hidden = !repoMenu.hidden;
    }

    function toggleFilesMenu() {
        repoMenu.hidden = true;

        if (filesMenu.hidden) {
            loadFilesMenu();
            return;
        }

        filesMenu.hidden = true;
    }

    function renderActiveRepoButton() {
        gitRagBtn.textContent = 'GIT-RAG / BACK TO REPOS LIST';
        gitRagBtn.title = 'Back to GIT-RAG repositories list';
        gitRagBtn.classList.add('git-rag-main-btn--selected');
        gitRagBtn.hidden = false;
        gitRagBtn.disabled = false;
    }

    function renderRepoMenu() {
        if (!repos.length) {
            repoMenu.innerHTML = '<div class="git-rag-menu-empty">No GIT-RAG repository configured</div>';
            gitRagBtn.hidden = true;
            return;
        }

        const rows = repos.map((repo) => {
            const selected = repo.id === activeRepoId;
            const statusLabel = repo.ready ? 'READY' : 'NOT READY';
            const branch = repo.branch || repo.configured_branch || '-';
            const commit = repo.commit_short || '-';

            return `
                <button type="button"
                    class="git-rag-menu-item ${selected ? 'git-rag-menu-item--selected' : ''}"
                    data-repo-id="${gitRagEscapeHtml(repo.id)}"
                    ${repo.ready ? '' : 'disabled'}>
                    <span class="git-rag-menu-item-main">${gitRagEscapeHtml(repo.name || repo.id)}</span>
                    <span class="git-rag-menu-item-meta">branch ${gitRagEscapeHtml(branch)} / ${gitRagEscapeHtml(commit)} / ${statusLabel}</span>
                </button>
            `;
        }).join('');

        repoMenu.innerHTML = `
            <div class="git-rag-menu-title">GIT-RAG REPOSITORIES</div>
            ${rows}
        `;

        if (!filesMenu || filesMenu.hidden) {
            gitRagBtn.hidden = true;
        }
    }

    function selectRepo(repoId) {
        const repo = repos.find((item) => item && item.id === repoId);

        if (!repo || !repo.ready) {
            return;
        }

        saveActiveRepoId(repo.id);
        renderRepoMenu();
        renderActiveRepoButton();

        repoMenu.hidden = true;
        filesMenu.hidden = true;
        gitRagBtn.hidden = false;
        loadFilesMenu();
    }

    async function fetchRepos() {
        gitRagBtn.disabled = true;

        try {
            const response = await fetch('api/git-rag-repos.php', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data || data.ok !== true || !Array.isArray(data.repos)) {
                throw new Error(data && data.error ? data.error : 'Invalid GIT-RAG repos response');
            }

            repos = data.repos;

            const activeExists = repos.some((repo) => repo.id === activeRepoId && repo.ready);

            if (!activeExists) {
                activeRepoId = '';
            }

            renderRepoMenu();
            gitRagBtn.hidden = true;
        } catch (error) {
            repos = [];
            saveActiveRepoId('');
            repoMenu.innerHTML = `<div class="git-rag-menu-empty">GIT-RAG error: ${gitRagEscapeHtml(error.message)}</div>`;
            gitRagBtn.textContent = 'GIT-RAG / REPOS LIST';
            gitRagBtn.title = 'GIT-RAG repository list unavailable';
        } finally {
            gitRagBtn.disabled = false;
        }
    }


    async function reindexActiveRepo() {
        const activeRepo = getActiveRepo();

        if (!activeRepo) {
            return;
        }

        const reindexBtn = document.getElementById('gitRagReindexBtn');
        const statusEl = document.getElementById('gitRagReindexStatus');

        if (reindexBtn) {
            reindexBtn.disabled = true;
            reindexBtn.textContent = 'INDEXING';
        }

        if (statusEl) {
            statusEl.textContent = 'Indexing repository with local llama.cpp embeddings...';
        }

        try {
            const response = await fetch('api/git-rag-index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    repo: activeRepo.id,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data || data.ok !== true) {
                throw new Error(data && data.error ? data.error : 'GIT-RAG index failed');
            }

            const manifest = data.manifest || {};
            const chunks = manifest.chunks_count || 0;
            const skipped = manifest.chunks_skipped || 0;
            const duration = manifest.duration_seconds || '-';

            if (statusEl) {
                statusEl.textContent = `Index OK / ${chunks} chunks / skipped ${skipped} / ${duration}s`;
            }

            await fetchRepos();
        } catch (error) {
            if (statusEl) {
                statusEl.textContent = `Index error: ${error.message}`;
            }
        } finally {
            if (reindexBtn) {
                reindexBtn.disabled = false;
                reindexBtn.textContent = 'REINDEX';
            }
        }
    }


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

    function gitRagSetGitButtonsDisabled(disabled) {
        filesMenu.querySelectorAll('[data-git-rag-action]').forEach((button) => {
            button.disabled = disabled;
        });
    }

    function gitRagShowGitResult(statusText, data, isError) {
        const statusEl = document.getElementById('gitRagGitStatus');
        const outputEl = document.getElementById('gitRagGitOutput');

        if (statusEl) {
            statusEl.textContent = statusText;
        }

        if (outputEl) {
            outputEl.hidden = false;
            outputEl.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
            outputEl.classList.toggle('git-rag-git-output--error', Boolean(isError));
        }
    }

    async function runGitRepoAction(action) {
        const activeRepo = getActiveRepo();

        if (!activeRepo) {
            return;
        }

        const repoPayload = {
            repo: activeRepo.id,
        };

        let endpoint = '';
        let payload = repoPayload;
        let label = String(action || '').toUpperCase();

        if (action === 'status') {
            endpoint = 'api/git-rag-git-status.php';
        } else if (action === 'diff') {
            endpoint = 'api/git-rag-git-diff.php';
        } else if (action === 'pull') {
            endpoint = 'api/git-rag-git-pull.php';
            if (!window.confirm('Run git pull --ff-only on active repository?')) {
                return;
            }
        } else if (action === 'push') {
            endpoint = 'api/git-rag-git-push.php';
            if (!window.confirm('Run git push on active repository?')) {
                return;
            }
        } else if (action === 'commit') {
            endpoint = 'api/git-rag-git-commit.php';
            const message = window.prompt('Commit message:');

            if (!message || !message.trim()) {
                return;
            }

            payload = {
                repo: activeRepo.id,
                message: message.trim(),
            };
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


    // KUZAI_GIT_RAG_FILE_EDIT_UI_STEP1
    async function saveRepoFile(filePath, content) {
        const activeRepo = getActiveRepo();

        if (!activeRepo || !filePath) {
            return null;
        }

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

        if (viewer) {
            viewer.hidden = enabled;
        }

        if (editor) {
            editor.hidden = !enabled;

            if (enabled) {
                editor.focus();
            }
        }

        if (editBtn) {
            editBtn.hidden = enabled;
        }

        if (saveBtn) {
            saveBtn.hidden = !enabled;
        }

        if (cancelBtn) {
            cancelBtn.hidden = !enabled;
        }
    }


    async function loadRepoFile(filePath) {
        const activeRepo = getActiveRepo();

        if (!activeRepo || !filePath) {
            return;
        }

        filesMenu.hidden = false;
        filesMenu.innerHTML = `
            <div class="git-rag-files-header">
                <div>
                    <div class="git-rag-menu-title">FILE / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
                    <div class="git-rag-menu-subtitle">
                        <span>${gitRagEscapeHtml(filePath)}</span>
                    </div>
                </div>
                <div class="git-rag-files-actions">
                    <button type="button" class="git-rag-reindex-btn" id="gitRagFileBackBtn">FILES</button>
                    <button type="button" class="git-rag-menu-close" id="gitRagFileCloseBtn">×</button>
                </div>
            </div>
            <div class="git-rag-menu-empty">Loading file...</div>
        `;

        try {
            const response = await fetch('api/git-rag-file-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    repo: activeRepo.id,
                    path: filePath,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data || data.ok !== true) {
                throw new Error(data && data.error ? data.error : 'GIT-RAG file read failed');
            }

            filesMenu.innerHTML = `
                <div class="git-rag-files-header">
                    <div>
                        <div class="git-rag-menu-title">FILE / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
                        <div class="git-rag-menu-subtitle">
                            <span>${gitRagEscapeHtml(data.path || filePath)}</span>
                            <span>${gitRagEscapeHtml(gitRagFormatBytes(data.size_bytes || 0))}</span>
                        </div>
                    </div>
                    <div class="git-rag-files-actions">
                        <button type="button" class="git-rag-reindex-btn" id="gitRagFileBackBtn">FILES</button>
                        <button type="button" class="git-rag-reindex-btn" id="gitRagFileEditBtn">EDIT</button>
                        <button type="button" class="git-rag-reindex-btn" id="gitRagFileSaveBtn" hidden>SAVE</button>
                        <button type="button" class="git-rag-reindex-btn" id="gitRagFileCancelBtn" hidden>CANCEL</button>
                        <button type="button" class="git-rag-menu-close" id="gitRagFileCloseBtn">×</button>
                    </div>
                </div>
                <div class="git-rag-reindex-status" id="gitRagFileEditStatus">Read only</div>
                <pre class="git-rag-file-viewer" id="gitRagFileViewer">${gitRagEscapeHtml(data.content || '')}</pre>
                <textarea class="git-rag-file-editor" id="gitRagFileEditor" spellcheck="false" hidden>${gitRagEscapeHtml(data.content || '')}</textarea>
            `;

            const backBtn = document.getElementById('gitRagFileBackBtn');
            const closeBtn = document.getElementById('gitRagFileCloseBtn');
            const editBtn = document.getElementById('gitRagFileEditBtn');
            const saveBtn = document.getElementById('gitRagFileSaveBtn');
            const cancelBtn = document.getElementById('gitRagFileCancelBtn');
            const editor = document.getElementById('gitRagFileEditor');
            const viewer = document.getElementById('gitRagFileViewer');
            const editStatus = document.getElementById('gitRagFileEditStatus');

            if (backBtn) {
                backBtn.addEventListener('click', loadFilesMenu);
            }

            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    setRepoFileEditMode(true);

                    if (editStatus) {
                        editStatus.textContent = 'Edit mode';
                    }
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    if (editor && viewer) {
                        editor.value = viewer.textContent || '';
                    }

                    setRepoFileEditMode(false);

                    if (editStatus) {
                        editStatus.textContent = 'Read only';
                    }
                });
            }

            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    if (!editor || !viewer) {
                        return;
                    }

                    if (!window.confirm('Save changes to this repository file? A backup will be created server-side.')) {
                        return;
                    }

                    saveBtn.disabled = true;

                    if (editStatus) {
                        editStatus.textContent = 'Saving file...';
                    }

                    try {
                        const saved = await saveRepoFile(data.path || filePath, editor.value);
                        viewer.textContent = editor.value;
                        setRepoFileEditMode(false);

                        if (editStatus) {
                            const backupPath = saved && saved.backup_path ? ` / backup ${saved.backup_path}` : '';
                            editStatus.textContent = `Saved / ${saved.size_bytes || 0} bytes${backupPath}`;
                        }
                    } catch (error) {
                        if (editStatus) {
                            editStatus.textContent = `Save error: ${error.message}`;
                        }
                    } finally {
                        saveBtn.disabled = false;
                    }
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    filesMenu.hidden = true;
                });
            }
        } catch (error) {
            filesMenu.innerHTML = `
                <div class="git-rag-files-header">
                    <div>
                        <div class="git-rag-menu-title">FILE / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
                        <div class="git-rag-menu-subtitle">
                            <span>${gitRagEscapeHtml(filePath)}</span>
                        </div>
                    </div>
                    <div class="git-rag-files-actions">
                        <button type="button" class="git-rag-reindex-btn" id="gitRagFileBackBtn">FILES</button>
                        <button type="button" class="git-rag-menu-close" id="gitRagFileCloseBtn">×</button>
                    </div>
                </div>
                <div class="git-rag-menu-empty">GIT-RAG file error: ${gitRagEscapeHtml(error.message)}</div>
            `;

            const backBtn = document.getElementById('gitRagFileBackBtn');
            const closeBtn = document.getElementById('gitRagFileCloseBtn');

            if (backBtn) {
                backBtn.addEventListener('click', loadFilesMenu);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    filesMenu.hidden = true;
                });
            }
        }
    }

    async function loadFilesMenu() {
        const activeRepo = getActiveRepo();

        if (!activeRepo) {
            filesMenu.innerHTML = '<div class="git-rag-menu-empty">No active repository</div>';
            filesMenu.hidden = false;
            return;
        }

        if (filesLoading) {
            return;
        }

        filesLoading = true;
        filesMenu.hidden = false;
        filesMenu.innerHTML = `
            <div class="git-rag-menu-title">FILES / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
            <div class="git-rag-menu-empty">Loading files...</div>
        `;

        try {
            const url = `api/git-rag-files.php?repo=${encodeURIComponent(activeRepo.id)}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok || !data || data.ok !== true || !Array.isArray(data.files)) {
                throw new Error(data && data.error ? data.error : 'Invalid GIT-RAG files response');
            }

            const summary = data.summary || {};
            const files = data.files;

            const rows = files.map((file) => {
                const filePath = String(file.path || '');
                const fileName = gitRagFileName(file);
                const fileDir = gitRagFileDir(file);
                const fileSize = gitRagFormatBytes(file.size_bytes);
                const fileExt = gitRagFileExtension(file);
                const binaryBadge = file.likely_binary
                    ? '<span class="git-rag-file-badge git-rag-file-badge--binary">BINARY</span>'
                    : '<span class="git-rag-file-badge">TEXT</span>';

                return `
                    <button type="button"
                        class="git-rag-file-item"
                        data-file-path="${gitRagEscapeHtml(filePath)}"
                        title="${gitRagEscapeHtml(filePath)}">
                        <span class="git-rag-file-main">
                            <span class="git-rag-file-name">${gitRagEscapeHtml(fileName)}</span>
                            <span class="git-rag-file-dir">${gitRagEscapeHtml(fileDir)}</span>
                        </span>
                        <span class="git-rag-file-info">
                            <span class="git-rag-file-badge">${gitRagEscapeHtml(fileExt)}</span>
                            <span class="git-rag-file-badge">${gitRagEscapeHtml(fileSize)}</span>
                            ${binaryBadge}
                        </span>
                    </button>
                `;
            }).join('');

            filesMenu.innerHTML = `
                <div class="git-rag-files-header">
                    <div>
                        <div class="git-rag-menu-title">FILES / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
                    </div>
                    <div class="git-rag-files-actions">
                        <button type="button" class="git-rag-reindex-btn" data-git-rag-action="status">STATUS</button>
                        <button type="button" class="git-rag-reindex-btn" data-git-rag-action="diff">DIFF</button>
                        <button type="button" class="git-rag-reindex-btn" data-git-rag-action="pull">PULL</button>
                        <button type="button" class="git-rag-reindex-btn" data-git-rag-action="commit">COMMIT</button>
                        <button type="button" class="git-rag-reindex-btn" data-git-rag-action="push">PUSH</button>
                        <button type="button" class="git-rag-reindex-btn" id="gitRagReindexBtn">REINDEX</button>
                        <button type="button" class="git-rag-menu-close" id="gitRagFilesCloseBtn">×</button>
                    </div>
                </div>

                <div class="git-rag-page-meta-row">
                    <span class="git-rag-page-meta-pill">${gitRagEscapeHtml(String(summary.files_count || files.length))} FILES</span>
                    <span class="git-rag-page-meta-pill">BRANCH ${gitRagEscapeHtml(String(data.repo && data.repo.branch ? data.repo.branch : '-').toUpperCase())}</span>
                    <span class="git-rag-page-meta-pill">${gitRagEscapeHtml(gitRagFormatBytes(summary.total_bytes || 0)).toUpperCase()}</span>
                    <span class="git-rag-page-meta-pill git-rag-reindex-status" id="gitRagReindexStatus">INDEX READY</span>
                    <span class="git-rag-page-meta-pill git-rag-reindex-status" id="gitRagGitStatus">GIT READY</span>
                </div>

                <pre class="git-rag-file-viewer git-rag-git-output" id="gitRagGitOutput" hidden></pre>
                <div class="git-rag-files-list">
                    ${rows || '<div class="git-rag-menu-empty">No file found</div>'}
                </div>
            `;

            const reindexBtn = document.getElementById('gitRagReindexBtn');
            const closeBtn = document.getElementById('gitRagFilesCloseBtn');
            const gitActionBtns = filesMenu.querySelectorAll('[data-git-rag-action]');

            if (reindexBtn) {
                reindexBtn.addEventListener('click', reindexActiveRepo);
            }

            gitActionBtns.forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    runGitRepoAction(button.getAttribute('data-git-rag-action') || '');
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    filesMenu.hidden = true;
                });
            }
        } catch (error) {
            filesMenu.innerHTML = `
                <div class="git-rag-menu-title">FILES / ${gitRagEscapeHtml(activeRepo.name || activeRepo.id)}</div>
                <div class="git-rag-menu-empty">GIT-RAG files error: ${gitRagEscapeHtml(error.message)}</div>
            `;
        } finally {
            filesLoading = false;
        }
    }

    gitRagBtn.addEventListener('click', (event) => {
        event.stopPropagation();

        filesMenu.hidden = true;
        repoMenu.hidden = false;
        gitRagBtn.hidden = true;
        renderRepoMenu();
        gitRagBtn.hidden = true;
    });

    repoMenu.addEventListener('click', (event) => {
        const target = event.target.closest('[data-repo-id]');

        if (!target) {
            return;
        }

        selectRepo(target.getAttribute('data-repo-id') || '');
    });

    filesMenu.addEventListener('click', (event) => {
        event.stopPropagation();

        const target = event.target.closest('[data-file-path]');

        if (!target) {
            return;
        }

        const filePath = target.getAttribute('data-file-path') || '';

        if (filePath) {
            loadRepoFile(filePath);
        }
    });

    repoMenu.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    document.addEventListener('click', (event) => {
        if (!toolbar.contains(event.target)) {
            return;
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideMenus();
        }
    });

    window.KUZAI_GIT_RAG = {
        getActiveRepoId: function () {
            return activeRepoId || '';
        },
        getActiveRepoName: function () {
            const repo = getActiveRepo();
            return repo ? (repo.name || repo.id || '') : '';
        },
        hasActiveRepo: function () {
            return Boolean(activeRepoId);
        },
    };

    fetchRepos().then(() => {
        filesMenu.hidden = true;
        repoMenu.hidden = false;
        gitRagBtn.hidden = true;
        renderRepoMenu();
    }).catch(() => {
        filesMenu.hidden = true;
        repoMenu.hidden = false;
        gitRagBtn.hidden = true;
    });
}());
/* KUZAI_GIT_RAG_UI_END */

