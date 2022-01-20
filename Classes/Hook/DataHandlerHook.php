<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Hook;

use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Copy first found CropArea to all other CropVariants as long as selectedRatio matches
 */
class DataHandlerHook
{
    /**
     * @var UpdateCropVariantsService
     */
    protected $updateCropVariantsService;

    public function __construct(UpdateCropVariantsService $updateCropVariantsService = null)
    {
        $this->updateCropVariantsService = $updateCropVariantsService ?? GeneralUtility::makeInstance(UpdateCropVariantsService::class);
    }

    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ): void {
        if (
            $table === 'sys_file_reference'
            && ($sysFileReferenceRecord = $this->getSysFileReferenceRecord($dataHandler, $fieldArray))
            && !empty($sysFileReferenceRecord['crop'])
            && !empty($sysFileReferenceRecord['sync_crop_area'])
        ) {
            [$pageUid] = BackendUtility::getTSCpid($table, $id, $sysFileReferenceRecord['pid'] ?? 0);

            $fieldArray['crop'] = $this->updateCropVariantsService->synchronizeCropVariants(
                $sysFileReferenceRecord['crop'],
                $pageUid
            );
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
     * @return string[]|int[] Returns full DB record with updated values
     */
    protected function getSysFileReferenceRecord(DataHandler $dataHandler, array $fieldArray): array
    {
        $fullDbRecordBeforeSave = $dataHandler->checkValue_currentRecord ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fullDbRecordBeforeSave, $fieldArray);

        return $fullDbRecordBeforeSave;
    }
}
