<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\FaceDetect;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * 生产环境人脸检测数据装载器
 */
class FaceDetectFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const VERIFIED_DETECT_REFERENCE = 'verified-face-detect';

    public function load(ObjectManager $manager): void
    {
        /** @var LearnSession $session */
        $session = $this->getReference(LearnSessionFixtures::ACTIVE_SESSION_REFERENCE, LearnSession::class);

        $detection = new FaceDetect();
        $detection->setSession($session);
        $detection->setConfidence('0.95');
        $detection->setIsVerified(true);
        $detection->setDetectResult(['status' => 'success', 'confidence' => 0.95]);

        $manager->persist($detection);

        $this->addReference(self::VERIFIED_DETECT_REFERENCE, $detection);

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
