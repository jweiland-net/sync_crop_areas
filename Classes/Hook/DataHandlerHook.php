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
 * Copy first found CropArea to all other CropVariants as long as selectedRatio matches
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
            // We need at least 2 CropVariants. With just 1 there is no target to copy something over ;-)
            $cropVariants = json_decode($incomingFieldArray['crop'], true) ?? [];
            if (is_array($cropVariants) && count($cropVariants) > 1) {
                $firstCropVariant = current($cropVariants);
                if ($this->isValidCropVariant($firstCropVariant)) {
                    foreach ($cropVariants as &$cropVariant) {
                        // Don't modify first CropVariant
                        if ($cropVariant === $firstCropVariant) {
                            continue;
                        }

                        if (
                            $this->isValidCropVariant($cropVariant)
                            && $cropVariant['selectedRatio'] === $firstCropVariant['selectedRatio']
                        ) {
                            $cropVariant['cropArea'] = $firstCropVariant['cropArea'];
                        }
                    }
                    unset($cropVariant);
                    $incomingFieldArray['crop'] = json_encode($cropVariants);
                }
            }
        }
    }

    protected function isValidCropVariant(array $cropVariant): bool
    {
        return
            array_key_exists('cropArea', $cropVariant)
            && array_key_exists('selectedRatio', $cropVariant)
            && is_array($cropVariant['cropArea'])
            && !empty($cropVariant['cropArea'])
            && !empty($cropVariant['selectedRatio']);
    }
}
