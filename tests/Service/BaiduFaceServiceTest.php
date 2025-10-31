<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Service\BaiduFaceService;

/**
 * @internal
 */
#[CoversClass(BaiduFaceService::class)]
#[RunTestsInSeparateProcesses]
final class BaiduFaceServiceTest extends AbstractIntegrationTestCase
{
    private BaiduFaceService $baiduFaceService;

    private HttpClientInterface&MockObject $mockHttpClient;

    protected function onSetUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        // 设置环境变量用于测试
        $_ENV['BAIDU_API_KEY'] = 'test-api-key';
        $_ENV['BAIDU_SECRET_KEY'] = 'test-secret-key';

        // 将Mock依赖注入到容器中
        self::getContainer()->set(HttpClientInterface::class, $this->mockHttpClient);
        self::getContainer()->set(LoggerInterface::class, $mockLogger);

        // 从容器获取服务实例
        $this->baiduFaceService = self::getService(BaiduFaceService::class);
    }

    protected function onTearDown(): void
    {
        // 清理测试环境变量
        unset($_ENV['BAIDU_API_KEY'], $_ENV['BAIDU_SECRET_KEY']);
        parent::onTearDown();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BaiduFaceService::class, $this->baiduFaceService);
    }

    public function testDetectFaceWithMockResponse(): void
    {
        // 先模拟获取access token的响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 然后模拟人脸检测的响应
        $detectResponse = $this->createMock(ResponseInterface::class);
        $detectResponse->method('toArray')->willReturn([
            'error_code' => 222202,
            'error_msg' => 'pic not has face',
            'result' => null,
        ]);
        $detectResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $detectResponse)
        ;

        $fakeImageBase64 = 'invalid_base64_image_data';
        $result = $this->baiduFaceService->detectFace($fakeImageBase64);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error_code', $result);
        $this->assertArrayHasKey('error_msg', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(222202, $result['error_code']);
        $this->assertEquals('pic not has face', $result['error_msg']);
    }

    public function testCompareFaceWithMockResponse(): void
    {
        // 模拟access token响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟人脸比对响应
        $compareResponse = $this->createMock(ResponseInterface::class);
        $compareResponse->method('toArray')->willReturn([
            'error_code' => 0,
            'error_msg' => 'SUCCESS',
            'result' => ['score' => 85.5],
        ]);
        $compareResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $compareResponse)
        ;

        $fakeImageBase64_1 = 'valid_base64_image_data_1';
        $fakeImageBase64_2 = 'valid_base64_image_data_2';
        $result = $this->baiduFaceService->compareFace($fakeImageBase64_1, $fakeImageBase64_2);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error_code', $result);
        $this->assertArrayHasKey('error_msg', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(0, $result['error_code']);
        $resultData = $result['result'];
        $this->assertIsArray($resultData);
        $this->assertEquals(85.5, $resultData['score']);
    }

    public function testFaceverifyWithMockResponse(): void
    {
        // 模拟access token响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟人脸核身响应
        $verifyResponse = $this->createMock(ResponseInterface::class);
        $verifyResponse->method('toArray')->willReturn([
            'error_code' => 0,
            'error_msg' => 'SUCCESS',
            'result' => ['score' => 90.2],
        ]);
        $verifyResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $verifyResponse)
        ;

        $fakeImageBase64 = 'valid_base64_image_data';
        $fakeIdcard = '123456789012345678';
        $fakeName = 'Test User';
        $result = $this->baiduFaceService->faceverify($fakeImageBase64, $fakeIdcard, $fakeName);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error_code', $result);
        $this->assertArrayHasKey('error_msg', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(0, $result['error_code']);
        $resultData = $result['result'];
        $this->assertIsArray($resultData);
        $this->assertEquals(90.2, $resultData['score']);
    }

    public function testDetectFaceWithInvalidImageReturnsError(): void
    {
        // 模拟token响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟检测失败响应
        $detectResponse = $this->createMock(ResponseInterface::class);
        $detectResponse->method('toArray')->willReturn([
            'error_code' => 216109,
            'error_msg' => 'image decode failed',
            'result' => null,
        ]);
        $detectResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $detectResponse)
        ;

        $result = $this->baiduFaceService->detectFace('invalid_image');

        $this->assertIsArray($result);
        $this->assertEquals(216109, $result['error_code']);
    }

    public function testCompareFaceWithValidImagesReturnsScore(): void
    {
        // 模拟token响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟比对成功响应
        $compareResponse = $this->createMock(ResponseInterface::class);
        $compareResponse->method('toArray')->willReturn([
            'error_code' => 0,
            'error_msg' => 'SUCCESS',
            'result' => ['score' => 78.2],
        ]);
        $compareResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $compareResponse)
        ;

        $result = $this->baiduFaceService->compareFace('image1_base64', 'image2_base64');

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['error_code']);
        $resultData = $result['result'];
        $this->assertIsArray($resultData);
        $this->assertEquals(78.2, $resultData['score']);
    }

    public function testFaceverifyWithInvalidIdCardReturnsError(): void
    {
        // 模拟token响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'mock-access-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟验证失败响应
        $verifyResponse = $this->createMock(ResponseInterface::class);
        $verifyResponse->method('toArray')->willReturn([
            'error_code' => 216613,
            'error_msg' => 'id number format error',
            'result' => null,
        ]);
        $verifyResponse->method('getStatusCode')->willReturn(200);

        $this->mockHttpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $verifyResponse)
        ;

        $result = $this->baiduFaceService->faceverify('image_base64', 'invalid_id', 'Test Name');

        $this->assertIsArray($result);
        $this->assertEquals(216613, $result['error_code']);
    }

    public function testDetectFaceHandlesHttpException(): void
    {
        // 模拟获取token失败的情况
        $this->mockHttpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'))
        ;

        $result = $this->baiduFaceService->detectFace('test_image');

        $this->assertIsArray($result);
        $this->assertEquals(-1, $result['error_code']);
        $this->assertEquals('人脸检测服务异常', $result['error_msg']);
        $this->assertNull($result['result']);
    }

    public function testAccessTokenCaching(): void
    {
        // 模拟第一次获取token的响应
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('toArray')->willReturn([
            'access_token' => 'cached-token',
            'expires_in' => 3600,
        ]);
        $tokenResponse->method('getStatusCode')->willReturn(200);

        // 模拟两次API调用的响应
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willReturn([
            'error_code' => 0,
            'result' => ['face_num' => 1],
        ]);
        $apiResponse->method('getStatusCode')->willReturn(200);

        // 期望：第一次调用获取token + API，第二次调用只需API（token被缓存）
        $this->mockHttpClient->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $apiResponse, $apiResponse)
        ;

        // 第一次调用
        $this->baiduFaceService->detectFace('image1');
        // 第二次调用（应该使用缓存的token）
        $this->baiduFaceService->detectFace('image2');
    }
}
