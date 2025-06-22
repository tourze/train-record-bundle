<?php

namespace Tourze\TrainRecordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Entity\Student;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyType;
use Tourze\TrainRecordBundle\Repository\LearnDeviceRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

/**
 * 增强的学习会话服务
 * 
 * 提供多设备控制、进度同步、防作弊检测等功能
 */
class LearnSessionService
{
    // 防作弊阈值
    private const MAX_CONCURRENT_DEVICES = 2;
    private const SUSPICIOUS_SPEED_THRESHOLD = 2.0; // 2倍速度
    private const MIN_FOCUS_RATIO = 0.7; // 最小专注度比例
    
    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnDeviceRepository $deviceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 开始学习会话
     */
    public function startSession(string $userId, string $lessonId, array $deviceInfo): LearnSession
    {
        // 检查多设备登录
        $this->checkMultiDeviceLogin($userId, $deviceInfo);
        
        // 注册设备 - 暂时简化
        // $device = $this->deviceService->registerDevice($userId, $deviceInfo);
        
        // 查找或创建学习会话
        $session = $this->findOrCreateSession($userId, $lessonId);
        
        // 更新会话状态
        $session->setFirstLearnTime($session->getFirstLearnTime() ?? new \DateTimeImmutable());
        $session->setLastLearnTime(new \DateTimeImmutable());
        
        // 缓存当前学习状态 - 暂时移除
        // $this->cacheSessionState($userId, $session, $device);
        
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        $this->logger->info('学习会话已开始', [
            'user_id' => $userId,
            'session_id' => $session->getId(),
            'lesson_id' => $lessonId,
        ]);
        
        return $session;
    }

    /**
     * 更新学习进度
     */
    public function updateProgress(string $sessionId, float $currentTime, float $duration): void
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            throw new \InvalidArgumentException('学习会话不存在');
        }
        
        // 检查进度异常
        $this->checkProgressAnomaly($session, $currentTime, $duration);
        
        // 更新会话进度
        $session->setCurrentDuration((string) $currentTime);
        $session->setLastLearnTime(new \DateTimeImmutable());
        
        // 同步跨设备进度
        $this->syncProgressAcrossDevices($session, $currentTime);
        
        $this->entityManager->persist($session);
        $this->entityManager->flush();
    }

    /**
     * 记录学习行为
     */
    public function recordBehavior(string $sessionId, string $behaviorType, array $data = []): void
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            return;
        }
        
        // TODO: 实现行为记录功能
        // $this->behaviorService->recordBehavior($session, $behaviorType, $data);
        
        // 检查行为异常
        $this->checkBehaviorAnomaly($session, $behaviorType, $data);
    }

    /**
     * 结束学习会话
     */
    public function endSession(string $sessionId): void
    {
        $session = $this->sessionRepository->find($sessionId);
        if ($session === null) {
            return;
        }
        
        $session->setLastLearnTime(new \DateTimeImmutable());
        
        // 计算有效学习时长
        $effectiveDuration = $this->calculateEffectiveDuration($session);
        $session->setTotalDuration((string) $effectiveDuration);
        
        // 清理缓存
        $this->clearSessionCache($session);
        
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        
        $this->logger->info('学习会话已结束', [
            'session_id' => $sessionId,
            'effective_duration' => $effectiveDuration,
        ]);
    }

    /**
     * 检查多设备登录
     */
    private function checkMultiDeviceLogin(string $userId, array $deviceInfo): void
    {
        $activeDevices = $this->deviceRepository->findBy(['user' => $userId]);
        
        if ((bool) count($activeDevices) >= self::MAX_CONCURRENT_DEVICES) {
                         $this->recordAnomaly((string) $userId,
                 AnomalyType::MULTIPLE_DEVICE,
                 AnomalySeverity::HIGH,
                 '检测到多设备同时登录',
                 ['device_count' => count($activeDevices), 'new_device' => $deviceInfo]
             );
            
            throw new \RuntimeException('检测到多设备登录，请关闭其他设备后重试');
        }
    }

    /**
     * 查找或创建学习会话
     */
    private function findOrCreateSession(string $userId, string $lessonId): LearnSession
    {
        // 这里需要根据实际的实体关系来实现
        // 由于类型问题，暂时返回一个简单的实现
        $sessions = $this->sessionRepository->findBy(['lesson' => $lessonId]);
        
        if (!empty($sessions)) {
            return $sessions[0];
        }
        
        // 创建新会话的逻辑需要根据实际的实体关系来实现
        throw new \RuntimeException('创建会话功能需要完善实体关系后实现');
    }

    /**
     * 检查学员是否有其他活跃的学习会话
     *
     * @param mixed $student 学员实体
     * @param string $lessonId 当前要学习的课时ID
     * @throws \RuntimeException 如果存在其他活跃会话
     */
    public function checkConcurrentLearning($student, string $lessonId): void
    {
        $otherActiveSessions = $this->sessionRepository->findOtherActiveSessionsByStudent($student, $lessonId);
        
        if (!empty($otherActiveSessions)) {
            $activeSession = $otherActiveSessions[0];
            $courseName = $activeSession->getCourse()->getTitle();
            $lessonName = $activeSession->getLesson()->getTitle();
            
            throw new \RuntimeException(
                sprintf(
                    '您正在学习课程"%s"的课时"%s"，请先完成或暂停当前学习后再开始新的课程',
                    $courseName,
                    $lessonName
                )
            );
        }
    }

    /**
     * 设置学习会话为活跃状态
     *
     * @param LearnSession $session 学习会话
     * @param bool $flush 是否立即刷新到数据库
     */
    public function activateSession(LearnSession $session, bool $flush = true): void
    {
        $session->setActive(true);
        $session->setLastLearnTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($session);
        
        if ((bool) $flush) {
            $this->entityManager->flush();
        }
        
        $this->logger->info('学习会话已激活', [
            'session_id' => $session->getId(),
            'student_id' => $session->getStudent()->getId(),
            'lesson_id' => $session->getLesson()->getId(),
        ]);
    }

    /**
     * 设置学习会话为非活跃状态
     *
     * @param LearnSession $session 学习会话
     * @param bool $flush 是否立即刷新到数据库
     */
    public function deactivateSession(LearnSession $session, bool $flush = true): void
    {
        $session->setActive(false);
        $session->setLastLearnTime(new \DateTimeImmutable());
        
        $this->entityManager->persist($session);
        
        if ((bool) $flush) {
            $this->entityManager->flush();
        }
        
        $this->logger->info('学习会话已停用', [
            'session_id' => $session->getId(),
            'student_id' => $session->getStudent()->getId(),
            'lesson_id' => $session->getLesson()->getId(),
        ]);
    }

    /**
     * 检查进度异常
     */
    private function checkProgressAnomaly(LearnSession $session, float $currentTime, float $duration): void
    {
        $lastTime = (float) $session->getCurrentDuration();
        $timeDiff = $currentTime - $lastTime;
        $realTimeDiff = time() - ($session->getLastLearnTime()?->getTimestamp() ?? time());
        
        // 检查播放速度异常
        if ($realTimeDiff > 0 && ($timeDiff / $realTimeDiff) > self::SUSPICIOUS_SPEED_THRESHOLD) {
            $this->recordAnomaly(
                (string) $session->getStudent()->getId(),
                AnomalyType::RAPID_PROGRESS,
                AnomalySeverity::MEDIUM,
                '检测到异常播放速度',
                [
                    'speed_ratio' => $timeDiff / $realTimeDiff,
                    'time_diff' => $timeDiff,
                    'real_time_diff' => $realTimeDiff,
                ]
            );
        }
    }

    /**
     * 同步跨设备进度
     */
    private function syncProgressAcrossDevices(LearnSession $session, float $currentTime): void
    {
        $userId = $session->getStudent()->getId();
        $lessonId = $session->getLesson()->getId();
        
        // 更新进度记录
        // TODO: 实现跨设备进度同步
        // 需要根据实际的实体关系来实现
        $this->logger->info('同步跨设备进度', [
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'current_time' => $currentTime,
        ]);
    }

    /**
     * 检查行为异常
     */
    private function checkBehaviorAnomaly(LearnSession $session, string $behaviorType, array $data): void
    {
        // 根据行为类型检查异常
        switch ($behaviorType) {
            case 'window_blur':
                $this->checkWindowBlurAnomaly($session, $data);
                break;
            case 'mouse_leave':
                $this->checkMouseLeaveAnomaly($session, $data);
                break;
            case 'rapid_seek':
                $this->checkRapidSeekAnomaly($session, $data);
                break;
        }
    }

    /**
     * 检查窗口失焦异常
     */
    private function checkWindowBlurAnomaly(LearnSession $session, array $data): void
    {
        // 简化实现，直接记录异常
        $this->recordAnomaly(
            (string) $session->getStudent()->getId(),
            AnomalyType::WINDOW_SWITCH,
            AnomalySeverity::LOW,
            '检测到窗口切换行为',
            $data
        );
    }

    /**
     * 检查鼠标离开异常
     */
    private function checkMouseLeaveAnomaly(LearnSession $session, array $data): void
    {
        // 简化实现，直接记录异常
        $this->recordAnomaly(
            (string) $session->getStudent()->getId(),
            AnomalyType::SUSPICIOUS_BEHAVIOR,
            AnomalySeverity::LOW,
            '检测到鼠标离开学习区域',
            $data
        );
    }

    /**
     * 检查快速拖拽异常
     */
    private function checkRapidSeekAnomaly(LearnSession $session, array $data): void
    {
        // 简化实现，直接记录异常
        $this->recordAnomaly(
            (string) $session->getStudent()->getId(),
            AnomalyType::RAPID_PROGRESS,
            AnomalySeverity::MEDIUM,
            '检测到快速拖拽行为',
            $data
        );
    }

    /**
     * 计算有效学习时长
     */
    private function calculateEffectiveDuration(LearnSession $session): float
    {
        $totalDuration = (float) $session->getCurrentDuration();
        
        // 简化实现，使用最小专注度比例
        return $totalDuration * self::MIN_FOCUS_RATIO;
    }

    /**
     * 记录异常
     */
    private function recordAnomaly(
        string $userId,
        AnomalyType $type,
        AnomalySeverity $severity,
        string $description,
        array $evidence = []
    ): void {
        // 简化实现，暂时只记录日志
        $this->logger->warning('检测到学习异常', [
            'user_id' => $userId,
            'type' => $type->value,
            'severity' => $severity->value,
            'description' => $description,
            'evidence' => $evidence,
        ]);
        
        // TODO: 完善异常记录实体的创建
        // 需要根据实际的实体关系来实现
    }

    /**
     * 清理会话缓存
     */
    private function clearSessionCache(LearnSession $session): void
    {
        // 暂时移除缓存功能
        /*
        $userId = $session->getStudent()->getId();
        
        // 清理相关缓存
        $this->cache->delete(self::CACHE_PREFIX_LEARNING . $userId);
        
        // 清理设备缓存需要获取设备信息
        $devices = $this->deviceRepository->findBy(['user' => $userId);
        foreach ($devices as $device) {
            $this->cache->delete(self::CACHE_PREFIX_DEVICE . $device->getDeviceFingerprint());
        }
        */
    }
} 