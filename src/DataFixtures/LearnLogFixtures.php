<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\LearnAction;

/**
 * 生产环境学习日志数据装载器
 */
class LearnLogFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const START_LOG_REFERENCE = 'start-learn-log';

    public function load(ObjectManager $manager): void
    {
        /** @var LearnSession $session */
        $session = $this->getReference(LearnSessionFixtures::ACTIVE_SESSION_REFERENCE, LearnSession::class);

        $log = new LearnLog();
        $log->setLearnSession($session);
        $log->setAction(LearnAction::START);
        $log->setMessage('学习会话开始');
        $log->setContext(['session_id' => $session->getSessionId()]);

        $manager->persist($log);

        $this->addReference(self::START_LOG_REFERENCE, $log);

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
