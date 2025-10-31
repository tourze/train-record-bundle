<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\FaceDetect;

/**
 * @internal
 */
#[CoversClass(FaceDetect::class)]
#[RunTestsInSeparateProcesses]
final class FaceDetectTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new FaceDetect();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'imageData' => ['imageData', 'base64-encoded-image-data'];
        yield 'confidence' => ['confidence', '0.95'];
        yield 'similarity' => ['similarity', '0.88'];
        yield 'detectResult' => ['detectResult', ['faces' => 1, 'landmarks' => []]];
        yield 'errorMessage' => ['errorMessage', 'Face detection failed'];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
    }

    public function testIsVerifiedProperty(): void
    {
        $entity = new FaceDetect();
        $entity->setIsVerified(true);
        $this->assertTrue($entity->isVerified());

        $entity->setIsVerified(false);
        $this->assertFalse($entity->isVerified());
    }

    public function testConfidenceBoundaryValues(): void
    {
        $entity = new FaceDetect();

        // 测试边界值
        $entity->setConfidence('0.0');
        $this->assertEquals('0.0', $entity->getConfidence());

        $entity->setConfidence('1.0');
        $this->assertEquals('1.0', $entity->getConfidence());

        // 测试小数精度
        $entity->setConfidence('0.9876');
        $this->assertEquals('0.9876', $entity->getConfidence());
    }

    public function testSimilarityBoundaryValues(): void
    {
        $entity = new FaceDetect();

        // 测试边界值
        $entity->setSimilarity('0.0');
        $this->assertEquals('0.0', $entity->getSimilarity());

        $entity->setSimilarity('1.0');
        $this->assertEquals('1.0', $entity->getSimilarity());

        // 测试高精度相似度
        $entity->setSimilarity('0.99999');
        $this->assertEquals('0.99999', $entity->getSimilarity());
    }

    public function testDetectResultComplexData(): void
    {
        $entity = new FaceDetect();

        // 测试复杂的检测结果数据
        $complexResult = [
            'faces' => [
                [
                    'bbox' => [100, 150, 200, 300],
                    'landmarks' => [
                        'left_eye' => [120, 180],
                        'right_eye' => [180, 180],
                        'nose' => [150, 200],
                        'mouth' => [150, 220],
                    ],
                    'confidence' => 0.95,
                ],
            ],
            'total_faces' => 1,
            'processing_time' => 0.234,
        ];

        $entity->setDetectResult($complexResult);
        $this->assertEquals($complexResult, $entity->getDetectResult());
        $this->assertIsArray($entity->getDetectResult());
        $this->assertArrayHasKey('faces', $entity->getDetectResult());
        $this->assertEquals(1, $entity->getDetectResult()['total_faces']);
    }

    public function testErrorMessageHandling(): void
    {
        $entity = new FaceDetect();

        // 测试空错误消息
        $entity->setErrorMessage(null);
        $this->assertNull($entity->getErrorMessage());

        // 测试长错误消息
        $longError = str_repeat('Error occurred during face detection processing. ', 10);
        $entity->setErrorMessage($longError);
        $this->assertEquals($longError, $entity->getErrorMessage());

        // 测试特殊字符
        $specialError = 'Error: 无法检测人脸 - 图像质量太差 😞';
        $entity->setErrorMessage($specialError);
        $this->assertEquals($specialError, $entity->getErrorMessage());
    }

    public function testImageDataHandling(): void
    {
        $entity = new FaceDetect();

        // 测试base64格式的图像数据
        $base64Data = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQE=';
        $entity->setImageData($base64Data);
        $this->assertEquals($base64Data, $entity->getImageData());
        $imageData = $entity->getImageData();
        $this->assertNotNull($imageData);
        $this->assertStringStartsWith('data:image/', $imageData);
        $this->assertStringContainsString('base64,', $imageData);

        // 测试纯base64数据
        $pureBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $entity->setImageData($pureBase64);
        $this->assertEquals($pureBase64, $entity->getImageData());
    }

    public function testDefaultValues(): void
    {
        $entity = new FaceDetect();

        // 测试默认值
        $this->assertNull($entity->getImageData());
        $this->assertNull($entity->getConfidence());
        $this->assertNull($entity->getSimilarity());
        $this->assertNull($entity->getDetectResult());
        $this->assertNull($entity->getErrorMessage());
        $this->assertFalse($entity->isVerified());
    }
}
