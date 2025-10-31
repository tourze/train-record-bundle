<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\LearnBehavior;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

/**
 * 生产环境学习行为数据装载器
 */
class LearnBehaviorFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const FOCUS_BEHAVIOR_REFERENCE = 'focus-behavior';

    public function load(ObjectManager $manager): void
    {
        /** @var LearnSession $session */
        $session = $this->getReference(LearnSessionFixtures::ACTIVE_SESSION_REFERENCE, LearnSession::class);

        $behavior = new LearnBehavior();
        $behavior->setSession($session);
        $behavior->setBehaviorType(BehaviorType::PLAY);
        $behavior->setBehaviorData(['duration' => 300, 'intensity' => 0.8]);

        $manager->persist($behavior);

        $this->addReference(self::FOCUS_BEHAVIOR_REFERENCE, $behavior);

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
