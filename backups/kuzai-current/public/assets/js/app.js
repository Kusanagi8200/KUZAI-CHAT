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
const composerActions = document.querySelector('.composer-actions');

const STORAGE_KEY = 'kuzai.history.v1';
const AUTO_SPEAK_KEY = 'kuzai.autoSpeak.v1';
const AUTO_WEB_KEY = 'kuzai.autoWeb.v1';

let history = loadHistory();
let busy = false;
let uploading = false;
let searching = false;
let currentController = null;
let currentPendingMessage = null;
let attachments = [];
let webResults = [];

let activeAudio = null;
let activeAudioButton = null;
let activeTtsController = null;
let autoSpeakEnabled = loadAutoSpeakState();
let autoSpeakBtn = null;
let autoWebEnabled = loadAutoWebState();
let autoWebBtn = null;

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

function loadAutoSpeakState() {
    try {
        return localStorage.getItem(AUTO_SPEAK_KEY) === '1';
    } catch (error) {
        return false;
    }
}

function saveAutoSpeakState() {
    try {
        localStorage.setItem(AUTO_SPEAK_KEY, autoSpeakEnabled ? '1' : '0');
    } catch (error) {
        return;
    }
}

function updateAutoSpeakButton() {
    if (!autoSpeakBtn) {
        return;
    }

    autoSpeakBtn.textContent = autoSpeakEnabled ? 'VOICE ON' : 'VOICE OFF';
    autoSpeakBtn.classList.toggle('btn-primary', autoSpeakEnabled);
    autoSpeakBtn.classList.toggle('btn-secondary', !autoSpeakEnabled);
}

function createAutoSpeakButton() {
    if (!composerActions) {
        return;
    }

    autoSpeakBtn = document.createElement('button');
    autoSpeakBtn.type = 'button';
    autoSpeakBtn.id = 'autoSpeakBtn';
    autoSpeakBtn.style.minWidth = '110px';
    autoSpeakBtn.style.whiteSpace = 'nowrap';
    autoSpeakBtn.style.paddingLeft = '10px';
    autoSpeakBtn.style.paddingRight = '10px';
    autoSpeakBtn.className = 'btn';
    autoSpeakBtn.setAttribute('aria-label', 'Toggle voice playback');

    autoSpeakBtn.addEventListener('click', () => {
        autoSpeakEnabled = !autoSpeakEnabled;
        saveAutoSpeakState();
        updateAutoSpeakButton();

        if (!autoSpeakEnabled) {
            stopAudioPlayback();
        }
    });

    composerActions.insertBefore(autoSpeakBtn, sendBtn);
    updateAutoSpeakButton();
}


function loadAutoWebState() {
    try {
        return localStorage.getItem(AUTO_WEB_KEY) === '1';
    } catch (error) {
        return false;
    }
}

function saveAutoWebState() {
    try {
        localStorage.setItem(AUTO_WEB_KEY, autoWebEnabled ? '1' : '0');
    } catch (error) {
        return;
    }
}

function updateAutoWebButton() {
    if (!autoWebBtn) {
        return;
    }

    autoWebBtn.textContent = autoWebEnabled ? 'WEB ON' : 'WEB OFF';
    autoWebBtn.classList.toggle('btn-primary', autoWebEnabled);
    autoWebBtn.classList.toggle('btn-secondary', !autoWebEnabled);
}

function createAutoWebButton() {
    if (!composerActions) {
        return;
    }

    autoWebBtn = document.createElement('button');
    autoWebBtn.type = 'button';
    autoWebBtn.id = 'autoWebBtn';
    autoWebBtn.className = 'btn';
    autoWebBtn.style.minWidth = '92px';
    autoWebBtn.style.whiteSpace = 'nowrap';
    autoWebBtn.style.paddingLeft = '10px';
    autoWebBtn.style.paddingRight = '10px';
    autoWebBtn.setAttribute('aria-label', 'Toggle web search');

    autoWebBtn.addEventListener('click', () => {
        autoWebEnabled = !autoWebEnabled;
        saveAutoWebState();
        updateAutoWebButton();
    });

    if (webBtn && webBtn.parentNode === composerActions) {
        composerActions.insertBefore(autoWebBtn, webBtn);
    } else {
        composerActions.insertBefore(autoWebBtn, composerActions.firstChild);
    }

    updateAutoWebButton();
}


function setupComposerButtonsLayout() {
    if (!composerActions) {
        return;
    }

    if (webBtn) {
        webBtn.classList.add('btn-hidden');
        webBtn.disabled = true;
        webBtn.setAttribute('aria-hidden', 'true');
        webBtn.tabIndex = -1;
    }

    if (autoWebBtn) {
        autoWebBtn.style.minWidth = '96px';
        autoWebBtn.style.whiteSpace = 'nowrap';
    }

    if (autoSpeakBtn) {
        autoSpeakBtn.style.minWidth = '118px';
        autoSpeakBtn.style.whiteSpace = 'nowrap';
    }

    const orderedButtons = [
        autoWebBtn,
        autoSpeakBtn,
        uploadBtn,
        sendBtn,
        stopBtn,
    ];

    for (const btn of orderedButtons) {
        if (btn && btn.parentNode === composerActions) {
            composerActions.appendChild(btn);
        }
    }

    if (stopBtn && !busy) {
        stopBtn.classList.add('btn-hidden');
    }
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

function isSpeakableAssistantContent(content) {
    const value = String(content || '').trim();

    if (value === '') {
        return false;
    }

    if (value === 'Generating...') {
        return false;
    }

    if (value.startsWith('Error:')) {
        return false;
    }

    if (value.startsWith('TTS error:')) {
        return false;
    }

    if (value.startsWith('[Generation stopped')) {
        return false;
    }

    return true;
}

function stopAudioPlayback() {
    if (activeTtsController) {
        activeTtsController.abort();
        activeTtsController = null;
    }

    if (activeAudio) {
        activeAudio.pause();
        activeAudio.currentTime = 0;
        activeAudio = null;
    }

    if (activeAudioButton) {
        activeAudioButton.textContent = 'SPEAK';
        activeAudioButton.disabled = false;
        activeAudioButton = null;
    }
}

async function requestTtsAudio(text) {
    activeTtsController = new AbortController();

    const response = await fetch('api/tts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        signal: activeTtsController.signal,
        body: JSON.stringify({
            personality_profile_id: (
                window.KUZAI_CUSTOM_LLM
                && typeof window.KUZAI_CUSTOM_LLM.getActiveProfileId === 'function'
            ) ? window.KUZAI_CUSTOM_LLM.getActiveProfileId() : '',
            text,
            engine: 'piper',
            voice: 'en_US-lessac-high',
            speed: 155,
        }),
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
        const error = data && data.error ? data.error : 'TTS API error';
        throw new Error(error);
    }

    if (!data.audio || typeof data.audio.url !== 'string') {
        throw new Error('Invalid TTS response');
    }

    return data.audio.url;
}

async function speakText(text, button = null) {
    const clean = String(text || '').trim();

    if (!clean) {
        return;
    }

    stopAudioPlayback();

    activeAudioButton = button;

    if (activeAudioButton) {
        activeAudioButton.disabled = true;
        activeAudioButton.textContent = 'LOADING...';
    }

    try {
        const audioUrl = await requestTtsAudio(clean);
        activeTtsController = null;

        const separator = audioUrl.includes('?') ? '&' : '?';
        const finalAudioUrl = `${audioUrl}${separator}ts=${Date.now()}`;

        activeAudio = new Audio(finalAudioUrl);

        activeAudio.addEventListener('ended', () => {
            if (activeAudioButton) {
                activeAudioButton.textContent = 'SPEAK';
                activeAudioButton.disabled = false;
            }

            activeAudio = null;
            activeAudioButton = null;
        });

        activeAudio.addEventListener('error', () => {
            if (activeAudioButton) {
                activeAudioButton.textContent = 'SPEAK';
                activeAudioButton.disabled = false;
            }

            activeAudio = null;
            activeAudioButton = null;
        });

        if (activeAudioButton) {
            activeAudioButton.textContent = 'PLAYING...';
            activeAudioButton.disabled = false;
        }

        await activeAudio.play();
    } catch (error) {
        if (error.name !== 'AbortError') {
            appendMessage('assistant', `TTS error: ${error.message}`, false);
        }

        if (activeAudioButton) {
            activeAudioButton.textContent = 'SPEAK';
            activeAudioButton.disabled = false;
        }

        activeTtsController = null;
        activeAudio = null;
        activeAudioButton = null;
    }
}

function autoSpeakAnswer(content) {
    if (!autoSpeakEnabled) {
        return;
    }

    if (!isSpeakableAssistantContent(content)) {
        return;
    }

    speakText(content, null);
}

function refreshAudioControls(wrapper, role, content) {
    if (!wrapper) {
        return;
    }

    const existing = wrapper.querySelector('.message-audio-actions');

    if (existing) {
        existing.remove();
    }

    if (role !== 'assistant') {
        return;
    }

    if (!isSpeakableAssistantContent(content)) {
        return;
    }

    const contentEl = wrapper.querySelector('.message-content');

    if (!contentEl) {
        return;
    }

    const actions = document.createElement('div');
    actions.className = 'message-audio-actions composer-actions';
    actions.style.marginTop = '10px';
    actions.style.justifyContent = 'flex-start';

    const speakBtn = document.createElement('button');
    speakBtn.type = 'button';
    speakBtn.className = 'btn btn-secondary';
    speakBtn.textContent = 'SPEAK';
    speakBtn.setAttribute('aria-label', 'Speak this answer');

    const stopAudioBtn = document.createElement('button');
    stopAudioBtn.type = 'button';
    stopAudioBtn.className = 'btn btn-danger';
    stopAudioBtn.textContent = 'STOP AUDIO';
    stopAudioBtn.setAttribute('aria-label', 'Stop audio playback');

    speakBtn.addEventListener('click', () => {
        speakText(content, speakBtn);
    });

    stopAudioBtn.addEventListener('click', () => {
        stopAudioPlayback();
    });

    actions.appendChild(speakBtn);
    actions.appendChild(stopAudioBtn);

    contentEl.insertAdjacentElement('afterend', actions);
}

function appendMessage(role, content, persist = true) {
    const wrapper = document.createElement('div');
    wrapper.className = `message message-${role}`;
    wrapper.dataset.role = role;
    wrapper.dataset.content = content;

    const roleLabel = role === 'user' ? 'USER' : 'KUZAI';

    wrapper.innerHTML = `
        <div class="message-role">${roleLabel}</div>
        <div class="message-content">${renderContent(content)}</div>
    `;

    refreshAudioControls(wrapper, role, content);

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

    const role = wrapper.dataset.role || 'assistant';
    wrapper.dataset.content = content;

    refreshAudioControls(wrapper, role, content);
}

function renderInitialHistory() {
    messagesEl.innerHTML = '';

    if (history.length === 0) {
        return;
    }

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

    if (autoSpeakBtn) {
        autoSpeakBtn.disabled = false;
    }

    if (autoWebBtn) {
        autoWebBtn.disabled = false;
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

/* KUZAI_CUSTOM_LLM_SEND_PROFILE_BEGIN */
async function sendMessage(message, signal) {
    const payloadHistory = history.slice(-20);

    const response = await fetch('api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        signal,
        body: JSON.stringify({
            
            personality_profile_id: sessionStorage.getItem('kuzai.customLlm.activeProfileId.session.v1') || '',message,
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
/* KUZAI_CUSTOM_LLM_SEND_PROFILE_END */

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

    stopAudioPlayback();

    const finalMessage = message || 'Analyze the attached context.';


    if (autoWebEnabled && message && webResults.length === 0) {
        setSearching(true);

        try {
            const results = await searchWeb(message);

            if (results.length > 0) {
                webResults = results;
                renderWebResults();
                setServerState(true, 'ONLINE');
            }
        } catch (error) {
            setServerState(false, 'ERROR');
        } finally {
            setSearching(false);
        }
    }

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
        autoSpeakAnswer(answer);
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

    if (event.key === 'Escape' && !busy) {
        stopAudioPlayback();
    }
});

clearBtn.addEventListener('click', () => {
    if (busy) {
        stopCurrentGeneration();
    }

    stopAudioPlayback();

    history = [];
    attachments = [];
    webResults = [];

    localStorage.removeItem(STORAGE_KEY);

    messagesEl.innerHTML = '';
renderAttachments();
    renderWebResults();
    promptInput.focus();
});

createAutoWebButton();
createAutoSpeakButton();
setupComposerButtonsLayout();
renderInitialHistory();
renderAttachments();
renderWebResults();
checkServer();


/* KUZAI_CUSTOM_LLM_PAGE_MODULE_BEGIN */
(function () {
    'use strict';

    const ACTIVE_ID_KEY = 'kuzai.customLlm.activeProfileId.session.v1';
    const ACTIVE_LABEL_KEY = 'kuzai.customLlm.activeProfileLabel.session.v1';
    const LOCKED_KEY = 'kuzai.customLlm.locked.session.v1';

    function getActiveProfileId() {
        return sessionStorage.getItem(ACTIVE_ID_KEY) || '';
    }

    function getActiveProfileLabel() {
        return sessionStorage.getItem(ACTIVE_LABEL_KEY) || '';
    }

    function isLocked() {
        return sessionStorage.getItem(LOCKED_KEY) === '1';
    }

    function setLocked(value) {
        if (value) {
            sessionStorage.setItem(LOCKED_KEY, '1');
        } else {
            sessionStorage.removeItem(LOCKED_KEY);
        }

        updateButtonState();
    }

    function updateButtonState() {
        const customBtn = document.getElementById('customLlmBtn');

        if (!customBtn) {
            return;
        }

        const id = getActiveProfileId();
        const label = getActiveProfileLabel() || id;

        customBtn.classList.toggle('custom-llm-btn--selected', Boolean(id));
        customBtn.classList.toggle('custom-llm-btn--locked', isLocked());

        if (!id) {
            customBtn.textContent = 'CUSTOM LLM';
            customBtn.title = 'No active profile. Select a KUZAI behavior profile.';
            return;
        }

        customBtn.textContent = isLocked() ? 'CUSTOM LLM LOCKED' : 'CUSTOM LLM';
        customBtn.title = `Active profile: ${label}${isLocked() ? ' / locked during discussion' : ''}`;
    }

    function openProfilePage() {
        window.location.href = '/KUZAI/profiles.php';
    }

    function setupCustomLlmPageEvents() {
        const customBtn = document.getElementById('customLlmBtn');
        const clearBtn = document.getElementById('clearBtn');

        if (customBtn) {
            customBtn.addEventListener('click', () => {
                openProfilePage();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                setLocked(false);
            });
        }

        document.addEventListener('submit', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLFormElement)) {
                return;
            }

            if (target.id !== 'chatForm') {
                return;
            }

            if (!getActiveProfileId()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                openProfilePage();
                return;
            }

            setLocked(true);
        }, true);
    }

    window.KUZAI_CUSTOM_LLM = {
        getActiveProfileId,
        getActiveProfileLabel,
        isLocked,
        setLocked,
    };

    setupCustomLlmPageEvents();
    updateButtonState();
}());
/* KUZAI_CUSTOM_LLM_PAGE_MODULE_END */

