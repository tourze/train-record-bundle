<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\TrainRecordBundle\Exception\RuntimeException;

/**
 * 百度人脸识别服务
 *
 * 集成百度AI开放平台的人脸识别API
 */
#[WithMonologChannel(channel: 'train_record')]
class BaiduFaceService
{
    private string $apiKey;

    private string $secretKey;

    private string $accessToken = '';

    private int $tokenExpireTime = 0;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $apiKey = '',
        string $secretKey = '',
    ) {
        $envApiKey = $_ENV['BAIDU_API_KEY'] ?? '';
        assert(is_string($envApiKey), 'BAIDU_API_KEY must be a string');
        $this->apiKey = '' !== $apiKey ? $apiKey : $envApiKey;

        $envSecretKey = $_ENV['BAIDU_SECRET_KEY'] ?? '';
        assert(is_string($envSecretKey), 'BAIDU_SECRET_KEY must be a string');
        $this->secretKey = '' !== $secretKey ? $secretKey : $envSecretKey;
    }

    /**
     * 人脸检测
     *
     * @return array<string, mixed>
     */
    public function detectFace(string $imageBase64): array
    {
        $startTime = microtime(true);
        $requestData = [
            'image_type' => 'BASE64',
            'face_field' => 'age,beauty,expression,face_shape,gender,glasses,landmark,landmark150,quality,eye_status,emotion,face_type,mask,spoofing',
        ];

        $this->logger->info('开始百度人脸检测请求', [
            'url' => 'https://aip.baidubce.com/rest/2.0/face/v3/detect',
            'request_data' => $requestData,
            'image_size' => strlen($imageBase64),
        ]);

        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/rest/2.0/face/v3/detect', [
                'query' => ['access_token' => $token],
                'json' => [
                    'image' => $imageBase64,
                    'image_type' => 'BASE64',
                    'face_field' => 'age,beauty,expression,face_shape,gender,glasses,landmark,landmark150,quality,eye_status,emotion,face_type,mask,spoofing',
                ],
            ]);

            $result = $response->toArray();
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('百度人脸检测完成', [
                'face_num' => is_array($result['result'] ?? null) && isset($result['result']['face_num']) ? $result['result']['face_num'] : 0,
                'error_code' => $result['error_code'] ?? 0,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'response_size' => strlen(false !== json_encode($result) ? json_encode($result) : '[]'),
            ]);

            /** @var array<string, mixed> */
            return $result;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->error('百度人脸检测失败', [
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'exception_class' => get_class($e),
            ]);

            return [
                'error_code' => -1,
                'error_msg' => '人脸检测服务异常',
                'result' => null,
            ];
        }
    }

    /**
     * 获取访问令牌
     */
    private function getAccessToken(): string
    {
        if ((bool) time() < $this->tokenExpireTime && '' !== $this->accessToken) {
            return $this->accessToken;
        }

        $startTime = microtime(true);
        $this->logger->info('开始获取百度AI访问令牌', [
            'url' => 'https://aip.baidubce.com/oauth/2.0/token',
            'grant_type' => 'client_credentials',
        ]);

        try {
            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/oauth/2.0/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->secretKey,
                ],
            ]);

            $data = $response->toArray();
            $duration = (microtime(true) - $startTime) * 1000;

            if (!isset($data['access_token']) || !is_string($data['access_token'])) {
                throw new RuntimeException('百度AI返回的access_token格式错误');
            }
            if (!isset($data['expires_in']) || !is_int($data['expires_in'])) {
                throw new RuntimeException('百度AI返回的expires_in格式错误');
            }

            $this->accessToken = $data['access_token'];
            $this->tokenExpireTime = time() + $data['expires_in'] - 300; // 提前5分钟刷新

            $this->logger->info('获取百度AI访问令牌成功', [
                'expires_in' => $data['expires_in'],
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
            ]);

            return $this->accessToken;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->error('获取百度AI访问令牌失败', [
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'exception_class' => get_class($e),
            ]);
            throw new RuntimeException('无法获取百度AI访问令牌');
        }
    }

    /**
     * 人脸比对
     *
     * @return array<string, mixed>
     */
    public function compareFace(string $imageBase64_1, string $imageBase64_2): array
    {
        $startTime = microtime(true);
        $this->logger->info('开始百度人脸比对请求', [
            'url' => 'https://aip.baidubce.com/rest/2.0/face/v3/match',
            'image1_size' => strlen($imageBase64_1),
            'image2_size' => strlen($imageBase64_2),
        ]);

        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/rest/2.0/face/v3/match', [
                'query' => ['access_token' => $token],
                'json' => [
                    [
                        'image' => $imageBase64_1,
                        'image_type' => 'BASE64',
                        'face_type' => 'LIVE',
                        'quality_control' => 'LOW',
                    ],
                    [
                        'image' => $imageBase64_2,
                        'image_type' => 'BASE64',
                        'face_type' => 'IDCARD',
                        'quality_control' => 'LOW',
                    ],
                ],
            ]);

            $result = $response->toArray();
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('百度人脸比对完成', [
                'score' => is_array($result['result'] ?? null) && isset($result['result']['score']) ? $result['result']['score'] : 0,
                'error_code' => $result['error_code'] ?? 0,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'response_size' => strlen(false !== json_encode($result) ? json_encode($result) : '[]'),
            ]);

            /** @var array<string, mixed> */
            return $result;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->error('百度人脸比对失败', [
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'exception_class' => get_class($e),
            ]);

            return [
                'error_code' => -1,
                'error_msg' => '人脸比对服务异常',
                'result' => null,
            ];
        }
    }

    /**
     * 活体检测
     *
     * @return array<string, mixed>
     */
    public function faceverify(string $imageBase64, string $idcardNumber, string $name): array
    {
        $startTime = microtime(true);
        $requestData = [
            'image_type' => 'BASE64',
            'quality_control' => 'LOW',
            'liveness_control' => 'HIGH',
        ];

        $this->logger->info('开始百度人脸核身请求', [
            'url' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceverify',
            'request_data' => $requestData,
            'image_size' => strlen($imageBase64),
            'name' => $name,
        ]);

        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/rest/2.0/face/v3/faceverify', [
                'query' => ['access_token' => $token],
                'json' => [
                    'image' => $imageBase64,
                    'image_type' => 'BASE64',
                    'id_card_number' => $idcardNumber,
                    'name' => $name,
                    'quality_control' => 'LOW',
                    'liveness_control' => 'HIGH',
                ],
            ]);

            $result = $response->toArray();
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->info('百度人脸核身完成', [
                'score' => is_array($result['result'] ?? null) && isset($result['result']['score']) ? $result['result']['score'] : 0,
                'error_code' => $result['error_code'] ?? 0,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'response_size' => strlen(false !== json_encode($result) ? json_encode($result) : '[]'),
            ]);

            /** @var array<string, mixed> */
            return $result;
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->error('百度人脸核身失败', [
                'error' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'exception_class' => get_class($e),
            ]);

            return [
                'error_code' => -1,
                'error_msg' => '人脸核身服务异常',
                'result' => null,
            ];
        }
    }
}
