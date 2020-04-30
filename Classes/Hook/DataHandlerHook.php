<?php
declare(strict_types = 1);
namespace JWeiland\SyncCropAreas\Hook;

/*
 * This file is part of the sync_crop_areas project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Copy first found CropArea to all other CropVariants
 */
class DataHandlerHook
{
    /**
     * @param array $incomingFieldArray
     * @param string $table
     * @param int|string $id String, if NEW record
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        $id,
        DataHandler $dataHandler
    ): void {
        if (
            $table === 'sys_file_reference'
            && !empty($incomingFieldArray['crop'])
            && !empty($incomingFieldArray['sync_crop_area'])
        ) {
            $firstCropVariant = [];
            $cropVariants = json_decode($incomingFieldArray['crop'], true) ?? [];
            foreach ($cropVariants as $cropVariantName => &$cropVariant) {
                if (empty($firstCropVariant)) {
                    $firstCropVariant = $cropVariant;
                    continue;
                }
                if (!is_array($cropVariant['cropArea'])) {
                    continue;
                }
                $cropVariant['selectedRatio'] = $firstCropVariant['selectedRatio'];
                $cropVariant['cropArea'] = $firstCropVariant['cropArea'];
            }
            unset($cropVariant);
            $incomingFieldArray['crop'] = json_encode($cropVariants);
        }
    }
}
