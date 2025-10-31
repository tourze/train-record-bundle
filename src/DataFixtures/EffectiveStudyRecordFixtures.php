<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * 生产环境有效学时记录数据装载器
 */
class EffectiveStudyRecordFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const REFERENCE_PREFIX = 'effective-study-record-';
    public const VALID_RECORD_REFERENCE = 'valid-study-record';

    public function load(ObjectManager $manager): void
    {
        // 生产环境的 DataFixtures 通常只创建必要的基础数据
        // 这里创建一些示例有效学时记录

        /** @var LearnSession $session */
        $session = $this->getReference(LearnSessionFixtures::ACTIVE_SESSION_REFERENCE, LearnSession::class);

        $record = new EffectiveStudyRecord();
        $record->setSession($session);
        $record->setCourse($session->getCourse());
        $record->setLesson($session->getLesson());
        $record->setUserId($session->getStudent()->getUserIdentifier());
        $record->setStudyDate($session->getFirstLearnTime() ?? new \DateTimeImmutable());
        $record->setStartTime(new \DateTimeImmutable());
        $record->setEndTime(new \DateTimeImmutable('+1 hour'));
        $record->setTotalDuration(3600.0);
        $record->setEffectiveDuration(3240.0);
        $record->setInvalidDuration(360.0);
        $record->setStatus(StudyTimeStatus::VALID);
        $record->setQualityScore(8.5);
        $record->setIncludeInDailyTotal(true);
        $record->setDescription('示例有效学时记录');

        $manager->persist($record);

        $this->addReference(self::VALID_RECORD_REFERENCE, $record);

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
