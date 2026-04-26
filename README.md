
KUZAI Local AI - complete installation of a local, autonomous, and extensible AI assistant.

The objective was simple -->  build a custom web interface able to communicate with a local LLM, analyze uploaded files, and use local web search, without depending on a cloud platform for inference.

The installed stack is intentionally simple and controllable --> 

Apache2 for web exposure.
PHP for the backend application.
JavaScript for the interactive interface.
Custom CSS for the graphical interface.
llama.cpp to serve the local model.
Qwen3-8B Q5_K_M as the active LLM model.
SearXNG installed natively for local web search.
systemd for service management.

The development process was done step by step.

First step -->  build a standalone PHP application named KUZAI, separated from the KUZCHAT LLM DUO project to avoid impacting the existing stack.

Second step -->  connect the application to the local llama.cpp server exposed on `127.0.0.1 --> 8080`, with active model verification through a status API.

Third step -->  develop the web interface with a dynamic conversation area, a prompt field, a SEND button, a STOP button to interrupt generation, and clear response rendering.

Fourth step -->  add file upload. Files are sent to the PHP backend, stored server-side, then their extracted content is injected into the prompt context for model analysis.

Fifth step -->  add web search. SearXNG was installed natively, exposed locally on `127.0.0.1 --> 8888`. The PHP API `web-search.php` queries SearXNG, retrieves the results, and forwards them to `chat.php` to enrich the LLM response.

Sixth step -->  stabilize the interface. The WEB, UPLOAD, STOP, SEND, CLEAR, and REMOVE buttons were adjusted. The response container was made dynamic to adapt to generated content.

Main specifications --> 

Application -->  KUZAI
Type -->  standalone PHP application
Frontend -->  HTML, CSS, JavaScript
Backend -->  PHP
Web server -->  Apache2
LLM backend -->  llama.cpp
Active model -->  qwen3-8b-q5km
Web search -->  local SearXNG
Upload -->  text files, source code, logs, JSON, and analyzable content
Web path -->  `/KUZAI/`
Search service -->  `searxng.service`
Objective -->  local, controlled, extensible AI assistant

Validated features --> 

Local chat with an LLM served by llama.cpp.
Uploaded file analysis.
Local web search through SearXNG.
Injection of web sources into the model context.
Automatic addition of source URLs in answers.
STOP button to interrupt generation.
Dynamic and customized interface.
Simple, readable, maintainable PHP backend.
Services managed by systemd.
Full validation through PHP linting, API tests, Apache configtest, and service checks.

The key point of the project -->  the whole chain is based on open source components or the open source / open-weight ecosystem.

No mandatory cloud inference.
No dependency on a proprietary interface.
No application black box.
The server, model, interface, search layer, and files remain under local control.

KUZAI provides a clean base for future extensions -->  PDF support, local memory, document indexing, RAG, persistent history, system profiles, GPU supervision, or integration into a broader control console.

A local AI assistant is not only a model.
It is a complete architecture -->  server, application, API, files, search, UI, security, services, and validation.

KUZAI is a stable first brick in that direction.

#LocalAI #OpenSource #Linux #PHP #LLM #llamacpp #SearXNG #SelfHosted #AIInfrastructure #Qwen #SysAdmin
