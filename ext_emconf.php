<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Sync Crop Areas',
    'description' => 'Sync first found crop area to all other CropVariants',
    'version' => '2.0.1',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.99.99',
            'typo3' => '10.4.19-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
