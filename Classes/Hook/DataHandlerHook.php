<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Hook;

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Copy first found CropArea to all other CropVariants as long as selectedRatio matches
 */
class DataHandlerHook
{
    /**
     * @param string $status
     * @param string $table
     * @param int|string $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ) {
        if (
            $table === 'sys_file_reference'
            && ($sysFileReferenceRecord = $this->getSysFileReferenceRecord($dataHandler, $fieldArray))
            && !empty($sysFileReferenceRecord['crop'])
            && !empty($sysFileReferenceRecord['sync_crop_area'])
        ) {
            [$pageUid] = BackendUtility::getTSCpid($table, $id, $sysFileReferenceRecord['pid'] ?? 0);
            // We need at least 2 CropVariants. With just 1 there is no target to copy something over ;-)
            $cropVariants = json_decode($sysFileReferenceRecord['crop'], true) ?? [];
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
                                (int)$pageUid
                            )
                        ) {
                            $cropVariant['selectedRatio'] = $firstCropVariant['selectedRatio'];
                            $cropVariant['cropArea'] = $firstCropVariant['cropArea'];
                        }
                    }
                    unset($cropVariant);
                    $fieldArray['crop'] = json_encode($cropVariants);
                }
            }
        }
    }

    /**
     * We can not use $fieldArray as $dataHandler->compareFieldArrayWithCurrentAndUnset has removed all equal
     * columns before Hook processDatamap_postProcessFieldArray was processed.
     * The only full record I have found was in $dataHandler->checkValue_currentRecord.
     * As checkValue_currentRecord is defined as public, it may happen that this property will be removed in
     * future TYPO3 versions. In that case this hook has to be moved to hook_processDatamap_afterDatabaseOperations.
     *
     * @param DataHandler $dataHandler Needed to get the full "old" DB record
     * @param array $fieldArray Needed to overwrite values in old DB record with the updated properties
     * @return array Returns full DB record with updated values
     */
    protected function getSysFileReferenceRecord(DataHandler $dataHandler, array $fieldArray): array
    {
        $fullDbRecordBeforeSave = $dataHandler->checkValue_currentRecord ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fullDbRecordBeforeSave, $fieldArray);

        return $fullDbRecordBeforeSave;
    }

    /**
     * Test, if $selectedRatio is available in CropVariant named $cropVariantName
     *
     * @param string $cropVariantName
     * @param string $selectedRatio
     * @param int $pageUid
     * @return bool
     */
    protected function isSelectedRatioAvailableInForeignCropVariant(string $cropVariantName, string $selectedRatio, int $pageUid): bool
    {
        return in_array($selectedRatio, $this->getAllowedAspectRatiosForCropVariant($cropVariantName, $pageUid), true);
    }

    protected function getAllowedAspectRatiosForCropVariant(string $cropVariantName, int $pageUid): array
    {
        $cropVariants = $this->getMergedCropVariants($pageUid);
        if (
            array_key_exists($cropVariantName, $cropVariants)
            && array_key_exists('allowedAspectRatios', $cropVariants[$cropVariantName])
            && is_array($cropVariants[$cropVariantName]['allowedAspectRatios'])
        ) {
            return array_keys($cropVariants[$cropVariantName]['allowedAspectRatios']);
        }

        return [];
    }

    /**
     * Return merged (TCA and TCEFORM) config for "cropVariants"
     *
     * @param int $pageUid
     * @return array
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
     * @param string $table
     * @param string $field
     * @param int $pageUid
     * @return array
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
