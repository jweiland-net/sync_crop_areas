<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Command;

use Doctrine\DBAL\Driver\Statement;
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Use this service to synchronize first found cropVariants to the other defined cropVariants
 */
class SynchronizeCropVariantsCommand extends Command
{
    protected OutputInterface $output;

    protected UpdateCropVariantsService $updateCropVariantsService;

    /*
     * Will be called by DI, so please don't add extbase classes with inject methods here.
     */
    public function __construct(UpdateCropVariantsService $updateCropVariantsService, string $name = null)
    {
        parent::__construct($name);

        $this->updateCropVariantsService = $updateCropVariantsService;
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will synchronize all CropVariants of table sys_file_reference. ' .
            'It takes first found cropVariant and copies over the configuration to the other cropVariants. ' .
            'If a cropVariant does not exists, it will not be touched.'
        );
    }

    /*
     * Synchronize all CropVariants of table sys_file_reference
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $output->writeln('Start synchronizing crop variants of table sys_file_reference');
        [$counter, $processed, $skipped] = $this->synchronizeCropVariants();
        $output->writeln(sprintf(
            'We had %d sys_file_reference records in total. %d records were processed successfully and %d records must be skipped because of invalid values',
            $counter,
            $processed,
            $skipped
        ));

        return 0;
    }

    protected function synchronizeCropVariants(): array
    {
        $counter = 0;
        $processed = 0;
        $skipped = 0;
        $statement = $this->getStatementForSysFileReferences();
        while ($sysFileReferenceRecord = $statement->fetch()) {
            $counter++;
            if (empty($sysFileReferenceRecord['crop'])) {
                $this->output->writeln(sprintf(
                    'SKIP: Column "crop" of sys_file_reference record with UID %d is empty',
                    (int)$sysFileReferenceRecord['uid']
                ));
                $skipped++;
                continue;
            }

            if (empty($sysFileReferenceRecord['pid'])) {
                $this->output->writeln(sprintf(
                    'SKIP: Column "pid" of sys_file_reference record with UID %d is empty',
                    (int)$sysFileReferenceRecord['uid']
                ));
                $skipped++;
                continue;
            }

            $updatedSysFileReferenceRecord = $this->updateCropVariantsService->synchronizeCropVariants(
                $sysFileReferenceRecord
            );

            if ($updatedSysFileReferenceRecord === []) {
                continue;
            }

            if ($sysFileReferenceRecord['crop'] === $updatedSysFileReferenceRecord['crop']) {
                $this->output->writeln(sprintf(
                    'SKIP: Column "crop" of table "sys_file_reference" with UID %d because it is unchanged, empty or invalid JSON',
                    (int)$sysFileReferenceRecord['uid']
                ));
                $skipped++;
            } else {
                $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
                $connection->update(
                    'sys_file_reference',
                    [
                        'crop' => $updatedSysFileReferenceRecord['crop']
                    ],
                    [
                        'uid' => (int)$sysFileReferenceRecord['uid']
                    ]
                );
                $processed++;
            }
        }

        return [$counter, $processed, $skipped];
    }

    protected function getStatementForSysFileReferences(): Statement
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'sync_crop_area',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
