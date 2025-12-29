<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary' => env('WKHTML_PDF_BINARY', '/usr/local/bin/wkhtmltopdf'),
        'timeout' => false,
        'temporary_folder' => '/var/services/tmp',
        'options' => [
            'enable-local-file-access' => true,
            'load-error-handling' => 'ignore',
            'disable-smart-shrinking' => true,
        ],
        'env' => [
            'QTWEBKIT_DRT_MODE' => '1',
        ],
    ],
    'image' => [
        'enabled' => true,
        'binary' => env('WKHTML_IMG_BINARY', '/usr/local/bin/wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env' => [],
    ],
];
