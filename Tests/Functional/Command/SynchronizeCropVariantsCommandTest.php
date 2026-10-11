<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Tests\Functional\Command;

use JWeiland\SyncCropAreas\Command\SynchronizeCropVariantsCommand;
use JWeiland\SyncCropAreas\Helper\TcaHelper;
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class SynchronizeCropVariantsCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/sync_crop_areas',
    ];

    protected SynchronizeCropVariantsCommand $subject;

    protected UpdateCropVariantsService|MockObject $updateCropVariantsServiceMock;

    protected InputInterface|MockObject $inputMock;

    protected OutputInterface|MockObject $outputMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateCropVariantsServiceMock = $this->createMock(UpdateCropVariantsService::class);
        $this->inputMock = $this->createMock(StringInput::class);
        $this->outputMock = $this->createMock(ConsoleOutput::class);

        $this->subject = new SynchronizeCropVariantsCommand(
            $this->updateCropVariantsServiceMock,
            \JWeiland\SyncCropAreas\Command\SynchronizeCropVariantsCommand::class
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->outputMock,
            $this->inputMock,
            $this->updateCropVariantsServiceMock
        );

        parent::tearDown();
    }

    #[Test]
    public function runWithNoSysFileReferencesDisplaysEmptyStatistic(): void
    {
        $this->outputMock
            ->expects(self::exactly(2))
            ->method('writeln')
            ->with(self::callback(function ($output) {
                static $calls = 0;
                $expectedOutputs = [
                    'Start synchronizing crop variants of table sys_file_reference',
                    'We had 0 sys_file_reference records in total. 0 records were processed successfully and 0 records must be skipped because of invalid values',
                ];

                // Assert each call matches the expected output
                $this->assertEquals($expectedOutputs[$calls], $output);
                $calls++;
                return true;
            }));

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );
    }

    #[Test]
    public function runWithEmptyCropColumnWillSkipRecord(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        $connection->insert(
            'sys_file_reference',
            [
                'uid' => 1,
                'pid' => 1,
                'crop' => '',
                'tstamp' => time(),
                'crdate' => time(),
                'tablenames' => 'tt_content',
                'fieldname' => 'image',
                'uid_local' => 13,
                'uid_foreign' => 7,
            ]
        );

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnCallback(function () {
                static $calls = 0;
                $responses = [
                    'Start synchronizing crop variants of table sys_file_reference',
                    'SKIP: Column "crop" of sys_file_reference record with UID 1 is empty',
                    'We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values',
                ];

                return $responses[$calls++];
            });

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );
    }

    #[Test]
    public function runWithEmptyPidColumnWillSkipRecord(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        $connection->insert(
            'sys_file_reference',
            [
                'uid' => 1,
                'pid' => 0,
                'crop' => '{}',
                'tstamp' => time(),
                'crdate' => time(),
                'tablenames' => 'tt_content',
                'fieldname' => 'image',
                'uid_local' => 13,
                'uid_foreign' => 7,
            ]
        );

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnCallback(function () {
                static $calls = 0;
                $responses = [
                    'Start synchronizing crop variants of table sys_file_reference',
                    'SKIP: Column "pid" of sys_file_reference record with UID 1 is empty',
                    'We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values',
                ];

                return $responses[$calls++];
            });

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );
    }

    #[Test]
    public function runWithUnchangedCropWillSkipRecord(): void
    {
        $sysFileReferenceRecord = [
            'uid' => 1,
            'pid' => 1,
            'crop' => '{}',
            'tstamp' => time(),
            'crdate' => time(),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_local' => 13,
            'uid_foreign' => 7,
        ];

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        $connection->insert(
            'sys_file_reference',
            $sysFileReferenceRecord
        );

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnCallback(function () {
                static $calls = 0;
                $responses = [
                    'Start synchronizing crop variants of table sys_file_reference',
                    'SKIP: Column "crop" of table "sys_file_reference" with UID 1 because it is unchanged, empty or invalid JSON',
                    'We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values',
                ];

                return $responses[$calls++];
            });

        $this->updateCropVariantsServiceMock
            ->expects(self::atLeastOnce())
            ->method('synchronizeCropVariants')
            ->willReturn($sysFileReferenceRecord);

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );
    }

    #[Test]
    public function runWithChangedCropWillUpdateRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_reference.csv');

        $this->outputMock
            ->expects(self::exactly(2))
            ->method('writeln')
            ->willReturnCallback(function () {
                static $calls = 0;
                $responses = [
                    'Start synchronizing crop variants of table sys_file_reference',
                    'We had 3 sys_file_reference records in total. 3 records were processed successfully and 0 records must be skipped because of invalid values',
                ];

                return $responses[$calls++];
            });

        $this->updateCropVariantsServiceMock
            ->expects(self::atLeastOnce())
            ->method('synchronizeCropVariants')
            ->willReturn(['crop' => '{foo: "bar"}']);

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        $statement = $connection->select(['*'], 'sys_file_reference');
        while ($updatedRecord = $statement->fetchAssociative()) {
            self::assertSame(
                '{foo: "bar"}',
                $updatedRecord['crop']
            );
        }
    }

    #[Test]
    public function commandIsRegisteredInCommandRegistry(): void
    {
        $commandRegistry = $this->get(CommandRegistry::class);

        self::assertTrue($commandRegistry->has('sync_crop_areas:sync'));
        self::assertInstanceOf(
            SynchronizeCropVariantsCommand::class,
            $commandRegistry->get('sync_crop_areas:sync')
        );
    }

    /**
     * Integration test without mocks: one record has to be synchronized, one is already in sync
     */
    #[Test]
    public function runWithRealServiceWillSynchronizeChangedRecordsOnly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');

        $freeRatio = [
            'NaN' => [
                'title' => 'Free',
                'value' => 0.0,
            ],
        ];
        $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] = [
            'desktop' => [
                'title' => 'Desktop',
                'allowedAspectRatios' => $freeRatio,
            ],
            'mobile' => [
                'title' => 'Mobile',
                'allowedAspectRatios' => $freeRatio,
            ],
        ];

        $desktopCropArea = ['x' => 0.1, 'y' => 0.2, 'width' => 0.5, 'height' => 0.4];
        $unsynchronizedCrop = json_encode([
            'desktop' => ['cropArea' => $desktopCropArea, 'selectedRatio' => 'NaN', 'focusArea' => null],
            'mobile' => ['cropArea' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1], 'selectedRatio' => 'NaN', 'focusArea' => null],
        ], JSON_THROW_ON_ERROR);
        $synchronizedCrop = json_encode([
            'desktop' => ['cropArea' => $desktopCropArea, 'selectedRatio' => 'NaN', 'focusArea' => null],
            'mobile' => ['cropArea' => $desktopCropArea, 'selectedRatio' => 'NaN', 'focusArea' => null],
        ], JSON_THROW_ON_ERROR);

        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file_reference');
        foreach ([1 => $unsynchronizedCrop, 2 => $synchronizedCrop] as $uid => $crop) {
            $connection->insert(
                'sys_file_reference',
                [
                    'uid' => $uid,
                    'pid' => 1,
                    'crop' => $crop,
                    'sync_crop_area' => 1,
                    'tablenames' => 'tt_content',
                    'fieldname' => 'image',
                    'uid_local' => $uid,
                    'uid_foreign' => $uid,
                ]
            );
        }

        $output = new BufferedOutput();
        $command = new SynchronizeCropVariantsCommand(new UpdateCropVariantsService(new TcaHelper()));
        $command->run(new ArrayInput([]), $output);

        self::assertStringContainsString(
            'We had 2 sys_file_reference records in total. 1 records were processed successfully and 1 records must be skipped',
            $output->fetch()
        );

        $records = [];
        $statement = $connection->select(['uid', 'crop'], 'sys_file_reference');
        while ($record = $statement->fetchAssociative()) {
            $records[(int)$record['uid']] = $record['crop'];
        }

        $updatedCrop = json_decode((string)$records[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($desktopCropArea, $updatedCrop['mobile']['cropArea']);

        // Already synchronized record is untouched (not re-serialized)
        self::assertSame($synchronizedCrop, $records[2]);
    }
}
