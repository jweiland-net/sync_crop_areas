<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function() {
    // Copy first found CropVariant configuration to all other CropVariants
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \JWeiland\SyncCropAreas\Hook\DataHandlerHook::class;
});
