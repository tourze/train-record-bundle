<?php

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnDevice;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;

/**
 * 学习设备服务
 * 
 * 负责设备注册、识别和多设备控制
 */
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
    public function registerDevice(string $userId, array $deviceInfo): LearnDevice
    {
        $deviceFingerprint = $this->generateDeviceFingerprint($deviceInfo);
        
        // 查找现有设备
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        
        if ($device === null) {
            // 创建新设备
            $device = new LearnDevice();
            $device->setUserId($userId);
            $device->setDeviceFingerprint($deviceFingerprint);
            $device->setFirstUsedTime(new \DateTimeImmutable());
            $device->setUsageCount(0);
            // Initialize suspicious count removed - not in entity
            $device->setIsTrusted(false);
        }
        
        // 更新设备信息
        $device->setDeviceType($this->detectDeviceType($deviceInfo));
        $device->setDeviceFeatures($deviceInfo['device'] ?? []);
        if (isset($deviceInfo['browser']['name'])) {
            $device->setBrowser($deviceInfo['browser']['name']);
        }
        if (isset($deviceInfo['os']['name'])) {
            $device->setOperatingSystem($deviceInfo['os']['name']);
        }
        $device->setLastUsedTime(new \DateTimeImmutable());
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
        return $device && $device->isTrusted();
    }

    /**
     * 标记设备为受信任
     */
    public function trustDevice(string $userId, string $deviceFingerprint): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if ($device !== null) {
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
    public function recordSuspiciousActivity(string $userId, string $deviceFingerprint): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if ($device !== null) {
            // 记录可疑活动 - 直接取消信任
            $device->setIsTrusted(false);
            
            $this->entityManager->persist($device);
            $this->entityManager->flush();
            
            $this->logger->warning('记录设备可疑活动', [
                'userId' => $userId,
                'deviceFingerprint' => $deviceFingerprint,
                'suspiciousCount' => $device->getSuspiciousCount(),
            ]);
        }
    }

    /**
     * 停用设备
     */
    public function deactivateDevice(string $userId, string $deviceFingerprint): void
    {
        $device = $this->deviceRepository->findByUserAndFingerprint($userId, $deviceFingerprint);
        if ($device !== null) {
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
    public function getDeviceStats(string $userId): array
    {
        $devices = $this->deviceRepository->findByUser($userId);
        
        $stats = [
            'totalDevices' => count($devices),
            'activeDevices' => 0,
            'trustedDevices' => 0,
            'deviceTypes' => [],
            'browsers' => [],
            'operatingSystems' => [],
        ];
        
        foreach ($devices as $device) {
            if ($device->isActive()) {
                $stats['activeDevices']++;
            }
            
            if ($device->isTrusted()) {
                $stats['trustedDevices']++;
            }
            
            // 统计设备类型
            $deviceType = $device->getDeviceType();
            $stats['deviceTypes'][$deviceType] = ($stats['deviceTypes'][$deviceType]) + 1;
            
            // 统计浏览器
            $browserInfo = $device->getBrowserInfo();
            if ((bool) isset($browserInfo['name'])) {
                $browser = $browserInfo['name'];
                $stats['browsers'][$browser] = ($stats['browsers'][$browser]) + 1;
            }
            
            // 统计操作系统
            $osInfo = $device->getOsInfo();
            if ((bool) isset($osInfo['name'])) {
                $os = $osInfo['name'];
                $stats['operatingSystems'][$os] = ($stats['operatingSystems'][$os]) + 1;
            }
        }
        
        return $stats;
    }

    /**
     * 生成设备指纹
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
        
        return hash('sha256', json_encode($fingerprint));
    }

    /**
     * 检测设备类型
     */
    private function detectDeviceType(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['userAgent'] ?? '';
        
        if ((bool) preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if ((bool) preg_match('/iPad/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        
        return 'desktop';
    }
}
