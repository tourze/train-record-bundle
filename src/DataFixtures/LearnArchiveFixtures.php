<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainCourseBundle\DataFixtures\CourseFixtures;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;

class LearnArchiveFixtures extends Fixture implements DependentFixtureInterface
{
    public const LEARN_ARCHIVE_1 = 'learn_archive_1';
    public const LEARN_ARCHIVE_2 = 'learn_archive_2';
    public const LEARN_ARCHIVE_3 = 'learn_archive_3';

    public function load(ObjectManager $manager): void
    {
        $course1 = $this->getReference(CourseFixtures::COURSE_PHP_BASICS, Course::class);
        $course2 = $this->getReference(CourseFixtures::COURSE_SYMFONY_ADVANCED, Course::class);
        $course3 = $this->getReference(CourseFixtures::COURSE_SAFETY_TRAINING, Course::class);

        $archive1 = new LearnArchive();
        $archive1->setUserId('user_001');
        $archive1->setCourse($course1);
        $archive1->setArchiveFormat(ArchiveFormat::ZIP);
        $archive1->setArchiveStatus(ArchiveStatus::COMPLETED);
        $archive1->setArchivePath('/archives/2024/01/user_001_learn_records.zip');
        $archive1->setFileSize(1024000);
        $archive1->setTotalEffectiveTime(45000.0);
        $archive1->setTotalSessions(25);
        $archive1->setArchiveMetadata([
            'sessions_count' => 25,
            'total_duration' => 45000,
            'courses' => [$course1->getId()],
        ]);
        $manager->persist($archive1);
        $this->addReference(self::LEARN_ARCHIVE_1, $archive1);

        $archive2 = new LearnArchive();
        $archive2->setUserId('user_002');
        $archive2->setCourse($course2);
        $archive2->setArchiveFormat(ArchiveFormat::JSON);
        $archive2->setArchiveStatus(ArchiveStatus::PROCESSING);
        $archive2->setTotalSessions(15);
        $archive2->setTotalEffectiveTime(25000.0);
        $manager->persist($archive2);
        $this->addReference(self::LEARN_ARCHIVE_2, $archive2);

        $archive3 = new LearnArchive();
        $archive3->setUserId('user_003');
        $archive3->setCourse($course3);
        $archive3->setArchiveFormat(ArchiveFormat::CSV);
        $archive3->setArchiveStatus(ArchiveStatus::FAILED);
        $archive3->setTotalSessions(8);
        $archive3->setTotalEffectiveTime(12000.0);
        $manager->persist($archive3);
        $this->addReference(self::LEARN_ARCHIVE_3, $archive3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CourseFixtures::class,
        ];
    }
}
