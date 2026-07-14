<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'paths' => [
        'repos_dir' => '/srv/kuzai-git-rag/repos',
        'indexes_dir' => '/var/lib/kuzai-git-rag/indexes',
        'state_dir' => '/var/lib/kuzai-git-rag/state',
        'logs_dir' => '/var/log/kuzai-git-rag',
    ],

    'service' => [
        'endpoint' => 'http://127.0.0.1:8890',
        'timeout' => 120,
    ],

    'git' => [
        'auth_mode' => 'ssh',
        'clone_mode' => 'manual',
        'allow_status' => true,
        'allow_pull' => true,
        'allow_diff' => true,
        'allow_commit' => true,
        'allow_push' => true,
        'allow_force_push' => false,
        'single_active_branch' => true,
    ],

    'indexing' => [
        'scope' => 'full_text_repository',
        'max_file_bytes' => 1048576,
        'max_chunk_chars' => 5000,
        'chunk_overlap_chars' => 600,
        'exclude_dirs' => [
            '.git',
            'node_modules',
            'vendor',
            'venv',
            '.venv',
            '__pycache__',
            'dist',
            'build',
            'cache',
            '.cache',
        ],
        'exclude_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'ico',
            'pdf',
            'zip',
            'gz',
            'tar',
            '7z',
            'rar',
            'mp3',
            'wav',
            'mp4',
            'avi',
            'mov',
            'sqlite',
            'db',
            'onnx',
            'gguf',
        ],
    ],

    'embeddings' => [
        'mode' => 'api',
        'env_file' => '/etc/kuzai-git-rag/embedding.env',
        'provider' => 'openai_compatible',
        'endpoint' => '',
        'model' => '',
    ],

    'repos' => [
        /*
        Exemple à activer plus tard :

        'kuzai-local' => [
            'name' => 'kuzai-local',
            'path' => '/srv/kuzai-git-rag/repos/kuzai-local',
            'remote' => 'git@github.com:USER/REPO.git',
            'active_branch' => 'main',
            'enabled' => true,
        ],
        */
    ],
];
