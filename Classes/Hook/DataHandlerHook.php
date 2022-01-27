<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Hook;

use JWeiland\SyncCropAreas\Helper\TcaHelper;
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Copy first found CropArea to all other CropVariants as long as selectedRatio matches
 */
class DataHandlerHook
{
    protected UpdateCropVariantsService $updateCropVariantsService;

    protected TcaHelper $tcaHelper;

    public function __construct(UpdateCropVariantsService $updateCropVariantsService, TcaHelper $tcaHelper)
    {
        $this->updateCropVariantsService = $updateCropVariantsService;
        $this->tcaHelper = $tcaHelper;
    }

    /**
     * Be careful with $fieldArray. On UPDATE it just contains updated columns. All other columns were removed.
     * See DataHandler::compareFieldArrayWithCurrentAndUnset()
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        $id,
        array &$fieldArray,
        DataHandler $dataHandler
    ): void {
        // Do nothing, if this request has no file relations
        if (!$this->requestHasRelationsToFiles($dataHandler)) {
            return;
        }

        // Do nothing, if current $table is a FAL table
        if (!$this->isAllowedTable($table)) {
            return;
        }

        foreach ($this->tcaHelper->getColumnsWithFileReferences($table) as $column) {
            $sysFileReferenceRecords = $this->getSysFileReferenceRecordsForColumn(
                $column,
                (string)$id,
                $table,
                $dataHandler
            );
            if ($sysFileReferenceRecords === []) {
                continue;
            }

            foreach ($sysFileReferenceRecords as $sysFileReferenceRecord) {
                $sysFileReferenceRecord = $this->updateCropVariantsService->synchronizeCropVariants(
                    $sysFileReferenceRecord
                );
                $dataHandler->datamap['sys_file_reference'][$sysFileReferenceRecord['uid']]['crop']
                    = $sysFileReferenceRecord['crop'];
            }
        }
    }

    protected function requestHasRelationsToFiles(DataHandler $dataHandler): bool
    {
        return array_key_exists('sys_file_reference', $dataHandler->datamap);
    }

    protected function getCurrentFullRecord(DataHandler $dataHandler): array
    {
        return $dataHandler->checkValue_currentRecord;
    }

    /**
     * We need records which have a relation to FAL (tt_content, pages, tx_*) and not FAL internal.
     */
    protected function isAllowedTable(string $table): bool
    {
        $disallowedTables = [
            'sys_file',
            'sys_filemounts',
            'sys_file_collection',
            'sys_file_metadata',
            'sys_file_processedfile',
            'sys_file_reference',
            'sys_file_storage',
        ];

        return !in_array($table, $disallowedTables, true);
    }

    protected function getSysFileReferenceRecordsForColumn(
        string $column,
        string $id,
        string $table,
        DataHandler $dataHandler
    ): array {
        try {
            $uidValues = (string)ArrayUtility::getValueByPath(
                $dataHandler->datamap,
                sprintf(
                    '%s/%s/%s',
                    $table,
                    $id,
                    $column
                )
            );
        } catch (MissingArrayPathException $missingArrayPathException) {
            // Segment of path could not be found in array
            return [];
        } catch (\RuntimeException $runtimeException) {
            // $path is empty
            return [];
        } catch (\InvalidArgumentException $invalidArgumentException) {
            // $path is not string or array
            return [];
        }

        $sysFileReferenceRecords = [];
        foreach (GeneralUtility::trimExplode(',', $uidValues) as $sysFileReferenceUid) {
            $sysFileReferenceRecordFromDatabase = [];
            if (MathUtility::canBeInterpretedAsInteger($sysFileReferenceUid)) {
                $sysFileReferenceRecordFromDatabase = $this->getSysFileReferenceRecordByUid((int)$sysFileReferenceUid);
            }

            try {
                $sysFileReferenceRecordFromRequest = ArrayUtility::getValueByPath(
                    $dataHandler->datamap,
                    sprintf(
                        'sys_file_reference/%s',
                        $sysFileReferenceUid
                    )
                );
            } catch (MissingArrayPathException $missingArrayPathException) {
                // Segment of path could not be found in array
                continue;
            } catch (\RuntimeException $runtimeException) {
                // $path is empty
                continue;
            } catch (\InvalidArgumentException $invalidArgumentException) {
                // $path is not string or array
                continue;
            }

            ArrayUtility::mergeRecursiveWithOverrule(
                $sysFileReferenceRecordFromDatabase,
                $sysFileReferenceRecordFromRequest
            );

            if (empty($sysFileReferenceRecordFromDatabase['crop'])) {
                continue;
            }

            if (empty($sysFileReferenceRecordFromDatabase['sync_crop_area'])) {
                continue;
            }

            $sysFileReferenceRecords[$sysFileReferenceUid] = $sysFileReferenceRecordFromDatabase;
        }

        return $sysFileReferenceRecords;
    }

    protected function getSysFileReferenceRecordByUid(int $sysFileReferenceUid): array
    {
        return BackendUtility::getRecord('sys_file_reference', $sysFileReferenceUid) ?: [];
    }
}
