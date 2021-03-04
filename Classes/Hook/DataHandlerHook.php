<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync_crop_areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Hook;

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
