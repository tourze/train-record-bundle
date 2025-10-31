<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\LearnAnomaly;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyStatus;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * 生产环境学习异常数据装载器
 */
class LearnAnomalyFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const DETECTED_ANOMALY_REFERENCE = 'detected-anomaly';

    public function load(ObjectManager $manager): void
    {
        /** @var LearnSession $session */
        $session = $this->getReference(LearnSessionFixtures::ACTIVE_SESSION_REFERENCE, LearnSession::class);

        $anomaly = new LearnAnomaly();
        $anomaly->setSession($session);
        $anomaly->setAnomalyType(AnomalyType::SUSPICIOUS_BEHAVIOR);
        $anomaly->setSeverity(AnomalySeverity::MEDIUM);
        $anomaly->setStatus(AnomalyStatus::DETECTED);
        $anomaly->setDetectTime(new \DateTimeImmutable());
        $anomaly->setAnomalyDescription('检测到可疑行为');
        $anomaly->setAnomalyData(['behavior' => 'focus_lost', 'duration' => 30]);

        $manager->persist($anomaly);

        $this->addReference(self::DETECTED_ANOMALY_REFERENCE, $anomaly);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LearnSessionFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['production', 'dev'];
    }
}
