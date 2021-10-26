<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Tests\Functional\Hook;

use JWeiland\SyncCropAreas\Hook\DataHandlerHook;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 */
class DataHandlerHookTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/sync_crop_areas'
    ];

    /**
     * @var DataHandlerHook
     */
    protected $subject;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * Cropping example with equal selectedRatio but different cropArea
     *
     * @var array
     */
    protected $crop = [
        'desktop' => [
            'cropArea' => [
                'x' => 0,
                'y' => 0,
                'width' => 1,
                'height' => 1
            ],
            'selectedRatio' => '4:3',
        ],
        'mobile' => [
            'cropArea' => [
                'x' => 0.3,
                'y' => 0.1,
                'width' => 0.9,
                'height' => 0.85
            ],
            'selectedRatio' => '16:9',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->activateTcaCropVariants();

        $this->dataHandler = $this->prophesize(DataHandler::class)->reveal();
        $this->dataHandler->checkValue_currentRecord = [
            'uid' => 123,
            'pid' => 53,
            'tstamp' => time(),
            'crdate' => time(),
            'cruser_id' => 1,
            'hidden' => 0,
            'deleted' => 0,
            'sys_language_uid' => 0,
            'uid_local' => 1,
            'uid_foreign' => 1,
            'tablenames' => 'tt_content',
            'fieldname' => 'media',
            'table_local' => 'sys_file'
        ];

        $this->subject = new DataHandlerHook();
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->dataHandler
        );

        parent::tearDown();
    }

    protected function activateTcaCropVariants(): void
    {
        $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] = [
            'desktop' => [
                'title' => 'Desktop',
                'allowedAspectRatios' => [
                    '4:3' => [
                        'title' => '4 zu 3',
                        'value' => 4 / 3
                    ],
                    'NaN' => [
                        'title' => 'Free',
                        'value' => 0.0
                    ],
                ],
            ],
            'mobile' => [
                'title' => 'Mobile',
                'allowedAspectRatios' => [
                    '16:9' => [
                        'title' => '16 zu 9',
                        'value' => 16 / 9
                    ],
                    'NaN' => [
                        'title' => 'Free',
                        'value' => 0.0
                    ],
                ],
            ],
        ];
    }

    protected function disableTcaCropVariants(): void
    {
        unset($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']);
    }

    protected function activatePageTsConfigCropVariants(): void
    {
        /** @var FrontendInterface|ObjectProphecy $runtimeCacheProphecy */
        $runtimeCacheProphecy = $this->prophesize(VariableFrontend::class);
        $runtimeCacheProphecy
            ->get('pagesTsConfigIdToHash53')
            ->willReturn('Id2Hash');
        $runtimeCacheProphecy
            ->get('pagesTsConfigHashToContentId2Hash')
            ->willReturn([
                'TCEFORM.' => [
                    'sys_file_reference.' => [
                        'crop.' => [
                            'config.' => [
                                'cropVariants.' => [
                                    'desktop.' => [
                                        'title' => 'default',
                                        'selectedRatio' => 'NaN',
                                        'allowedAspectRatios.' => [
                                            'NaN.' => [
                                                'title' => 'free',
                                                'value' => 0.0
                                            ],
                                            '4:3.' => [
                                                'title' => '4to3',
                                                'value' => 1.3333333333
                                            ],
                                            '16:9.' => [
                                                'title' => '16to9',
                                                'value' => 1.7777777778
                                            ],
                                        ]
                                    ],
                                    'tablet.' => [
                                        'title' => 'tablet',
                                        'selectedRatio' => 'NaN',
                                        'allowedAspectRatios.' => [
                                            'NaN.' => [
                                                'title' => 'free',
                                                'value' => 0.0
                                            ],
                                            '4:3.' => [
                                                'title' => '4to3',
                                                'value' => 1.3333333333
                                            ],
                                            '16:9.' => [
                                                'title' => '16to9',
                                                'value' => 1.7777777778
                                            ],
                                        ]
                                    ],
                                    'smartphone.' => [
                                        'title' => 'smartphone',
                                        'selectedRatio' => 'NaN',
                                        'allowedAspectRatios.' => [
                                            'NaN.' => [
                                                'title' => 'free',
                                                'value' => 0.0
                                            ],
                                            '4:3.' => [
                                                'title' => '4to3',
                                                'value' => 1.3333333333
                                            ],
                                            '16:9.' => [
                                                'title' => '16to9',
                                                'value' => 1.7777777778
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        /** @var CacheManager|ObjectProphecy $cacheManagerProphecy */
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy
            ->getCache('runtime')
            ->willReturn($runtimeCacheProphecy);

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
    }

    /**
     * @test
     */
    public function processDatamapWithDeactivatedFeatureWillNotChangeFieldArray(): void
    {
        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($this->crop),
            'sync_crop_area' => 0
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithWrongTableWillNotChangeFieldArray(): void
    {
        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($this->crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'tt_content',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptyCropWillNotChangeFieldArray(): void
    {
        $fieldArray = $expectedFieldArray = [
            'crop' => [],
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithMissingSelectedRatioWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['desktop']['selectedRatio']);

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptySelectedRatioWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = '';

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithMissingCropAreaWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['desktop']['cropArea']);

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptyCropAreaWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['desktop']['cropArea'] = [];

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithOneCropVariantWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['mobile']);

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithNonMatchingSelectedRatiosWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['mobile']['selectedRatio'] = '16:9';

        $fieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWillChangeFieldArrayForTcaDefinedCropVariants(): void
    {
        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = 'NaN';

        $fieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $crop['mobile']['selectedRatio'] = 'NaN';
        $crop['mobile']['cropArea'] = $this->crop['desktop']['cropArea'];
        $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWillChangeFieldArrayForPageTsConfigDefinedCropVariants(): void
    {
        $this->disableTcaCropVariants();
        $this->activatePageTsConfigCropVariants();

        $crop = $this->crop;
        $crop['smartphone'] = $crop['mobile'];
        $crop['tablet'] = $crop['mobile'];
        $crop['desktop']['selectedRatio'] = '16:9';
        unset($crop['mobile']);

        $fieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $crop['tablet'] = $crop['desktop'];
        $crop['smartphone'] = $crop['desktop'];
        $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_postProcessFieldArray(
            'update',
            'sys_file_reference',
            123,
            $fieldArray,
            $this->dataHandler
        );

        self::assertSame($expectedFieldArray, $fieldArray);
    }
}
