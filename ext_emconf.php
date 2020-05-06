<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Sync Crop Areas',
    'description' => 'Sync first found crop area to all other CropVariants',
    'version' => '1.0.2',
    'category' => 'plugin',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearCacheOnLoad' => true,
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
