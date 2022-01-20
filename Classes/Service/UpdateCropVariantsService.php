<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Service;

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Use this service to synchronize first found cropVariants to the other defined cropVariants
 */
class UpdateCropVariantsService
{
    /**
     * Copy first found CropArea to all other CropVariants as long as selectedRatio matches
     *
     * @param string $cropVariantsAsJSON The JSON string from column "crop" of sys_file_reference
     * @param int $pageUid The pageUid is needed to extract the PageTSConfig to merge cropVariants from TCA and PageTSConfig
     * @return string The new cropVariants in JSON format
     */
    public function synchronizeCropVariants(string $cropVariantsAsJSON, int $pageUid): string
    {
        // We need at least 2 CropVariants. With just 1 there is no target to copy something over ;-)
        $cropVariants = json_decode($cropVariantsAsJSON, true) ?? [];
        if (is_array($cropVariants) && count($cropVariants) > 1) {
            $firstCropVariant = current($cropVariants);
            if ($this->isValidCropVariant($firstCropVariant)) {
                foreach ($cropVariants as $cropVariantName => &$cropVariant) {
                    // Don't modify first CropVariant
                    if ($cropVariant === $firstCropVariant) {
                        continue;
                    }

                    if (
                        $this->isValidCropVariant($cropVariant)
                        && $this->isSelectedRatioAvailableInForeignCropVariant(
                            $cropVariantName,
                            $firstCropVariant['selectedRatio'],
                            $pageUid
                        )
                    ) {
                        $cropVariant['selectedRatio'] = $firstCropVariant['selectedRatio'];
                        $cropVariant['cropArea'] = $firstCropVariant['cropArea'];
                    }
                }

                unset($cropVariant);
                $cropVariantsAsJSON = json_encode($cropVariants);
            }
        }

        return $cropVariantsAsJSON;
    }

    /**
     * Test, if $selectedRatio is available in CropVariant named $cropVariantName
     */
    protected function isSelectedRatioAvailableInForeignCropVariant(
        string $cropVariantName,
        string $selectedRatio,
        int $pageUid
    ): bool {
        return in_array($selectedRatio, $this->getAllowedAspectRatiosForCropVariant($cropVariantName, $pageUid), true);
    }

    /**
     * Return allowed aspect ratios from merged (TCA and TCEFORM) config of cropVariants by name
     *
     * @return array[]
     */
    protected function getAllowedAspectRatiosForCropVariant(string $cropVariantName, int $pageUid): array
    {
        $cropVariants = $this->getMergedCropVariants($pageUid);
        if (!array_key_exists($cropVariantName, $cropVariants)) {
            return [];
        }
        if (!array_key_exists('allowedAspectRatios', $cropVariants[$cropVariantName])) {
            return [];
        }
        if (!is_array($cropVariants[$cropVariantName]['allowedAspectRatios'])) {
            return [];
        }
        return array_keys($cropVariants[$cropVariantName]['allowedAspectRatios']);
    }

    /**
     * Return merged (TCA and TCEFORM) config for "cropVariants"
     *
     * @return array[]
     */
    protected function getMergedCropVariants(int $pageUid): array
    {
        $mergedCropVariants = [];

        $fieldConfig = $this->getMergedFieldConfig('sys_file_reference', 'crop', $pageUid);
        if (
            isset($fieldConfig['cropVariants'])
            && is_array($fieldConfig['cropVariants'])
        ) {
            $mergedCropVariants = $fieldConfig['cropVariants'];
        }

        return $mergedCropVariants;
    }

    /**
     * Returns merged field config from TCA with field config from TCEFORM
     *
     * @return array[]
     */
    protected function getMergedFieldConfig(string $table, string $field, int $pageUid): array
    {
        $tcaConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? [];
        $pagesTsConfig = BackendUtility::getPagesTSconfig($pageUid);
        $fieldTsConfig = $pagesTsConfig['TCEFORM.'][$table . '.'][$field . '.'] ?? [];

        return FormEngineUtility::overrideFieldConf($tcaConfig, $fieldTsConfig);
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
