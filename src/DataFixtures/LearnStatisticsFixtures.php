<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainRecordBundle\Entity\LearnStatistics;
use Tourze\TrainRecordBundle\Enum\StatisticsPeriod;
use Tourze\TrainRecordBundle\Enum\StatisticsType;

class LearnStatisticsFixtures extends Fixture
{
    public const LEARN_STATISTICS_1 = 'learn_statistics_1';
    public const LEARN_STATISTICS_2 = 'learn_statistics_2';
    public const LEARN_STATISTICS_3 = 'learn_statistics_3';

    public function load(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable();

        $stats1 = new LearnStatistics();
        $stats1->setStatisticsType(StatisticsType::DURATION);
        $stats1->setStatisticsPeriod(StatisticsPeriod::DAILY);
        $stats1->setStatisticsDate($today);
        $stats1->setTotalUsers(150);
        $stats1->setActiveUsers(120);
        $stats1->setTotalSessions(300);
        $stats1->setTotalDuration(18000.0);
        $stats1->setEffectiveDuration(16200.0);
        $stats1->setCompletionRate(75.5);
        $manager->persist($stats1);
        $this->addReference(self::LEARN_STATISTICS_1, $stats1);

        $stats2 = new LearnStatistics();
        $stats2->setStatisticsType(StatisticsType::COMPLETION);
        $stats2->setStatisticsPeriod(StatisticsPeriod::WEEKLY);
        $stats2->setStatisticsDate($today->modify('monday this week'));
        $stats2->setTotalUsers(200);
        $stats2->setActiveUsers(180);
        $stats2->setCompletionRate(82.3);
        $stats2->setAverageEfficiency(88.7);
        $manager->persist($stats2);
        $this->addReference(self::LEARN_STATISTICS_2, $stats2);

        $stats3 = new LearnStatistics();
        $stats3->setStatisticsType(StatisticsType::ANOMALY);
        $stats3->setStatisticsPeriod(StatisticsPeriod::MONTHLY);
        $stats3->setStatisticsDate($today->modify('first day of this month'));
        $stats3->setTotalUsers(500);
        $stats3->setActiveUsers(450);
        $stats3->setAnomalyCount(25);
        $stats3->setExtendedData([
            'high_risk_users' => 5,
            'medium_risk_users' => 15,
            'low_risk_users' => 5,
        ]);
        $manager->persist($stats3);
        $this->addReference(self::LEARN_STATISTICS_3, $stats3);

        $manager->flush();
    }
}
