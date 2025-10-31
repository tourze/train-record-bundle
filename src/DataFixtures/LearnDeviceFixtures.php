<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\LearnDevice;

class LearnDeviceFixtures extends Fixture
{
    public const LEARN_DEVICE_1 = 'learn_device_1';
    public const LEARN_DEVICE_2 = 'learn_device_2';
    public const LEARN_DEVICE_3 = 'learn_device_3';

    public function load(ObjectManager $manager): void
    {
        $device1 = new LearnDevice();
        $device1->setUserId('user_001');
        $device1->setDeviceFingerprint('fp_win_001');
        $device1->setDeviceName('Windows PC');
        $device1->setDeviceType('PC');
        $device1->setBrowser('Chrome');
        $device1->setOperatingSystem('Windows 11');
        $device1->setScreenResolution('1920x1080');
        $device1->setLastIpAddress('192.168.1.100');
        $device1->setLastUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $device1->setIsActive(true);
        $device1->setLastUseTime(new \DateTimeImmutable());
        $device1->setTimezone('Asia/Shanghai');
        $manager->persist($device1);
        $this->addReference(self::LEARN_DEVICE_1, $device1);

        $device2 = new LearnDevice();
        $device2->setUserId('user_002');
        $device2->setDeviceFingerprint('fp_mac_002');
        $device2->setDeviceName('MacBook Pro');
        $device2->setDeviceType('PC');
        $device2->setBrowser('Safari');
        $device2->setOperatingSystem('macOS 14.2');
        $device2->setScreenResolution('2560x1600');
        $device2->setLastIpAddress('10.0.0.50');
        $device2->setLastUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15');
        $device2->setIsActive(true);
        $device2->setLastUseTime(new \DateTimeImmutable('-1 hour'));
        $device2->setTimezone('Asia/Shanghai');
        $manager->persist($device2);
        $this->addReference(self::LEARN_DEVICE_2, $device2);

        $device3 = new LearnDevice();
        $device3->setUserId('user_003');
        $device3->setDeviceFingerprint('fp_ios_003');
        $device3->setDeviceName('iPhone 15');
        $device3->setDeviceType('Mobile');
        $device3->setBrowser('Mobile Safari');
        $device3->setOperatingSystem('iOS 17.2');
        $device3->setScreenResolution('1179x2556');
        $device3->setLastIpAddress('192.168.1.200');
        $device3->setLastUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X)');
        $device3->setIsActive(false);
        $device3->setLastUseTime(new \DateTimeImmutable('-2 days'));
        $device3->setTimezone('Asia/Shanghai');
        $manager->persist($device3);
        $this->addReference(self::LEARN_DEVICE_3, $device3);

        $manager->flush();
    }
}
