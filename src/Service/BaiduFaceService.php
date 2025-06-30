<?php

namespace Tourze\TrainRecordBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;

/**
 * 百度人脸识别服务
 *
 * 集成百度AI开放平台的人脸识别API
 */
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
        string $secretKey = ''
    ) {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    /**
     * 人脸检测
     */
    public function detectFace(string $imageBase64): array
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/rest/2.0/face/v3/detect', [
                'query' => ['access_token' => $token],
                'json' => [
                    'image' => $imageBase64,
                    'image_type' => 'BASE64',
                    'face_field' => 'age,beauty,expression,face_shape,gender,glasses,landmark,landmark150,quality,eye_status,emotion,face_type,mask,spoofing'
                ]
            ]);

            $result = $response->toArray();

            $this->logger->info('百度人脸检测完成', [
                'face_num' => $result['result']['face_num'],
                'error_code' => $result['error_code']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('百度人脸检测失败', ['error' => $e->getMessage()]);
            return [
                'error_code' => -1,
                'error_msg' => '人脸检测服务异常',
                'result' => null
            ];
        }
    }

    /**
     * 获取访问令牌
     */
    private function getAccessToken(): string
    {
        if ((bool) time() < $this->tokenExpireTime && !empty($this->accessToken)) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/oauth/2.0/token', [
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->secretKey
                ]
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->tokenExpireTime = time() + $data['expires_in'] - 300; // 提前5分钟刷新

            return $this->accessToken;
        } catch (\Exception $e) {
            $this->logger->error('获取百度AI访问令牌失败', ['error' => $e->getMessage()]);
            throw new TrainRecordException('无法获取百度AI访问令牌');
        }
    }

    /**
     * 人脸比对
     */
    public function compareFace(string $imageBase64_1, string $imageBase64_2): array
    {
        try {
            $token = $this->getAccessToken();
            
            $response = $this->httpClient->request('POST', 'https://aip.baidubce.com/rest/2.0/face/v3/match', [
                'query' => ['access_token' => $token],
                'json' => [
                    [
                        'image' => $imageBase64_1,
                        'image_type' => 'BASE64',
                        'face_type' => 'LIVE',
                        'quality_control' => 'LOW'
                    ],
                    [
                        'image' => $imageBase64_2,
                        'image_type' => 'BASE64',
                        'face_type' => 'IDCARD',
                        'quality_control' => 'LOW'
                    ]
                ]
            ]);

            $result = $response->toArray();
            
            $this->logger->info('百度人脸比对完成', [
                'score' => $result['result']['score'],
                'error_code' => $result['error_code']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('百度人脸比对失败', ['error' => $e->getMessage()]);
            return [
                'error_code' => -1,
                'error_msg' => '人脸比对服务异常',
                'result' => null
            ];
        }
    }

    /**
     * 活体检测
     */
    public function faceverify(string $imageBase64, string $idcardNumber, string $name): array
    {
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
                    'liveness_control' => 'HIGH'
                ]
            ]);

            $result = $response->toArray();
            
            $this->logger->info('百度人脸核身完成', [
                'score' => $result['result']['score'],
                'error_code' => $result['error_code']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('百度人脸核身失败', ['error' => $e->getMessage()]);
            return [
                'error_code' => -1,
                'error_msg' => '人脸核身服务异常',
                'result' => null
            ];
        }
    }
}