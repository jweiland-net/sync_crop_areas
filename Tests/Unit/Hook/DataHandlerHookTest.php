<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Tests\Unit\Hook;

use JWeiland\SyncCropAreas\Hook\DataHandlerHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Test case.
 */
class DataHandlerHookTest extends UnitTestCase
{
    /**
     * @var DataHandlerHook
     */
    protected $subject;

    /**
     * @var DataHandler|ObjectProphecy
     */
    protected $dataHandlerProphecy;

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
            'selectedRatio' => '4:3',
        ],
    ];

    public function setUp(): void
    {
        $this->dataHandlerProphecy = $this->prophesize(DataHandler::class);

        $this->subject = new DataHandlerHook();
    }

    public function tearDown(): void
    {
        unset(
            $this->subject,
            $this->dataHandlerProphecy
        );
    }

    /**
     * @test
     */
    public function processDatamapWithDeactivatedFeatureWillNotChangeFieldArray(): void
    {
        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($this->crop),
            'sync_crop_area' => 0
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithWrongTableWillNotChangeFieldArray(): void
    {
        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($this->crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptyCropWillNotChangeFieldArray(): void
    {
        $incomingFieldArray = $expectedFieldArray = [
            'crop' => [],
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithMissingSelectedRatioWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['desktop']['selectedRatio']);

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptySelectedRatioWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['desktop']['selectedRatio'] = '';

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithMissingCropAreaWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['desktop']['cropArea']);

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithEmptyCropAreaWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['desktop']['cropArea'] = [];

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithOneCropVariantWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        unset($crop['mobile']);

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'tt_content',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWithNonMatchingSelectedRatiosWillNotChangeFieldArray(): void
    {
        $crop = $this->crop;
        $crop['mobile']['selectedRatio'] = '16:9';

        $incomingFieldArray = $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'sys_file_reference',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }

    /**
     * @test
     */
    public function processDatamapWillChangeFieldArray(): void
    {
        $incomingFieldArray = [
            'crop' => json_encode($this->crop),
            'sync_crop_area' => 1
        ];

        $crop = $this->crop;
        $crop['mobile']['cropArea'] = $this->crop['desktop']['cropArea'];
        $expectedFieldArray = [
            'crop' => json_encode($crop),
            'sync_crop_area' => 1
        ];

        $this->subject->processDatamap_preProcessFieldArray(
            $incomingFieldArray,
            'sys_file_reference',
            123,
            $this->dataHandlerProphecy->reveal()
        );

        self::assertSame($expectedFieldArray, $incomingFieldArray);
    }
}
