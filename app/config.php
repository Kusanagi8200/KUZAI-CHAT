<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'KUZAI',
        'brand_line' => 'A KUZ NETWORK SOLUTION - Beta-0.01.2026',
        'subtitle' => '/LOCAL AI WEB TOOLS/',
        'version' => '0.1.0',
        'timezone' => 'Europe/Paris',
    ],

    'llm' => [
        'api_base' => 'http://127.0.0.1:8080',
        'chat_endpoint' => 'http://127.0.0.1:8080/v1/chat/completions',
        'models_endpoint' => 'http://127.0.0.1:8080/v1/models',
        'model' => 'qwen3-8b-q5km',

        'temperature' => 0.4,
        'top_p' => 0.9,
        'top_k' => 40,
        'repeat_penalty' => 1.05,
        'max_tokens' => 768,
        'timeout' => 180,

        'no_think' => true,

        'system_prompt' => 'You are KUZAI, a local technical assistant running on a private llama.cpp server. Reply in clear plain text. Be precise, practical, and concise. Do not reveal hidden reasoning. Do not output reasoning_content. Do not use markdown tables unless requested. Do not answer with ellipsis only.',
    ],

    'limits' => [
        'max_user_message_chars' => 8000,
        'max_history_messages' => 20,
    ],

    'uploads' => [
        'enabled' => true,
        'storage_dir' => '/var/www/html/KUZAI/storage/uploads',
        'max_file_bytes' => 2097152,
        'max_text_chars_per_file' => 20000,
        'max_files_per_request' => 3,
        'allowed_extensions' => [
            'txt',
            'md',
            'log',
            'csv',
            'json',
            'xml',
            'yaml',
            'yml',
            'php',
            'py',
            'js',
            'css',
            'html',
            'htm',
            'sh',
            'conf',
            'ini',
            'service',
        ],
    ],
];
