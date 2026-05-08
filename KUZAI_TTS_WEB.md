#### **`KUZAI - Installation and Evolution Process from Voice Synthesis`**

------------------------------------------------------------------------

<picture>
 <source media="(prefers-color-scheme -->  dark)" srcset="">
 <source media="(prefers-color-scheme -->  light)" srcset="">
 <img alt="" src="">
</picture> 

------------------------------------------------------------------------

##### **`1. Document purpose`**

This document describes the KUZAI installation and evolution process starting from the introduction of text-to-speech voice synthesis.

It covers the validated implementation path for --> 

- voice playback of chatbot responses.
- improved voice quality with a local neural TTS engine.
- browser-side audio controls.
- optional automatic voice playback.
- web search integration through local SearXNG.
- automatic web search before sending a prompt.
- interface cleanup and UX stabilization.

The document starts after the base KUZAI application is already operational with a local `llama.cpp` backend.

---

##### **`2. Initial baseline`**

Before this process starts, KUZAI already has the following baseline --> 

```text
User browser
  -> Apache / PHP
  -> KUZAI web interface
  -> chat.php
  -> llama.cpp local server
  -> qwen3-8b-q5km model
  -> text response returned to the browser
```

The initial system can already generate text answers locally, but it cannot yet vocalize them.

---

##### **`3. Target result`**

The target result of this process is a local KUZAI instance with --> 

```text
Text chat
Voice playback
Voice auto-play mode
Local neural TTS with fallback
Text cleanup before speech generation
Local web search
Automatic web search mode
Upload support preserved
Stable control toolbar
```

The final validated components are --> 

```text
Application -->  KUZAI
Web server -->  Apache2
Backend language -->  PHP
Frontend -->  HTML / CSS / JavaScript
LLM backend -->  llama.cpp
Model -->  qwen3-8b-q5km
Primary TTS engine -->  Piper
Primary TTS voice -->  en_US-lessac-high
Fallback TTS engine -->  eSpeak NG
Fallback TTS voice -->  en-us+f4
Web search engine -->  SearXNG
Browser state -->  localStorage
Audio format -->  WAV
```

---

##### **`4. Functional installation synoptic`**

```text
[ USER / BROWSER ]
        |
        v
[ KUZAI WEB INTERFACE ]
        |
        v
[ app.js CONTROLLER ]
        |
        +------------------------------+
        |                              |
        v                              v
[ chat.php ]                    [ tts.php ]
        |                              |
        v                              v
[ llama.cpp ]                   [ text cleanup ]
        |                              |
        v                              v
[ qwen3-8b-q5km ]               [ Piper TTS ]
        |                              |
        v                              +---- fallback ----+
[ text response ]               |                  [ eSpeak NG ]
        |                       |                        |
        v                       v                        v
[ app.js ]                  [ WAV audio ] <---------------+
        |                       |
        v                       v
[ Browser display ]       [ Browser playback ]
```

Extended circuit with web search and upload --> 

```text
[ USER / BROWSER ]
        |
        v
[ KUZAI UI CONTROLS ]
  WEB ON/OFF
  VOICE ON/OFF
  UPLOAD
  SEND
  STOP
        |
        v
[ app.js ]
        |
        +------------------------------+
        |                              |
        v                              v
[ web-search.php ]              [ upload.php ]
        |                              |
        v                              v
[ SearXNG local ]               [ local uploaded files ]
        |                              |
        v                              v
[ web results ]                 [ file context ]
        |                              |
        +--------------+---------------+
                       |
                       v
                  [ chat.php ]
                       |
                       v
                  [ llama.cpp ]
                       |
                       v
                  [ text answer ]
                       |
                       v
                  [ optional TTS path ]
```

---

##### **`5. Step-by-step implementation process`**

###### **`5.1. Step 1 - Add a local TTS directory`**

A dedicated storage directory is required for generated audio files.

Target directory --> 

```text
/var/www/html/KUZAI/storage/tts
```

Purpose --> 

- store generated WAV files.
- store temporary text files used by the TTS engine.
- allow PHP and Apache to serve generated audio safely through the TTS API.

Expected ownership --> 

```text
www-data --> www-data
```

Expected directory mode --> 

```text
750
```

---

###### **`5.2. Step 2 - Validate eSpeak NG`**

The first TTS engine used is `espeak-ng` because it is simple, local, and easy to validate.

Validation objectives --> 

- confirm the binary exists.
- confirm the engine can generate a WAV file.
- confirm generation works under the `www-data` user.
- confirm the generated WAV file is readable.

Validated result --> 

```text
eSpeak NG can generate WAV audio files in storage/tts.
```

Initial limitation --> 

```text
The voice works but sounds robotic.
```

---

###### **`5.3. Step 3 - Configure English voice synthesis`**

The initial French voice configuration is replaced by an English voice.

Validated English voice family --> 

```text
en-us
```

The system is then tested with short English sentences to confirm that --> 

- the voice is English.
- the generated WAV is valid.
- the audio can be downloaded through the API.

---

###### **`5.4. Step 4 - Select a female eSpeak voice variant`**

Several eSpeak female variants are tested.

Tested variants --> 

```text
en-us+f1
en-us+f2
en-us+f3
en-us+f4
```

Selected fallback voice --> 

```text
en-us+f4
```

This voice is kept as fallback after Piper is introduced.

---

###### **`5.5. Step 5 - Create the TTS API`**

A new PHP endpoint is introduced --> 

```text
/var/www/html/KUZAI/public/api/tts.php
```

Responsibilities --> 

- receive text from the browser.
- generate a unique audio ID.
- create a temporary input text file.
- generate a WAV file.
- return the audio URL.
- serve audio by ID through HTTP GET.
- support HTTP HEAD for browser and diagnostic checks.
- clean old generated files.

Initial API behavior --> 

```text
POST /KUZAI/api/tts.php
  -> generates a WAV file
  -> returns JSON with audio.id and audio.url

GET /KUZAI/api/tts.php?id=<audio_id>
  -> serves the WAV file

HEAD /KUZAI/api/tts.php?id=<audio_id>
  -> returns audio headers without body
```

---

###### **`5.6. Step 6 - Add manual voice playback to the interface`**

The frontend controller `app.js` is updated so that every assistant answer can be spoken manually.

Added UI controls below assistant responses --> 

```text
SPEAK
STOP AUDIO
```

Frontend logic --> 

```text
SPEAK
  -> sends assistant response text to tts.php
  -> receives audio URL
  -> creates an Audio object in the browser
  -> plays WAV audio

STOP AUDIO
  -> stops current audio playback
  -> resets the audio control state
```

Result --> 

```text
Any KUZAI response can be spoken manually by the user.
```

---

###### **`5.7. Step 7 - Add automatic voice playback`**

A global voice mode is added.

Initial labels --> 

```text
AUTO VOICE OFF
AUTO VOICE ON
```

Final simplified labels --> 

```text
VOICE OFF
VOICE ON
```

State management --> 

```text
localStorage key -->  kuzai.autoSpeak.v1
```

Behavior --> 

```text
VOICE OFF
  -> assistant responses are displayed but not spoken automatically

VOICE ON
  -> assistant responses are displayed and then spoken automatically
```

Manual `SPEAK` remains available even when `VOICE OFF` is active.

---

###### **`5.8. Step 8 - Install Piper for higher-quality local TTS`**

Because eSpeak remains robotic, Piper is installed as the primary local neural TTS engine.

Target Piper path --> 

```text
/opt/kuzai-tts/piper
```

Python virtual environment --> 

```text
/opt/kuzai-tts/piper/venv
```

Piper binary --> 

```text
/opt/kuzai-tts/piper/venv/bin/piper
```

Primary model directory --> 

```text
/opt/kuzai-tts/piper/models
```

Selected model --> 

```text
en_US-lessac-high.onnx
en_US-lessac-high.onnx.json
```

Validated voice --> 

```text
en_US-lessac-high
```

Result --> 

```text
Piper generates local WAV audio with a much more natural voice than eSpeak.
```

---

###### **`5.9. Step 9 - Set Piper as default TTS engine`**

The TTS API is updated so that Piper is used by default.

Final TTS engine order --> 

```text
Primary -->  Piper
Fallback -->  eSpeak NG
```

Final default voice --> 

```text
Piper -->  en_US-lessac-high
Fallback eSpeak -->  en-us+f4
```

Fallback logic --> 

```text
1. tts.php receives text.
2. tts.php tries Piper.
3. If Piper fails or generates no valid WAV, tts.php falls back to eSpeak NG.
4. The browser always receives a valid audio URL if at least one engine succeeds.
```

---

###### **`5.10. Step 10 - Tune Piper quality settings`**

Some audio artifacts are detected during real tests.

Symptoms --> 

```text
scratch noise
saturation
inaudible fragments
then normal playback resumes
```

Stabilized Piper parameters --> 

```text
Default speed -->  145
Length scale -->  calculated from speed, typically around 1.07
Sentence silence -->  0.35
Volume -->  0.75
```

Result --> 

```text
Audio becomes cleaner, less saturated, and more stable.
```

---

###### **`5.11. Step 11 - Clean text before voice generation`**

Technical answers often contain content that should not be spoken directly.

Problematic content --> 

```text
URLs
source lists
code blocks
shell commands
JSON fragments
Linux paths
pipe-heavy command lines
markdown formatting
```

A speech cleanup function is added to `tts.php`.

Principle --> 

```text
Displayed answer -->  unchanged and complete
Spoken answer -->  cleaned and simplified
```

Cleanup behavior --> 

- remove markdown code blocks.
- remove `Sources --> ` sections.
- remove URLs.
- remove shell-command-heavy lines.
- remove noisy markdown characters.
- limit spoken text length.
- keep the useful natural-language explanation.

Result --> 

```text
The UI still shows the complete technical answer, but Piper only reads a clean spoken version.
```

---

###### **`5.12. Step 12 - Remove initial assistant messages`**

The chat window originally displayed an automatic assistant message when the page loaded or after `CLEAR`.

Decision --> 

```text
The conversation area must be empty on page load and after CLEAR.
```

Result --> 

```text
No fake assistant message appears.
No unnecessary SPEAK / STOP AUDIO buttons appear before a real response.
```

---

###### **`5.13. Step 13 - Validate web search behavior`**

KUZAI uses SearXNG for local web search.

Search chain --> 

```text
app.js
  -> web-search.php
  -> SearXNG on 127.0.0.1 --> 8888
  -> search results
  -> app.js
  -> chat.php
  -> llama.cpp with injected sources
```

Important behavior --> 

```text
SEND only
  -> local model response without web search

WEB then SEND
  -> search results are injected into chat.php
  -> local model answers with web context
```

---

###### **`5.14. Step 14 - Add automatic web search mode`**

To avoid manually clicking `WEB` before `SEND`, an automatic web mode is added.

Initial labels --> 

```text
AUTO WEB OFF
AUTO WEB ON
```

Final simplified labels --> 

```text
WEB OFF
WEB ON
```

State management --> 

```text
localStorage key -->  kuzai.autoWeb.v1
```

Behavior --> 

```text
WEB OFF
  -> SEND uses only local context and already attached files/results

WEB ON
  -> SEND automatically runs web search first
  -> results are displayed
  -> results are injected into chat.php
  -> answer is generated with web context
```

Fallback behavior --> 

```text
If automatic web search fails, SEND continues in local mode.
```

---

###### **`5.15. Step 15 - Remove the manual WEB button`**

Once `WEB ON / WEB OFF` is available, the old manual `WEB` button becomes redundant.

Decision --> 

```text
Hide the manual WEB button.
Keep WEB ON/OFF as the web mode control.
```

Final toolbar order --> 

```text
WEB | VOICE | UPLOAD | SEND | STOP
```

Expanded labels --> 

```text
WEB OFF / WEB ON
VOICE OFF / VOICE ON
UPLOAD
SEND
STOP
```

Result --> 

```text
The toolbar is simpler and the control flow is clearer.
```

---

###### **`5.16. Step 16 - Stabilize toolbar layout`**

Several UI layout fixes are applied after adding the new buttons.

Goals --> 

- keep buttons on one line when possible.
- add spacing between buttons.
- make `VOICE OFF / VOICE ON` wide enough.
- keep the manual `WEB` button hidden.
- preserve `STOP` behavior.

Final intended toolbar display --> 

```text
WEB OFF | VOICE OFF | UPLOAD | SEND | STOP
```

or --> 

```text
WEB ON | VOICE ON | UPLOAD | SEND | STOP
```

---

##### **`6. Final operating logic`**

###### **`6.1. Local-only answer`**

```text
User writes a prompt
  -> SEND
  -> app.js sends prompt to chat.php
  -> chat.php sends prompt to llama.cpp
  -> llama.cpp returns text
  -> app.js displays answer
```

---

###### **`6.2. Web-enabled answer`**

```text
WEB ON
User writes a prompt
  -> SEND
  -> app.js runs web-search.php
  -> web-search.php queries SearXNG
  -> app.js receives web results
  -> app.js sends prompt + web results to chat.php
  -> chat.php injects web sources into the prompt
  -> llama.cpp returns web-enriched text
  -> app.js displays answer
```

---

###### **`6.3. Manual voice playback`**

```text
Assistant answer displayed
  -> user clicks SPEAK
  -> app.js sends answer text to tts.php
  -> tts.php cleans spoken text
  -> Piper generates WAV
  -> browser plays audio
```

---

###### **`6.4. Automatic voice playback`**

```text
VOICE ON
Assistant answer generated
  -> app.js automatically calls tts.php
  -> tts.php cleans spoken text
  -> Piper generates WAV
  -> browser plays audio
```

---

###### **`6.5. Stop behavior`**

```text
STOP during generation
  -> aborts the current browser request

STOP AUDIO during playback
  -> stops current audio playback

Escape key
  -> stops generation if busy
  -> stops audio playback when not busy
```

---

##### **`7. Files impacted by this process`**

###### **`Frontend files`**

```text
/var/www/html/KUZAI/public/index.php
/var/www/html/KUZAI/public/assets/js/app.js
/var/www/html/KUZAI/public/assets/css/style.css
```

###### **`API files`**

```text
/var/www/html/KUZAI/public/api/tts.php
/var/www/html/KUZAI/public/api/chat.php
/var/www/html/KUZAI/public/api/web-search.php
/var/www/html/KUZAI/public/api/upload.php
/var/www/html/KUZAI/public/api/status.php
```

###### **`Application configuration`**

```text
/var/www/html/KUZAI/app/config.php
```

###### **`TTS components`**

```text
/opt/kuzai-tts/piper/venv/bin/piper
/opt/kuzai-tts/piper/models/en_US-lessac-high.onnx
/opt/kuzai-tts/piper/models/en_US-lessac-high.onnx.json
/usr/bin/espeak-ng
```

###### **`Storage paths`**

```text
/var/www/html/KUZAI/storage/tts
/var/www/html/KUZAI/storage/uploads
/var/www/html/KUZAI/storage/conversations
```

###### **`Web search components`**

```text
/etc/searxng/settings.yml
/etc/systemd/system/searxng.service
/opt/searxng
```

---

##### **`8. Final validation checklist`**

The system is considered stable when the following checks pass --> 

```text
KUZAI status API returns ok true
Apache configuration is valid
chat.php has no PHP syntax errors
tts.php has no PHP syntax errors
web-search.php has no PHP syntax errors
Piper generates WAV audio
TTS API serves WAV audio by ID
Fallback eSpeak works
WEB ON triggers search before SEND
VOICE ON triggers audio playback after answer
CLEAR empties the chat window
Toolbar layout remains stable
```

---

##### **`9. Stable final state before Step 44`**

Before moving to Step 44, the validated stable state is --> 

```text
Local LLM chat -->  operational
Piper TTS -->  operational
Fallback eSpeak -->  operational
Speech cleanup -->  operational
Web search through SearXNG -->  operational
Automatic web mode -->  operational
Automatic voice mode -->  operational
Upload support -->  preserved
Toolbar layout -->  stabilized
Conversation window -->  empty on load and after CLEAR
```

Recommended next step --> 

```text
STEP 44 - Server-side conversation history
```

------------------------------------------------------------------------

#### **`THE KUZ NETWORK - KUSANAGI8200 - @2026`**
