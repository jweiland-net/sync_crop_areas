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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        // Do nothing, if this request has no file relations
        if (!$this->requestHasRelationsToFiles($dataHandler)) {
            return;
        }

        foreach ($dataHandler->datamap as $table => $records) {
            if ($this->isDisallowedTable($table)) {
                continue;
            }

            foreach ($this->tcaHelper->getColumnsWithFileReferences($table) as $column) {
                foreach ($records as $uid => $record) {
                    $sysFileReferenceRecords = $this->getSysFileReferenceRecordsForColumn(
                        $column,
                        (string)$uid,
                        $table,
                        $dataHandler
                    );
                    if ($sysFileReferenceRecords === []) {
                        continue;
                    }

                    foreach ($sysFileReferenceRecords as $sysFileReferenceRecord) {
                        try {
                            $updatedSysFileReferenceRecord = $this->updateCropVariantsService->synchronizeCropVariants(
                                $sysFileReferenceRecord
                            );
                        } catch (InvalidConfigurationException $invalidConfigurationException) {
                            continue;
                        }

                        if ($updatedSysFileReferenceRecord === []) {
                            continue;
                        }

                        if ($sysFileReferenceRecord !== $updatedSysFileReferenceRecord) {
                            $this->updateSysFileReferenceRecord($updatedSysFileReferenceRecord);
                        }
                    }
                }
            }
        }
    }

    protected function requestHasRelationsToFiles(DataHandler $dataHandler): bool
    {
        return array_key_exists('sys_file_reference', $dataHandler->datamap);
    }

    /**
     * We need records which have a relation to FAL (tt_content, pages, tx_*) and not FAL internal.
     */
    protected function isDisallowedTable(string $table): bool
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

        return in_array($table, $disallowedTables, true);
    }

    protected function getSysFileReferenceRecordsForColumn(
        string $column,
        string $id,
        string $table,
        DataHandler $dataHandler
    ): array {
        try {
            $csvListOfIdentifiers = (string)ArrayUtility::getValueByPath(
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

        $sysFileReferenceIdentifiers = array_map(static function ($uid) use ($dataHandler): int {
            return array_key_exists($uid, $dataHandler->substNEWwithIDs)
                ? (int)$dataHandler->substNEWwithIDs[$uid]
                : (int)$uid;
        }, GeneralUtility::trimExplode(',', $csvListOfIdentifiers));

        $sysFileReferenceRecords = [];
        foreach ($sysFileReferenceIdentifiers as $sysFileReferenceIdentifier) {
            $sysFileReferenceRecord = BackendUtility::getRecord('sys_file_reference', $sysFileReferenceIdentifier);

            if ($sysFileReferenceRecord === []) {
                continue;
            }

            if ($sysFileReferenceRecord === null) {
                continue;
            }

            if (empty($sysFileReferenceRecord['crop'])) {
                continue;
            }

            if (empty($sysFileReferenceRecord['sync_crop_area'])) {
                continue;
            }

            $sysFileReferenceRecords[(int)$sysFileReferenceRecord['uid']] = $sysFileReferenceRecord;
        }

        return $sysFileReferenceRecords;
    }

    protected function updateSysFileReferenceRecord(array $sysFileReferenceRecord): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        $connection->update(
            'sys_file_reference',
            [
                'crop' => $sysFileReferenceRecord['crop'],
            ],
            [
                'uid' => $sysFileReferenceRecord['uid'],
            ]
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
