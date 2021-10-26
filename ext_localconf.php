<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function(): void {
    // Copy first found CropVariant configuration to all others CropVariants
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \JWeiland\SyncCropAreas\Hook\DataHandlerHook::class;
});
