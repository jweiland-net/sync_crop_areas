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

    protected function activatePageTsConfigCropVariants(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] = '
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
        ';
    }

    /**
     * @test
     */
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

    public function invalidSysFileReferenceDataProvider(): array
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

    /**
     * @test
     *
     * @dataProvider invalidSysFileReferenceDataProvider
     */
    public function synchronizeCropVariantsWithInvalidRecordWillNotChangeRecord(array $sysFileReference): void
    {
        self::assertSame(
            $sysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @tester
     */
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

        self::assertSame(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    /**
     * @test
     */
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

        self::assertSame(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    /**
     * @test
     */
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

        self::assertSame(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }

    /**
     * @test
     */
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

        self::assertSame(
            $expectedSysFileReference,
            $this->subject->synchronizeCropVariants($sysFileReference)
        );
    }
}
