<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    $ll = 'LLL:EXT:sync_crop_areas/Resources/Private/Language/locallang_db.xlf:';

    $newSysFileReferenceColumns = [
        'sync_crop_area' => [
            'exclude' => 1,
            'label' => $ll . 'sys_file_reference.sync_crop_area',
            'description' => $ll . 'sys_file_reference.sync_crop_area.description',
            'config' => [
                'renderType' => 'checkboxToggle',
                'type' => 'check',
                'default' => 1,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'sys_file_reference',
        $newSysFileReferenceColumns
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
        'sys_file_reference',
        'imageoverlayPalette',
        '--linebreak--,sync_crop_area,--linebreak--',
        'before:crop'
    );
});
