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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test case.
 */
class SynchronizeCropVariantsCommandTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/sync_crop_areas',
    ];

    protected SynchronizeCropVariantsCommand $subject;

    /**
     * @var UpdateCropVariantsService|ObjectProphecy
     */
    protected $updateCropVariantsServiceProphecy;

    /**
     * @var InputInterface|ObjectProphecy
     */
    protected $inputProphecy;

    /**
     * @var OutputInterface|ObjectProphecy
     */
    protected $outputProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateCropVariantsServiceProphecy = $this->prophesize(UpdateCropVariantsService::class);
        $this->inputProphecy = $this->prophesize(StringInput::class);
        $this->outputProphecy = $this->prophesize(ConsoleOutput::class);
        $this->outputProphecy
            ->writeln('Start synchronizing crop variants of table sys_file_reference')
            ->shouldBeCalled();

        $this->subject = new SynchronizeCropVariantsCommand(
            $this->updateCropVariantsServiceProphecy->reveal(),
            \JWeiland\SyncCropAreas\Command\SynchronizeCropVariantsCommand::class
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->updateCropVariantsServiceProphecy
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function runWithNoSysFileReferencesDisplaysEmptyStatistic(): void
    {
        $this->outputProphecy
            ->writeln('We had 0 sys_file_reference records in total. 0 records were processed successfully and 0 records must be skipped because of invalid values')
            ->shouldBeCalled();

        $this->subject->run(
            $this->inputProphecy->reveal(),
            $this->outputProphecy->reveal()
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

        $this->outputProphecy
            ->writeln('SKIP: Column "crop" of sys_file_reference record with UID 1 is empty')
            ->shouldBeCalled();
        $this->outputProphecy
            ->writeln('We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values')
            ->shouldBeCalled();

        $this->subject->run(
            $this->inputProphecy->reveal(),
            $this->outputProphecy->reveal()
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

        $this->outputProphecy
            ->writeln('SKIP: Column "pid" of sys_file_reference record with UID 1 is empty')
            ->shouldBeCalled();
        $this->outputProphecy
            ->writeln('We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values')
            ->shouldBeCalled();

        $this->subject->run(
            $this->inputProphecy->reveal(),
            $this->outputProphecy->reveal()
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

        $this->outputProphecy
            ->writeln('SKIP: Column "crop" of table "sys_file_reference" with UID 1 because it is unchanged, empty or invalid JSON')
            ->shouldBeCalled();
        $this->outputProphecy
            ->writeln('We had 1 sys_file_reference records in total. 0 records were processed successfully and 1 records must be skipped because of invalid values')
            ->shouldBeCalled();

        $this->updateCropVariantsServiceProphecy
            ->synchronizeCropVariants(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn($sysFileReferenceRecord);

        $this->subject->run(
            $this->inputProphecy->reveal(),
            $this->outputProphecy->reveal()
        );
    }

    /**
     * @test
     */
    public function runWithChangedCropWillUpdateRecord(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tt_content.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_file_reference.xml');

        $this->outputProphecy
            ->writeln('We had 3 sys_file_reference records in total. 3 records were processed successfully and 0 records must be skipped because of invalid values')
            ->shouldBeCalled();

        $this->updateCropVariantsServiceProphecy
            ->synchronizeCropVariants(Argument::cetera())
            ->shouldBeCalled()
            ->willReturn(['crop' => '{foo: "bar"}']);

        $this->subject->run(
            $this->inputProphecy->reveal(),
            $this->outputProphecy->reveal()
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
