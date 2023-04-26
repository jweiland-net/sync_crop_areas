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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test case.
 */
class SynchronizeCropVariantsCommandTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
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
            ->willReturnOnConsecutiveCalls([
                ['Start synchronizing crop variants of table sys_file_reference', null],
                ['We had 0 sys_file_reference records in total. 0 records were processed successfully and 0 records must be skipped because of invalid values', null],
            ]);

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
        $this->getDatabaseConnection()->insertArray(
            'sys_file_reference',
            [
                'uid' => 1,
                'pid' => 1,
                'crop' => '',
                'tstamp' => time(),
                'crdate' => time(),
                'cruser_id' => 1,
                'tablenames' => 'tt_content',
                'fieldname' => 'image',
                'uid_local' => 13,
                'uid_foreign' => 7,
            ]
        );

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnOnConsecutiveCalls([
                ['Start synchronizing crop variants of table sys_file_reference', null],
                ['SKIP: Column "crop" of sys_file_reference record with UID 1 is empty', null],
                ['We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values', null],
            ]);

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
        $this->getDatabaseConnection()->insertArray(
            'sys_file_reference',
            [
                'uid' => 1,
                'pid' => 0,
                'crop' => '{}',
                'tstamp' => time(),
                'crdate' => time(),
                'cruser_id' => 1,
                'tablenames' => 'tt_content',
                'fieldname' => 'image',
                'uid_local' => 13,
                'uid_foreign' => 7,
            ]
        );

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnOnConsecutiveCalls([
                ['Start synchronizing crop variants of table sys_file_reference', null],
                ['SKIP: Column "pid" of sys_file_reference record with UID 1 is empty', null],
                ['We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values', null],
            ]);

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
            'cruser_id' => 1,
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_local' => 13,
            'uid_foreign' => 7,
        ];

        $this->getDatabaseConnection()->insertArray('sys_file_reference', $sysFileReferenceRecord);

        $this->outputMock
            ->expects(self::exactly(3))
            ->method('writeln')
            ->willReturnOnConsecutiveCalls([
                ['Start synchronizing crop variants of table sys_file_reference', null],
                ['SKIP: Column "crop" of table "sys_file_reference" with UID 1 because it is unchanged, empty or invalid JSON', null],
                ['We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values', null],
            ]);

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
        $this->importDataSet(__DIR__ . '/../Fixtures/tt_content.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_file_reference.xml');

        $this->outputMock
            ->expects(self::exactly(2))
            ->method('writeln')
            ->willReturnOnConsecutiveCalls([
                ['Start synchronizing crop variants of table sys_file_reference', null],
                ['We had 3 sys_file_reference records in total. 3 records were processed successfully and 0 records must be skipped because of invalid values', null],
            ]);

        $this->updateCropVariantsServiceMock
            ->expects(self::atLeastOnce())
            ->method('synchronizeCropVariants')
            ->willReturn(['crop' => '{foo: "bar"}']);

        $this->subject->run(
            $this->inputMock,
            $this->outputMock
        );

        $statement = $this->getDatabaseConnection()->select('*', 'sys_file_reference', '1=1');
        while ($updatedRecord = $statement->fetch()) {
            self::assertSame(
                '{foo: "bar"}',
                $updatedRecord['crop']
            );
        }
    }
}
