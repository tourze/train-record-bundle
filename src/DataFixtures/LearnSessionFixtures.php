<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use BizUserBundle\DataFixtures\BizUserFixtures;
use BizUserBundle\Entity\BizUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainClassroomBundle\DataFixtures\RegistrationFixtures;
use Tourze\TrainClassroomBundle\Entity\Registration;
use Tourze\TrainCourseBundle\DataFixtures\CourseFixtures;
use Tourze\TrainCourseBundle\DataFixtures\LessonFixtures;
use Tourze\TrainCourseBundle\Entity\Course;
use Tourze\TrainCourseBundle\Entity\Lesson;
use Tourze\TrainRecordBundle\Entity\LearnSession;

/**
 * 生产环境学习会话数据装载器
 */
class LearnSessionFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public const ACTIVE_SESSION_REFERENCE = 'active-learn-session';

    public function load(ObjectManager $manager): void
    {
        // 获取依赖实体
        $student = $this->getReference(BizUserFixtures::ADMIN_USER_REFERENCE, BizUser::class);

        // 获取课程和课时 (需要先导入)
        $course = $this->getReference(CourseFixtures::COURSE_PHP_BASICS, Course::class);
        $lesson = $this->getReference(LessonFixtures::LESSON_PHP_SYNTAX, Lesson::class);

        // 获取注册记录
        $registration = $this->getReference(RegistrationFixtures::REGISTRATION_REFERENCE, Registration::class);

        // 创建一个基础的学习会话用于其他 Fixtures 依赖
        $session = new LearnSession();
        $session->setSessionId('prod-session-001');
        $session->setActive(true);
        $session->setFinished(false);
        $session->setCurrentDuration('1800');
        $session->setTotalDuration('1800');
        $session->setEffectiveDuration('1620');
        $session->setFirstLearnTime(new \DateTimeImmutable('-1 hour'));
        $session->setLastLearnTime(new \DateTimeImmutable());
        $session->setCreatedFromIp('127.0.0.1');
        $session->setCreatedFromUa('Test User Agent');

        // 设置必需的关联实体
        $session->setStudent($student);
        $session->setCourse($course);
        $session->setLesson($lesson);
        $session->setRegistration($registration);

        $manager->persist($session);

        $this->addReference(self::ACTIVE_SESSION_REFERENCE, $session);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BizUserFixtures::class,
            CourseFixtures::class,
            LessonFixtures::class,
            RegistrationFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['production', 'dev'];
    }
}
