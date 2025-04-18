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
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
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

    /**
     * @var UpdateCropVariantsService|MockObject
     */
    protected $updateCropVariantsServiceMock;

    /**
     * @var InputInterface|MockObject
     */
    protected $inputMock;

    /**
     * @var OutputInterface|MockObject
     */
    protected $outputMock;

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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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
}
