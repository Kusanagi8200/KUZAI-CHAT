#### **`KUZAI - THE LOCAL AI`**

#### **`COMPLETE INSTALLATION OF A LOCAL, AUTONOMOUS, AND EXTENSIBLE AI ASSISTANT.`**

------------------------------------------------------------------------

![KUZAI LLM3](https://github.com/Kusanagi8200/Kusanagi8200/blob/main/KUZAI-LLM3.png)

------------------------------------------------------------------------

#### **`WHITE PAPER`**

[![STATUS: BETA-0.03.2026](https://img.shields.io/badge/STATUS-BETA--0.03.2026-7bf2ff?style=flat-square)](#project-information)
[![DEPLOYMENT: LOCAL, SELF-HOSTED, MODULAR](https://img.shields.io/badge/DEPLOYMENT-LOCAL%20%7C%20SELF--HOSTED%20%7C%20MODULAR-8fc4ff?style=flat-square)](#project-information)
[![RUNTIME: LLAMA.CPP](https://img.shields.io/badge/RUNTIME-LLAMA.CPP-c19cff?style=flat-square)](#deployment)
[![PLATFORM: LINUX](https://img.shields.io/badge/PLATFORM-LINUX-fbfdff?style=flat-square&logo=linux&logoColor=000000)](#deployment)

> #### **`LOCAL AI IS NOT JUST ABOUT RUNNING A MODEL. IT IS ABOUT OWNING THE FULL STACK.`**

**KUZAI AI is a self-hosted local artificial intelligence control layer designed to keep models, prompts, files, repositories, voice generation, search workflows, and runtime services under the direct control of the operator.**

---

<a id="project-information"></a>

#### **`PROJECT INFORMATION`**

| Field | Value |
|---|---|
| **PROJECT** | KUZAI AI |
| **ECOSYSTEM** | KUZ NETWORK |
| **STATUS** | BETA-0.03.2026 |
| **DEPLOYMENT** | LOCAL / SELF-HOSTED / MODULAR |
| **INFERENCE** | LOCAL LLAMA.CPP RUNTIME |
| **WEB SEARCH** | LOCAL SEARXNG SERVICE |
| **VOICE** | PIPER TTS / ESPEAK FALLBACK |
| **DIRECTION** | OPEN SOURCE / PRIVATE RUNTIME |

---

#### **`CONTENTS`**

- [1. EXECUTIVE SUMMARY](#executive-summary)
- [2. PROJECT POSITION](#project-position)
- [3. CORE OBJECTIVES](#objectives)
- [4. SYSTEM ARCHITECTURE](#architecture)
- [5. CORE CAPABILITIES](#capabilities)
- [6. LOCAL CHAT AND MODEL CONTROL](#local-chat)
- [7. FILE UPLOAD AND ANALYSIS](#file-analysis)
- [8. WEB SEARCH AND SOURCE CONTEXT](#web-search)
- [9. LOCAL VOICE SYNTHESIS](#voice)
- [10. CUSTOM PROFILES AND SYSTEM PROMPTS](#profiles)
- [11. GIT-RAG REPOSITORY ANALYSIS](#git-rag)
- [12. RUNTIME STATUS AND SERVICE SUPERVISION](#runtime)
- [13. COMPLETE REQUEST DATA FLOW](#data-flow)
- [14. PRIVACY AND SECURITY MODEL](#privacy)
- [15. DEPLOYMENT STACK](#deployment)
- [16. USE CASES](#use-cases)
- [17. CURRENT LIMITATIONS](#limitations)
- [18. DEVELOPMENT ROADMAP](#roadmap)
- [19. CONCLUSION](#conclusion)

---

<a id="executive-summary"></a>

#### **`1. EXECUTIVE SUMMARY`**

KUZAI AI is a local AI application and control layer that connects a browser interface to locally operated language models and supporting services.

The project does not define local AI as a model running behind a basic chat interface. It treats local AI as a complete technical chain including inference, prompt control, file processing, web-assisted research, voice synthesis, repository retrieval, storage, runtime supervision, and user controls.

The system is designed around open, auditable, reproducible, and replaceable components. Its current architecture combines Linux, Apache2, PHP, JavaScript, llama.cpp, SearXNG, Piper TTS, eSpeak NG, local storage, Git, and systemd-managed services.

KUZAI AI is intended for developers, technical operators, researchers, small organizations, private infrastructures, and users who require greater control over their AI environment.

---

<a id="project-position"></a>

#### **`2. PROJECT POSITION`**

Most commercial AI services centralize the model, application logic, pricing, data flow, feature set, and infrastructure inside an external platform.

Running inference locally removes part of that dependency, but inference alone does not create a complete usable AI system. A practical local platform also requires an interface, file ingestion, search, speech, retrieval, prompt management, storage, service monitoring, and operational controls.

KUZAI AI provides this application layer while preserving the ability to replace individual components as the local AI ecosystem evolves.

> **The operator must remain in control of the model, the prompt, the files, the runtime, the services, and the evolution of the system.**

---

<a id="objectives"></a>

#### **`3. CORE OBJECTIVES`**

- Run language-model inference on infrastructure controlled by the operator.
- Keep prompts, uploaded files, repository content, profiles, and generated data inside the local environment whenever possible.
- Use modular components that can be replaced without rebuilding the complete application.
- Expose system behavior through conventional source code, APIs, configuration files, services, and logs.
- Avoid mandatory dependence on a single cloud provider, model vendor, API, or subscription.
- Allow the platform to evolve progressively through optional modules.
- Support reproducible deployments and transparent infrastructure management.

---

<a id="architecture"></a>

#### **`4. SYSTEM ARCHITECTURE`**

KUZAI AI uses a browser-based frontend, a PHP application layer, and a local OpenAI-compatible inference endpoint provided by llama.cpp.

Optional services extend the local model with web results, uploaded documents, repository context, local speech generation, vector embeddings, repository editing, and controlled Git operations.

The validated Git-RAG architecture separates the PHP integration layer from a dedicated local Python service bound to loopback. Repository data, vector indexes, backups, and Git credentials remain inside the operator-controlled infrastructure.

```text
+--------------------------------------------------------------------------------------------------+
| USER / BROWSER                                                                                   |
+--------------------------------------------------------------------------------------------------+
                                                |
                                                v
+--------------------------------------------------------------------------------------------------+
| KUZAI WEB INTERFACE - HTML / CSS / JAVASCRIPT                                                    |
+--------------------------------------------------------------------------------------------------+
                                                |
                                                v
+--------------------------------------------------------------------------------------------------+
| APACHE2 / PHP API LAYER                                                                          |
+--------------------------------------------------------------------------------------------------+
        |                        |                        |                        |
        v                        v                        v                        v
+----------------------+ +----------------------+ +----------------------+ +----------------------+
| LLAMA.CPP INFERENCE  | | FILE PROCESSING      | | SEARXNG WEB SEARCH   | | PIPER / ESPEAK       |
| 127.0.0.1:8080       | | LOCAL STORAGE        | | SOURCE CONTEXT       | | LOCAL AUDIO OUTPUT    |
+----------------------+ +----------------------+ +----------------------+ +----------------------+
                                                |
                                                v
+--------------------------------------------------------------------------------------------------+
| GIT-RAG PHP API                                                                                  |
+--------------------------------------------------------------------------------------------------+
                                                |
                                                v
+--------------------------------------------------------------------------------------------------+
| LOCAL PYTHON SERVICE - 127.0.0.1:8890                                                            |
+--------------------------------------------------------------------------------------------------+
                     |                                                    |
                     v                                                    v
+----------------------------------------------+     +----------------------------------------------+
| LLAMA.CPP EMBEDDINGS /V1/EMBEDDINGS          |     | LOCAL GIT REPOSITORIES                       |
| LOCAL VECTOR INDEX                           |     | READ / EDIT / BACKUP                         |
| RELEVANT SOURCE CHUNKS                       |     | STATUS / DIFF / COMMIT                       |
|                                              |     | PUSH / PULL --FF-ONLY / REINDEX              |
+----------------------------------------------+     +----------------------------------------------+
                     |
                     v
+--------------------------------------------------------------------------------------------------+
| CHAT CONTEXT INJECTION -> LOCAL LLAMA.CPP INFERENCE -> TEXT RESPONSE -> BROWSER / LOCAL TTS      |
+--------------------------------------------------------------------------------------------------+
```

---

<a id="capabilities"></a>

#### **`5. CORE CAPABILITIES`**

- **LOCAL AI CHAT** — Browser-based conversation with locally generated responses, conversation context, generation interruption, session clearing, dynamic response rendering, and direct access to a local llama.cpp runtime.
- **LOCAL MODEL RUNTIME** — OpenAI-compatible local inference through llama.cpp with replaceable GGUF models selected according to hardware, language, context, performance, quantization, and licensing requirements.
- **FILE UPLOAD AND ANALYSIS** — Server-side upload, validation, textual extraction, local storage, and prompt-context injection for source code, logs, JSON, CSV, configuration files, scripts, markup, and technical documents.
- **LOCAL WEB SEARCH** — Locally hosted SearXNG search with result filtering, source URL extraction, contextual prompt injection, and an explicit WEB OFF or WEB ON operating mode.
- **SOURCE-AWARE ANSWERS** — Selected web results are transformed into structured model context so that locally generated answers can retain and display the original source URLs.
- **LOCAL VOICE SYNTHESIS** — Piper neural text-to-speech with browser playback, manual SPEAK controls, VOICE OFF or VOICE ON automatic playback, local WAV generation, and eSpeak NG fallback.
- **SPEECH TEXT CLEANING** — Displayed technical answers remain complete while URLs, source lists, code blocks, commands, paths, and other technical noise are removed from the spoken version.
- **CUSTOM LLM PROFILES** — Profile creation, editing, JSON preview, server-side storage, activation, runtime selection, listing, deletion, and session-level application for specialized assistant behavior.
- **CUSTOM SYSTEM PROMPT** — Direct control over assistant identity, language, formatting, technical depth, task boundaries, priorities, response style, and domain-specific behavior.
- **GIT-RAG** — Optional local repository retrieval and controlled Git workspace operations including repository selection, file reading, editing with backup, status, diff, commit, push, fast-forward-only pull, reindexing, and chat-context injection.
- **RUNTIME STATUS** — Application, model, endpoint, service, repository, and health information exposed through local APIs, systemd supervision, logs, and standard Linux diagnostic tools.
- **LOCAL STORAGE** — Uploaded files, profiles, generated voice files, repository metadata, indexes, backups, and application state remain on operator-controlled storage.

---

<a id="local-chat"></a>

#### **`6. LOCAL CHAT AND MODEL CONTROL`**

The chat API assembles the system prompt, conversation history, user input, uploaded file context, web results, active profile data, and optional repository context before sending the request to the local model.

The use of an OpenAI-compatible API reduces coupling between the interface and the model runtime. The operator can replace the active model without redesigning the browser application, provided the selected runtime remains compatible.

Model selection can therefore be based on available VRAM, system RAM, CPU performance, target language, coding ability, reasoning quality, context length, quantization level, and licensing.

- SEND starts a local generation request.
- STOP interrupts the active browser request.
- CLEAR resets the visible conversation state.
- Conversation context can be limited to control prompt size.
- Runtime parameters can be adjusted independently of the interface.
- The model endpoint can remain bound to loopback or a private network.

---

<a id="file-analysis"></a>

#### **`7. FILE UPLOAD AND ANALYSIS`**

The upload module validates each file, checks its size and extension, extracts supported textual content, normalizes the result, applies configured length limits, and stores local metadata.

The uploaded content is then referenced by the chat request and injected as user-provided context.

This enables technical analysis without transferring the source document to a remote inference provider.

- Source-code review and debugging.
- Linux and application log analysis.
- Configuration and service-file inspection.
- JSON, YAML, XML, CSV, and structured-text examination.
- Shell-script and automation review.
- Technical-document summarization.
- Incident and troubleshooting context injection.

---

<a id="web-search"></a>

#### **`8. WEB SEARCH AND SOURCE CONTEXT`**

A local language model cannot independently retrieve current information. KUZAI AI uses a locally hosted SearXNG instance to provide controlled web-assisted research.

The search API receives the query, requests results from SearXNG, extracts titles, URLs, summaries, and engine information, then makes the selected results available as optional model context.

WEB OFF keeps the request inside local model knowledge, uploaded files, active profiles, conversation history, and any other locally selected context.

WEB ON automatically performs web search before final generation, displays the selected sources, and injects their context into the request sent to llama.cpp.

Search traffic may reach external search engines, but final answer generation remains local.

- Explicit WEB OFF and WEB ON mode.
- Automatic search before generation when WEB ON is active.
- Locally hosted SearXNG integration.
- Result title, URL, excerpt, and engine extraction.
- Controlled result limits.
- Source URL preservation.
- Prompt-context injection.
- Local fallback when automatic web search fails.
- Final inference through the local llama.cpp runtime.

---

<a id="voice"></a>

#### **`9. LOCAL VOICE SYNTHESIS`**

KUZAI AI includes a local speech pipeline that converts assistant responses into WAV audio without using a remote text-to-speech provider.

Piper is the primary neural TTS engine. The validated reference voice is en_US-lessac-high. eSpeak NG remains available as a local fallback when Piper cannot generate a valid audio file.

The browser can request speech manually through SPEAK controls or play assistant answers automatically when VOICE ON is enabled.

Displayed answers remain complete, while the spoken version is cleaned to remove code blocks, URLs, source lists, commands, paths, and other technical content that should not be read aloud.

- Manual SPEAK control for assistant responses.
- VOICE OFF and VOICE ON automatic playback modes.
- STOP AUDIO playback control.
- Local WAV generation and browser delivery.
- Piper en_US-lessac-high reference voice.
- eSpeak NG en-us+f4 fallback voice.
- Unique audio identifiers.
- Temporary audio-file cleanup.
- Speech-specific text normalization.
- Removal of URLs, source lists, commands, paths, code blocks, and technical noise.
- Local browser-state persistence for voice mode.

---

<a id="profiles"></a>

#### **`10. CUSTOM PROFILES AND SYSTEM PROMPTS`**

Custom profiles provide task-specific model behavior without modifying or retraining the model weights.

Each profile can define a dedicated system prompt, operational role, expected tone, formatting rules, language, technical depth, and response constraints.

The selected profile is activated as an explicit runtime layer and injected into the request sent to the local model.

- Profile editor.
- JSON profile preview.
- Profile save and update workflow.
- Server-side profile list.
- Profile deletion.
- Session-level active profile.
- Runtime prompt injection.
- Specialized Linux, development, security, writing, support, or research assistants.

---

<a id="git-rag"></a>

#### **`11. GIT-RAG REPOSITORY ANALYSIS`**

Git-RAG is an optional local retrieval and repository workspace module designed to connect the assistant to source repositories cloned on controlled infrastructure.

The service exposes repository selection, active branch information, readiness status, file inventory, file sizes, text or binary classification, file reading, controlled editing, and timestamped backups before modification.

Repository content is indexed locally and can be retrieved through local embeddings generated by the llama.cpp embeddings endpoint. Selected source context is then injected into the chat request.

Validated Git operations include status, diff, commit, push, fast-forward-only pull, and explicit reindexing. Write operations remain deliberate and separate from ordinary chat requests.

Git-RAG runs as an independent local microservice. The main KUZAI AI application remains operational when the repository service is disabled or unavailable.

- Whitelisted public and private local repositories.
- SSH-based repository access under operator-controlled credentials.
- Single active repository workspace.
- Active branch and repository readiness information.
- Repository file inventory and source classification.
- Code, Markdown, text, JSON, YAML, configuration, and log indexing.
- Local file reading.
- Controlled file editing with timestamped backup.
- Git status and diff inspection.
- Explicit commit operations with operator-defined messages.
- Explicit push operations.
- Fast-forward-only pull operations.
- Manual repository reindexing.
- Local embedding generation through llama.cpp.
- Repository context injection into chat requests.
- Independent local service on 127.0.0.1:8890.

---

<a id="runtime"></a>

#### **`12. RUNTIME STATUS AND SERVICE SUPERVISION`**

KUZAI AI exposes local status information for the application, configured model, active model, inference endpoint, web-search service, Git-RAG service, PHP runtime, voice pipeline, and supporting infrastructure.

The runtime interface is primarily a supervision and diagnostic layer. It does not imply unrestricted remote administration of the host.

systemd provides startup management, dependency handling, automatic restart, service status inspection, and journal-based diagnostics.

- llama.cpp health, endpoint, and active-model checks.
- Inference service verification on 127.0.0.1:8080.
- SearXNG verification on 127.0.0.1:8888.
- Git-RAG verification on 127.0.0.1:8890.
- Piper and eSpeak NG TTS validation.
- PHP syntax checking.
- Apache configuration testing.
- HTTP endpoint and JSON response testing.
- systemd unit status inspection.
- journalctl service-log inspection.
- Application version and environment information.
- Repository readiness and indexing status.

---

<a id="data-flow"></a>

#### **`13. COMPLETE REQUEST DATA FLOW`**

Each optional context source is added only when it is selected, available, and relevant to the active request.

This layered structure keeps the request path visible and allows each module to be tested independently.

```text
BASE SYSTEM PROMPT
        +
ACTIVE CUSTOM PROFILE
        +
CONVERSATION HISTORY
        +
USER PROMPT
        +
UPLOADED FILE CONTEXT
        +
WEB SEARCH CONTEXT
        +
GIT-RAG REPOSITORY CONTEXT
        |
        v
LOCAL LLAMA.CPP INFERENCE
        |
        v
TEXT RESPONSE
        |
        +-------------------------+
        |                         |
        v                         v
BROWSER DISPLAY          LOCAL TTS GENERATION
```

---

<a id="privacy"></a>

#### **`14. PRIVACY AND SECURITY MODEL`**

Local inference reduces the need to transmit prompts, documents, source code, repository context, and private operational data to an external AI API.

Local deployment does not automatically guarantee security. The operator remains responsible for operating-system hardening, authentication, network exposure, firewall rules, filesystem permissions, repository credentials, backups, logs, software updates, and model licensing.

External traffic can still occur when web search is enabled, repositories are pulled or pushed, packages are installed, or models are downloaded.

Git-RAG write operations require particular attention because an edit, commit, push, or synchronization operation can modify a working tree or remote repository.

- Bind internal model, search, and Git-RAG endpoints to loopback or a trusted private network.
- Protect public access with authentication, TLS, and appropriate reverse-proxy rules.
- Use an explicit repository whitelist.
- Run repository operations under a dedicated service account where possible.
- Apply least-privilege filesystem and SSH permissions.
- Protect private keys, access tokens, and repository credentials.
- Require explicit operator actions for edit, commit, push, and pull operations.
- Create timestamped backups before modifying repository files.
- Use fast-forward-only pulls to avoid implicit merge operations.
- Review status and diff output before commit or push.
- Separate public presentation services from private AI runtime services.
- Review application logs, service journals, and repository activity.
- Apply controlled backup, retention, and restoration policies.
- Validate every optional external integration.

---

<a id="deployment"></a>

#### **`15. DEPLOYMENT STACK`**

The application can run on a workstation, development computer, dedicated GPU host, private LAN server, or small organizational infrastructure.

The reference deployment uses Ubuntu, Apache, PHP, llama.cpp, SearXNG, Piper, Git, and an independent local Git-RAG service.

Performance depends on the selected model, context size, quantization, CPU, RAM, GPU, VRAM, storage, embedding workload, and runtime parameters.

The listed model and endpoints describe the validated reference installation. Components remain replaceable.

```text
OPERATING SYSTEM     UBUNTU 24.04 LTS / LINUX
WEB SERVER           APACHE2
BACKEND              PHP 8.4
FRONTEND             HTML / CSS / JAVASCRIPT
LLM RUNTIME          LLAMA.CPP OPENAI-COMPATIBLE API
REFERENCE MODEL      QWEN3-8B Q5_K_M
MODEL FORMAT         GGUF
INFERENCE ENDPOINT   127.0.0.1:8080
EMBEDDINGS           LLAMA.CPP /V1/EMBEDDINGS
WEB SEARCH           SEARXNG
SEARCH ENDPOINT      127.0.0.1:8888
GIT-RAG SERVICE      LOCAL PYTHON MICROSERVICE
GIT-RAG ENDPOINT     127.0.0.1:8890
VOICE ENGINE         PIPER TTS
REFERENCE VOICE      EN_US-LESSAC-HIGH
VOICE FALLBACK       ESPEAK NG
SERVICE CONTROL      SYSTEMD
LOCAL STORAGE        FILESYSTEM
REPOSITORY LAYER     GIT + LOCAL INDEXES + BACKUPS
```

---

<a id="use-cases"></a>

#### **`16. USE CASES`**

- Private technical assistance.
- Linux, server, and network troubleshooting.
- Source-code review and debugging.
- Local analysis of internal documents.
- Repository exploration and software architecture review.
- Web-assisted research with source context.
- Task-specific assistants using custom profiles.
- Voice access to locally generated answers.
- Local AI infrastructure experimentation.
- Evaluation of open-weight language models.
- Controlled development of retrieval and agentic workflows.

---

<a id="limitations"></a>

#### **`17. CURRENT LIMITATIONS`**

KUZAI AI is an evolving beta project. Owning the stack also means owning its validation, operation, security, maintenance, permissions, backups, and technical debt.

- Inference performance remains constrained by local hardware.
- Response quality depends on the selected model, context size, prompt structure, and quantization.
- The current upload pipeline is primarily designed for text-compatible formats.
- Web sources do not eliminate hallucinations, outdated information, or interpretation errors.
- Git-RAG quality depends on index freshness, embeddings, chunking, retrieval strategy, and source selection.
- The current Git-RAG workflow is centered on one active repository context.
- Multi-branch comparison and combined multi-repository retrieval remain limited.
- Repository write operations require appropriate permissions, review, backups, and operator discipline.
- The base installation does not provide universal multi-user isolation.
- Authentication and hardening depend on the deployment environment.
- The platform requires active Linux system administration and maintenance.
- Feature parity with large commercial AI platforms is not guaranteed.

---

<a id="roadmap"></a>

#### **`18. DEVELOPMENT ROADMAP`**

- Incremental repository indexing.
- Automatic detection of modified, added, and deleted repository files.
- Improved source attribution and retrieved-chunk traceability.
- Combined context from multiple repositories.
- Multi-branch repository navigation and comparison.
- Read-only and write-enabled repository permission profiles.
- Approval gates before edit, commit, push, or synchronization operations.
- Improved embedding, chunking, ranking, and retrieval evaluation.
- PDF and office-document extraction.
- Persistent server-side conversation history.
- Local long-term memory modules.
- Profile import and export.
- Authentication and role-based access control.
- Model selection through the browser interface.
- GPU, VRAM, memory, context, and runtime monitoring.
- Conversation export in Markdown and JSON.
- Support for additional local inference runtimes.
- Sandboxed tool execution with explicit permissions.
- Controlled agentic workflows.
- Multi-node local AI orchestration.

---

<a id="conclusion"></a>

#### **`19. CONCLUSION`**

KUZAI AI transforms local model inference into a complete and independently operated local AI environment.

It combines local chat, replaceable GGUF models, file upload and analysis, controlled web search, source-aware answers, local voice synthesis, custom profiles, system-prompt control, Git-RAG retrieval, controlled repository operations, runtime supervision, and local storage.

Its modular architecture keeps the model runtime, search engine, voice engine, repository service, web interface, and storage layers understandable and replaceable.

The objective is not to freeze every component. The objective is to ensure that each component can remain auditable, maintainable, replaceable, and controlled by the operator.

> **KUZAI AI is not only a local chatbot. It is a foundation for independently operated AI infrastructure.**

---

#### **`THE KUZ NETWORK - KUSANAGI8200 - @2026`**
