# KUZAI CURRENT BACKUP

## BACKUP METADATA

Generated at: 20260516-181326
Source path: /var/www/html/KUZAI
Repository path: /root/KUZAI-CHAT

## BACKUP CONTENT

- KUZAI PHP application
- public interface
- CSS / JS assets
- API endpoints
- application configuration
- logo assets
- SearXNG configuration when available
- SearXNG systemd service when available
- Piper voice JSON metadata when available

## EXCLUDED RUNTIME CONTENT

- generated TTS WAV files
- temporary TTS files
- uploaded user files
- conversation runtime files
- local backup files
- large Piper ONNX model file

## LARGE MODEL EXPECTED PATH

/opt/kuzai-tts/piper/models/en_US-lessac-high.onnx

## CURRENT APPLICATION PATH ON SERVER

/var/www/html/KUZAI

## CURRENT VALIDATED FEATURES

- local llama.cpp chat
- qwen3-8b-q5km model
- Piper TTS
- eSpeak fallback
- speech text cleanup before TTS generation
- WEB ON / WEB OFF
- VOICE ON / VOICE OFF
- upload support
- black KUZAI UI charter
- Anta typography
- integrated logo
- toolbar stabilized
- local SearXNG web search
