<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Tests\Functional\Hook;

use JWeiland\SyncCropAreas\Helper\TcaHelper;
use JWeiland\SyncCropAreas\Hook\DataHandlerHook;
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Test case.
 */
class DataHandlerHookTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/sync_crop_areas',
    ];

    protected DataHandlerHook $subject;

    /**
     * @var UpdateCropVariantsService|MockObject
     */
    protected $updateCropVariantsServiceMock;

    /**
     * @var TcaHelper|MockObject
     */
    protected $tcaHelperMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updateCropVariantsServiceMock = $this->createMock(UpdateCropVariantsService::class);
        $this->tcaHelperMock = $this->createMock(TcaHelper::class);

        $this->subject = new DataHandlerHook(
            $this->updateCropVariantsServiceMock,
            $this->tcaHelperMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->updateCropVariantsServiceMock,
            $this->tcaHelperMock
        );

        parent::tearDown();
    }

    /**
     * @test
     */
    public function hookWithEmptyDatamapWillNotProcessAnything(): void
    {
        $this->tcaHelperMock
            ->expects(self::never())
            ->method('getColumnsWithFileReferences');

        /** @var DataHandler|MockObject $dataHandlerMock */
        $dataHandlerMock = $this->createMock(DataHandler::class);

        /** @var DataHandler $dataHandler */
        $dataHandler = $dataHandlerMock;
        $dataHandler->datamap = [];

        $this->subject->processDatamap_afterAllOperations($dataHandler);
    }

    /**
     * @test
     */
    public function hookWithoutSysFileReferenceWillNotProcessAnything(): void
    {
        $this->tcaHelperMock
            ->expects(self::never())
            ->method('getColumnsWithFileReferences');

        /** @var DataHandler|MockObject $dataHandlerMock */
        $dataHandlerMock = $this->createMock(DataHandler::class);

        /** @var DataHandler $dataHandler */
        $dataHandler = $dataHandlerMock;
        $dataHandler->datamap = [
            'tt_content' => [
                1 => [
                    'pid' => 12,
                ],
            ],
        ];

        $this->subject->processDatamap_afterAllOperations($dataHandler);
    }

    public function dataProviderForInvalidFileTables(): array
    {
        return [
            'Do not process sys_file records' => ['sys_file'],
            'Do not process sys_filemounts records' => ['sys_filemounts'],
            'Do not process sys_file_collection records' => ['sys_file_collection'],
            'Do not process sys_file_metadata records' => ['sys_file_metadata'],
            'Do not process sys_file_processedfile records' => ['sys_file_processedfile'],
            'Do not process sys_file_reference records' => ['sys_file_reference'],
            'Do not process sys_file_storage records' => ['sys_file_storage'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForInvalidFileTables
     */
    public function hookWithOnlyFileTablesWillNotProcessAnything(string $invalidTable): void
    {
        $this->tcaHelperMock
            ->expects(self::never())
            ->method('getColumnsWithFileReferences');

        /** @var DataHandler|MockObject $dataHandlerMock */
        $dataHandlerMock = $this->createMock(DataHandler::class);

        /** @var DataHandler $dataHandler */
        $dataHandler = $dataHandlerMock;
        $dataHandler->datamap = [
            $invalidTable => [
                1 => [
                    'pid' => 12,
                ],
            ],
        ];

        $this->subject->processDatamap_afterAllOperations($dataHandler);
    }

    /**
     * @test
     */
    public function hookWillUpdateSysFileReferenceRecords(): void
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/tt_content.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/sys_file_reference.xml');

        $this->tcaHelperMock
            ->expects(self::atLeastOnce())
            ->method('getColumnsWithFileReferences')
            ->with(self::identicalTo('tt_content'))
            ->willReturn(['image']);

        $this->updateCropVariantsServiceMock
            ->expects(self::exactly(3))
            ->method('synchronizeCropVariants')
            ->willReturnCallback(static function (array $sysFileReferenceRecord) {
                self::assertArrayHasKey('uid', $sysFileReferenceRecord);
                $sysFileReferenceRecord['crop'] = '{foo: "bar"}';
                return $sysFileReferenceRecord;
            });

        /** @var DataHandler|MockObject $dataHandlerMock */
        $dataHandlerMock = $this->createMock(DataHandler::class);

        /** @var DataHandler $dataHandler */
        $dataHandler = $dataHandlerMock;
        $dataHandler->datamap = [
            'sys_file_reference' => [
                1 => [
                    'hidden' => 0,
                ],
                2 => [
                    'hidden' => 0,
                ],
                3 => [
                    'hidden' => 0,
                ],
            ],
            'tt_content' => [
                1 => [
                    'image' => '1',
                ],
                2 => [
                    'image' => '2,3',
                ],
            ],
        ];

        $this->subject->processDatamap_afterAllOperations($dataHandler);

        $statement = $this->getDatabaseConnection()->select('*', 'sys_file_reference', '1=1');
        while ($updatedRecord = $statement->fetch()) {
            self::assertSame(
                '{foo: "bar"}',
                $updatedRecord['crop']
            );
        }
    }
}
