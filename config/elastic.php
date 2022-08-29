<?php

return [
    'client' => [
        'hosts' => [
            env('ELASTIC_HOST', 'localhost:9200'),
        ],
    ],
    'update_mapping' => env('ELASTIC_UPDATE_MAPPING', true),
    'indexer' => env('ELASTIC_INDEXER', 'single'),
    'document_refresh' => env('ELASTIC_DOCUMENT_REFRESH'),
];
