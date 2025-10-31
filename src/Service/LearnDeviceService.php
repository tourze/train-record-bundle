<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;

/**
 * 学习设备服务
 *
 * 负责设备注册、识别和多设备控制
 */
#[WithMonologChannel(channel: 'train_record')]
class LearnDeviceService
{
    private const DEVICE_ACTIVE_THRESHOLD = 3600; // 设备活跃阈值（1小时）

    public function __construct(
        private readonly LearnDeviceRepository $deviceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 注册设备
     */
    /**
     * @param array<string, mixed> $deviceInfo
     */
    public function registerDevice(string $userId, array $deviceInfo): LearnDevice
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($deviceInfo);

        // 查找现有设备
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);

        if (null === $device) {
            // 创建新设备
            $device = new LearnDevice();
            $device->setUserId($userId);
            $device->setDeviceFingerprint($deviceFingerprint);
            $device->setFirstUseTime(new \DateTimeImmutable());
            $device->setUsageCount(0);
            // Initialize suspicious count removed - not in entity
            $device->setIsTrusted(false);
        }

        // 更新设备信息
        $device->setDeviceType($this->detectDeviceType($deviceInfo));

        $deviceFeatures = $this->getArrayValue($deviceInfo, 'device', []);
        $device->setDeviceFeatures($this->ensureStringArrayValues($deviceFeatures));

        $browserName = $this->getNestedStringValue($deviceInfo, ['browser', 'name']);
        if (null !== $browserName) {
            $device->setBrowser($browserName);
        }

        $osName = $this->getNestedStringValue($deviceInfo, ['os', 'name']);
        if (null !== $osName) {
            $device->setOperatingSystem($osName);
        }
        $device->setLastUseTime(new \DateTimeImmutable());
        $device->setIsActive(true);

        // 增加会话计数
        $device->setUsageCount($device->getUsageCount() + 1);

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        $this->logger->info('设备已注册', [
            'userId' => $userId,
            'deviceFingerprint' => $deviceFingerprint,
            'deviceType' => $device->getDeviceType(),
        ]);

        return $device;
    }

    /**
     * 获取用户的活跃设备
     */
    /**
     * @return LearnDevice[]
     */
    public function getActiveDevices(string $userId): array
    {
        $threshold = new \DateTimeImmutable();
        $threshold->sub(new \DateInterval('PT' . self::DEVICE_ACTIVE_THRESHOLD . 'S'));

        return $this->deviceRepository->findActiveByUser($userId, $threshold);
    }

    /**
     * 检查设备是否受信任
     */
    public function isDeviceTrusted(string $userId, string $deviceFingerprint): bool
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);

        return null !== $device && $device->isTrusted();
    }

    /**
     * 标记设备为受信任
     */
    public function trustDevice(string $userId, string $deviceFingerprint): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if (null !== $device) {
            $device->setIsTrusted(true);
            $this->entityManager->persist($device);
            $this->entityManager->flush();

            $this->logger->info('设备已标记为受信任', [
                'userId' => $userId,
                'deviceFingerprint' => $deviceFingerprint,
            ]);
        }
    }

    /**
     * 记录可疑活动
     */
    public function recordSuspiciousActivity(string $userId, string $deviceFingerprint, string $reason = '未知原因'): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if (null !== $device) {
            // 记录可疑活动 - 直接取消信任
            $device->setIsTrusted(false);

            $this->entityManager->persist($device);
            $this->entityManager->flush();

            $this->logger->warning('记录设备可疑活动', [
                'userId' => $userId,
                'deviceFingerprint' => $deviceFingerprint,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * 停用设备
     */
    public function deactivateDevice(string $userId, string $deviceFingerprint): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if (null !== $device) {
            $device->setIsActive(false);
            $this->entityManager->persist($device);
            $this->entityManager->flush();

            $this->logger->info('设备已停用', [
                'userId' => $userId,
                'deviceFingerprint' => $deviceFingerprint,
            ]);
        }
    }

    /**
     * 获取设备统计信息
     */
    /**
     * @return array<string, mixed>
     */
    public function getDeviceStats(string $userId): array
    {
        $devices = $this->deviceRepository->findByUser($userId);

        $stats = [
            'total_devices' => count($devices),
            'active_devices' => 0,
            'trusted_devices' => 0,
            'device_types' => [],
            'browsers' => [],
            'operating_systems' => [],
        ];

        foreach ($devices as $device) {
            // 更新设备计数器
            if ($device->isActive()) {
                ++$stats['active_devices'];
            }
            if ($device->isTrusted()) {
                ++$stats['trusted_devices'];
            }

            // 更新设备类型统计
            $deviceType = $device->getDeviceType();
            ++$stats['device_types'][$deviceType];

            // 更新浏览器统计
            $browserName = $device->getBrowserInfo();
            if (null !== $browserName) {
                ++$stats['browsers'][$browserName];
            }

            // 更新操作系统统计
            $osName = $device->getOsInfo();
            if (null !== $osName) {
                ++$stats['operating_systems'][$osName];
            }
        }

        return $stats;
    }

    /**
     * 生成设备指纹
     */
    /**
     * @param array<string, mixed> $deviceInfo
     */
    private function generateDeviceFingerprint(array $deviceInfo): string
    {
        // 基于设备信息生成唯一指纹
        $fingerprint = [
            'userAgent' => $deviceInfo['userAgent'] ?? '',
            'screen' => $deviceInfo['screen'] ?? '',
            'timezone' => $deviceInfo['timezone'] ?? '',
            'language' => $deviceInfo['language'] ?? '',
            'platform' => $deviceInfo['platform'] ?? '',
        ];

        $jsonResult = json_encode($fingerprint);
        if (false === $jsonResult) {
            throw new \RuntimeException('生成设备指纹时 JSON 编码失败');
        }

        return hash('sha256', $jsonResult);
    }

    /**
     * 检测设备类型
     */
    /**
     * @param array<string, mixed> $deviceInfo
     */
    private function detectDeviceType(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['userAgent'] ?? '';
        $userAgentString = is_string($userAgent) ? $userAgent : '';

        if ((bool) preg_match('/Mobile|Android|iPhone|iPad/', $userAgentString)) {
            if ((bool) preg_match('/iPad/', $userAgentString)) {
                return 'tablet';
            }

            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * 安全获取数组值
     *
     * @param array<string, mixed> $data
     * @param array<mixed> $default
     * @return array<mixed>
     */
    private function getArrayValue(array $data, string $key, array $default = []): array
    {
        $value = $data[$key] ?? $default;

        return is_array($value) ? $value : $default;
    }

    /**
     * 确保数组值都是字符串映射
     *
     * @param array<mixed> $data
     * @return array<string, mixed>
     */
    private function ensureStringArrayValues(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * 安全获取嵌套字符串值
     *
     * @param array<string, mixed> $data
     * @param array<string> $keys
     */
    private function getNestedStringValue(array $data, array $keys): ?string
    {
        $current = $data;
        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }

        return is_string($current) ? $current : null;
    }
}
