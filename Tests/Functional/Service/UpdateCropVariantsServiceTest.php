<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Tests\Functional\Service;

use JWeiland\SyncCropAreas\Helper\TcaHelper;
use JWeiland\SyncCropAreas\Service\UpdateCropVariantsService;
use JWeiland\SyncCropAreas\Tests\Functional\Traits\FrontendSiteTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case.
 */
class UpdateCropVariantsServiceTest extends FunctionalTestCase
{
    use FrontendSiteTrait;

    protected array $testExtensionsToLoad = [
        'typo3conf/ext/sync_crop_areas',
    ];

    protected UpdateCropVariantsService $subject;

    protected array $crop = [
        'desktop' => [
            'cropArea' => [
                'x' => 0,
                'y' => 0,
                'width' => 1,
                'height' => 1,
            ],
            'selectedRatio' => '4:3',
            'focusArea' => null,
        ],
        'mobile' => [
            'cropArea' => [
                'x' => 0.3,
                'y' => 0.1,
                'width' => 0.9,
                'height' => 0.85,
            ],
            'selectedRatio' => '16:9',
            'focusArea' => null,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_reference.csv');

        $this->setUpFrontendRootPage(1);
        $this->setUpFrontendSite(1);

        $this->activateTcaCropVariantsForSysFileReference();

        $this->subject = new UpdateCropVariantsService(
            new TcaHelper()
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );

        parent::tearDown();
    }

    protected function activateTcaCropVariantsForSysFileReference(): void
    {
        $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] = [
            'desktop' => [
                'title' => 'Desktop',
                'allowedAspectRatios' => [
                    '4:3' => [
                        'title' => '4 zu 3',
                        'value' => 4 / 3,
                    ],
                    'NaN' => [
                        'title' => 'Free',
                        'value' => 0.0,
                    ],
                ],
            ],
            'mobile' => [
                'title' => 'Mobile',
                'allowedAspectRatios' => [
                    '16:9' => [
                        'title' => '16 zu 9',
                        'value' => 16 / 9,
                    ],
                    'NaN' => [
                        'title' => 'Free',
                        'value' => 0.0,
                    ],
                ],
            ],
        ];
    }

    protected function activateTcaCropVariantsForTtContentWithoutTypes(): void
    {
        // Convert tt_content to a single type table
        unset($GLOBALS['TCA']['tt_content']['ctrl']['type']);
        unset($GLOBALS['TCA']['tt_content']['types']);
        $GLOBALS['TCA']['tt_content']['types'] = [
            '1' => [
                'columnsOverrides' => [
                    'image' => [
                        'config' => [
                            'overrideChildTca' => [
                                'columns' => [
                                    'crop' => [
                                        'config' => [
                                            'cropVariants' => [
                                                'desktop' => [
                                                    'title' => 'Desktop',
                                                    'allowedAspectRatios' => [
                                                        '4:3' => [
                                                            'title' => '4 zu 3',
                                                            'value' => 4 / 3,
                                                        ],
                                                        'NaN' => [
                                                            'title' => 'Free',
                                                            'value' => 0.0,
                                                        ],
                                                    ],
                                                ],
                                                'mobile' => [
                                                    'title' => 'Mobile',
                                                    'allowedAspectRatios' => [
                                                        '4:3' => [
                                                            'title' => '4 zu 3',
                                                            'value' => 4 / 3,
                                                        ],
                                                        'NaN' => [
                                                            'title' => 'Free',
                                                            'value' => 0.0,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function disableTcaCropVariants(): void
    {
        unset($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']);
    }

    /**
     * $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] is ignored since TYPO3 14.
     * Store the TSconfig in the root page record instead, which works for TYPO3 13 and 14.
     */
    protected function setPageTsConfig(string $pageTsConfig): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update('pages', ['TSconfig' => $pageTsConfig], ['uid' => 1]);
    }

    /**
     * Since TYPO3 14 each serialized CropVariant contains the key "excludeFromSync".
     * Compare the crop JSON semantically without this key to stay independent of the TYPO3 version.
     */
    protected static function assertSysFileReferenceEquals(array $expected, array $actual): void
    {
        $normalize = static function (array $sysFileReference): array {
            if (is_string($sysFileReference['crop'] ?? null)) {
                $crop = json_decode($sysFileReference['crop'], true, 512, JSON_THROW_ON_ERROR);
                foreach ($crop as &$cropVariant) {
                    unset($cropVariant['excludeFromSync']);
                }
                unset($cropVariant);
                $sysFileReference['crop'] = $crop;
            }
            return $sysFileReference;
        };

        self::assertSame($normalize($expected), $normalize($actual));
    }

    protected function activatePageTsConfigCropVariants(string $additionalPageTsConfig = ''): void
    {
        $this->setPageTsConfig('
TCEFORM.sys_file_reference.crop.config.cropVariants.desktop {
  title = default
  selectedRatio = NaN
  allowedAspectRatios {
    NaN {
      title = free
      value = 0.0
    }
    4:3 {
      title = 4to3
      value = 1.3333333333
    }
    16:9 {
      title = 16to9
      value = 1.7777777778
    }
  }
}
TCEFORM.sys_file_reference.crop.config.cropVariants.tablet {
  title = tablet
  selectedRatio = NaN
  allowedAspectRatios {
    NaN {
      title = free
      value = 0.0
    }
    4:3 {
      title = 4to3
      value = 1.3333333333
    }
    16:9 {
      title = 16to9
      value = 1.7777777778
    }
  }
}
TCEFORM.sys_file_reference.crop.config.cropVariants.smartphone {
  title = smartphone
  selectedRatio = NaN
  allowedAspectRatios {
    NaN {
      title = free
      value = 0.0
    }
    4:3 {
      title = 4to3
      value = 1.3333333333
    }
    16:9 {
      title = 16to9
      value = 1.7777777778
    }
  }
}
' . $additionalPageTsConfig);
    }

    #[Test]
    public function synchronizeCropVariantsWithDeactivatedFeatureWillNotChangeRecord(): void
    {
        $sysFileReference = [
            'uid' => 1,
            'crop' => json_encode($this->crop, JSON_THROW_ON_ERROR),
            'sync_crop_area' => 0,
        ];

        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    public static function invalidSysFileReferenceDataProvider(): array
    {
        return [
            'Missing sync_crop_area column' => [['crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],
            'Empty sync_crop_area column' => [['sync_crop_area' => 0, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],

            'Missing crop column' => [['sync_crop_area' => 1, 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],
            'Empty crop column' => [['sync_crop_area' => 1, 'crop' => '', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],

            'Missing tablenames column' => [['sync_crop_area' => 1, 'crop' => '{}', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],
            'Empty tablenames column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => '', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 2]],

            'Missing fieldname column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'uid_foreign' => 1, 'pid' => 2]],
            'Empty fieldname column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => '', 'uid_foreign' => 1, 'pid' => 2]],

            'Missing uid_foreign column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'pid' => 2]],
            'Empty uid_foreign column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 0, 'pid' => 2]],

            'Missing pid column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1]],
            'Empty pid column' => [['sync_crop_area' => 1, 'crop' => '{}', 'tablenames' => 'a', 'fieldname' => 'b', 'uid_foreign' => 1, 'pid' => 0]],
        ];
    }

    #[Test]
    #[DataProvider('invalidSysFileReferenceDataProvider')]
    public function synchronizeCropVariantsWithInvalidRecordWillNotChangeRecord(array $sysFileReference): void
    {
        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWithOneCropVariantWillNotChangeFieldArray(): void
    {
        $this->activateTcaCropVariantsForSysFileReference();

        // Now we have just ONE CropVariant configuration
        unset($GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['mobile']);

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => '{"desktop":{"cropArea":{"x":0.017092203898050978,"y":0.029985007496251874,"width":0.36881559220389803,"height":0.36881559220389803},"selectedRatio":"3:2","focusArea":null}}',
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWithNonMatchingSelectedRatiosWillNotChangeFieldArray(): void
    {
        $this->activateTcaCropVariantsForSysFileReference();

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($this->crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillChangeFieldArrayForTcaDefinedCropVariants(): void
    {
        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = 'NaN';

        $this->activateTcaCropVariantsForSysFileReference();

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        $crop['mobile']['selectedRatio'] = 'NaN';
        $crop['mobile']['cropArea'] = $this->crop['desktop']['cropArea'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($crop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillChangeFieldArrayForPageTsConfigDefinedCropVariants(): void
    {
        $this->disableTcaCropVariants();
        $this->activatePageTsConfigCropVariants();

        $crop = $this->crop;
        $crop['tablet'] = $crop['mobile'];
        $crop['smartphone'] = $crop['mobile'];
        $crop['desktop']['selectedRatio'] = '16:9';
        unset($crop['mobile']);

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        $crop['tablet'] = $crop['desktop'];
        $crop['smartphone'] = $crop['desktop'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($crop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillChangeFieldArrayForMergedCropVariants(): void
    {
        $this->activateTcaCropVariantsForSysFileReference();
        $this->activatePageTsConfigCropVariants();

        // Crop contains just two CropVariants.
        // This test also checks, if the two new CropVariants were also added
        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($this->crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        $crop = $this->crop;
        $crop['tablet'] = $crop['desktop'];
        $crop['smartphone'] = $crop['desktop'];

        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($crop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWithoutCtrlRecordTypesWillChangeFieldArray(): void
    {
        $this->activateTcaCropVariantsForTtContentWithoutTypes();

        // Crop contains just two CropVariants.
        // This test also checks, if the two new CropVariants were also added
        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($this->crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        $crop = $this->crop;

        $crop['mobile'] = $crop['desktop'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($crop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWithAlreadySynchronizedCropWillNotChangeRecord(): void
    {
        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = 'NaN';
        $crop['mobile'] = $crop['desktop'];

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        // Nothing to synchronize: the stored crop JSON must not be re-serialized (TYPO3 14 would add new keys)
        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillNotChangeCropVariantExcludedFromSync(): void
    {
        $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants']['listview'] = [
            'title' => 'List view',
            'excludeFromSync' => true,
            'allowedAspectRatios' => [
                'NaN' => [
                    'title' => 'Free',
                    'value' => 0.0,
                ],
            ],
        ];

        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = 'NaN';
        $crop['listview'] = $this->crop['mobile'];
        $crop['listview']['selectedRatio'] = 'NaN';

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        // "mobile" gets synchronized, "listview" keeps its own crop area
        $expectedCrop = $crop;
        $expectedCrop['mobile']['selectedRatio'] = 'NaN';
        $expectedCrop['mobile']['cropArea'] = $crop['desktop']['cropArea'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($expectedCrop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillUseFirstCropVariantNotExcludedFromSyncAsSource(): void
    {
        $freeRatio = [
            'NaN' => [
                'title' => 'Free',
                'value' => 0.0,
            ],
        ];
        $GLOBALS['TCA']['sys_file_reference']['columns']['crop']['config']['cropVariants'] = [
            'listview' => [
                'title' => 'List view',
                'excludeFromSync' => true,
                'allowedAspectRatios' => $freeRatio,
            ],
            'desktop' => [
                'title' => 'Desktop',
                'allowedAspectRatios' => $freeRatio,
            ],
            'mobile' => [
                'title' => 'Mobile',
                'allowedAspectRatios' => $freeRatio,
            ],
        ];

        $crop = [
            'listview' => [
                'cropArea' => ['x' => 0.5, 'y' => 0.5, 'width' => 0.25, 'height' => 0.25],
                'selectedRatio' => 'NaN',
                'focusArea' => null,
            ],
            'desktop' => [
                'cropArea' => ['x' => 0.1, 'y' => 0.2, 'width' => 0.6, 'height' => 0.5],
                'selectedRatio' => 'NaN',
                'focusArea' => null,
            ],
            'mobile' => [
                'cropArea' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1],
                'selectedRatio' => 'NaN',
                'focusArea' => null,
            ],
        ];

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        // "desktop" is the source, as "listview" is excluded from synchronization
        $expectedCrop = $crop;
        $expectedCrop['mobile']['cropArea'] = $crop['desktop']['cropArea'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($expectedCrop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    #[Test]
    public function synchronizeCropVariantsWillRespectExcludeFromSyncOfPageTsConfig(): void
    {
        $this->disableTcaCropVariants();
        $this->activatePageTsConfigCropVariants(
            'TCEFORM.sys_file_reference.crop.config.cropVariants.smartphone.excludeFromSync = 1'
        );

        $crop = $this->crop;
        $crop['tablet'] = $crop['mobile'];
        $crop['smartphone'] = $crop['mobile'];
        $crop['desktop']['selectedRatio'] = '16:9';
        unset($crop['mobile']);

        $sysFileReference = [
            'sync_crop_area' => 1,
            'crop' => json_encode($crop, JSON_THROW_ON_ERROR),
            'tablenames' => 'tt_content',
            'fieldname' => 'image',
            'uid_foreign' => 1,
            'pid' => 1,
        ];

        // "tablet" gets synchronized, "smartphone" is excluded via TSconfig (string value "1")
        $expectedCrop = $crop;
        $expectedCrop['tablet'] = $crop['desktop'];
        $expectedSysFileReference = $sysFileReference;
        $expectedSysFileReference['crop'] = json_encode($expectedCrop, JSON_THROW_ON_ERROR);

        self::assertSysFileReferenceEquals(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }
}
