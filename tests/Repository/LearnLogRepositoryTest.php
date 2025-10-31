<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Enum\LearnAction;
use Tourze\TrainRecordBundle\Repository\LearnLogRepository;

/**
 * LearnLogRepository 集成测试
 *
 * @template TEntity of LearnLog
 * @extends AbstractRepositoryTestCase<TEntity>
 * @internal
 */
#[CoversClass(LearnLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class LearnLogRepositoryTest extends AbstractRepositoryTestCase
{
    private LearnLogRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getEntityManager()->getRepository(LearnLog::class);
        $this->assertInstanceOf(LearnLogRepository::class, $repository);
        $this->repository = $repository;
    }

    /**
     * 测试创建和查找日志
     */
    public function testCreateAndFindWorksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Test log message');
        $log->setContext(['session' => 'session001']);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $foundLog = $this->repository->find($log->getId());

        $this->assertNotNull($foundLog);
        $this->assertEquals('INFO', $foundLog->getLogLevel());
        $this->assertEquals('Test log message', $foundLog->getMessage());
    }

    /**
     * 测试 findOneBy 方法
     */

    /**
     * 测试 findOneBy 方法的排序逻辑
     */
    public function testFindOneByWithOrderByReturnsCorrectEntity(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel('INFO');
        $log1->setMessage('Zebra');
        $log1->setContext([]);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::START);
        $log2->setLogLevel('INFO');
        $log2->setMessage('Alpha');
        $log2->setContext([]);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $firstLog = $this->repository->findOneBy(['logLevel' => 'INFO'], ['message' => 'ASC']);

        $this->assertNotNull($firstLog);
        $this->assertEquals('Alpha', $firstLog->getMessage());
    }

    /**
     * 测试 findAll 方法
     */

    /**
     * 测试 find 方法查找存在的实体
     */

    /**
     * 测试 findBy 方法
     */

    /**
     * 测试 findBy 方法的排序功能
     */

    /**
     * 测试 findBy 方法的分页功能
     */

    /**
     * 测试空数据库查询
     */
    public function testFindAllWithEmptyDatabaseReturnsEmptyArray(): void
    {
        // 清理数据库中的所有LearnLog记录
        $conn = self::getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform();
        $tableName = self::getEntityManager()->getClassMetadata(LearnLog::class)->getTableName();
        $conn->executeStatement($platform->getTruncateTableSQL($tableName, true));

        $allLogs = $this->repository->findAll();

        $this->assertEmpty($allLogs);
    }

    /**
     * 测试查找不存在的记录
     */
    public function testFindWithNonExistentIdReturnsNull(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::ENDED);
        $log->setLogLevel('INFO');
        $log->setMessage('Test');
        $log->setContext([]);
        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $nonExistentLog = $this->repository->find('non-existent-id');

        $this->assertNull($nonExistentLog);
    }

    /**
     * 测试复杂查询条件
     */
    public function testFindByWithMultipleCriteriaReturnsCorrectResults(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel('ERROR');
        $log1->setMessage('Database connection failed');
        $log1->setContext(['component' => 'database']);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('ERROR');
        $log2->setMessage('File not found');
        $log2->setContext(['component' => 'filesystem']);

        $log3 = new LearnLog();
        $log3->setAction(LearnAction::PLAY);
        $log3->setLogLevel('INFO');
        $log3->setMessage('User logged in');
        $log3->setContext(['component' => 'auth']);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->persist($log3);
        self::getEntityManager()->flush();

        $errorLogs = $this->repository->findBy(['logLevel' => 'ERROR']);

        $this->assertCount(2, $errorLogs);
        foreach ($errorLogs as $log) {
            $this->assertEquals('ERROR', $log->getLogLevel());
        }
    }

    /**
     * 测试 save 方法
     */
    public function testSaveMethodPersistsEntity(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('DEBUG');
        $log->setMessage('Save test');
        $log->setContext(['test' => true]);

        $this->repository->save($log);

        $found = $this->repository->find($log->getId());

        $this->assertNotNull($found);
        $this->assertEquals('DEBUG', $found->getLogLevel());
        $this->assertEquals('Save test', $found->getMessage());
    }

    /**
     * 测试 save 方法不刷新
     */
    public function testSaveMethodWithoutFlush(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::PAUSE);
        $log->setLogLevel('NOTICE');
        $log->setMessage('No flush test');
        $log->setContext([]);

        $this->repository->save($log, false);
        self::getEntityManager()->flush();

        $found = $this->repository->find($log->getId());

        $this->assertNotNull($found);
        $this->assertEquals('NOTICE', $found->getLogLevel());
    }

    /**
     * 测试 remove 方法
     */
    public function testRemoveMethodDeletesEntity(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::ENDED);
        $log->setLogLevel('CRITICAL');
        $log->setMessage('Remove test');
        $log->setContext([]);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $id = $log->getId();
        $this->repository->remove($log);

        $found = $this->repository->find($id);

        $this->assertNull($found);
    }

    /**
     * 测试 remove 方法不刷新
     */
    public function testRemoveMethodWithoutFlush(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::WATCH);
        $log->setLogLevel('ALERT');
        $log->setMessage('Remove no flush test');
        $log->setContext([]);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $id = $log->getId();
        $this->repository->remove($log, false);
        self::getEntityManager()->flush();

        $found = $this->repository->find($id);

        $this->assertNull($found);
    }

    /**
     * 测试查询可空字段
     */
    public function testFindByNullableFieldsWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel(null);
        $log1->setMessage(null);
        $log1->setContext(null);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('INFO');
        $log2->setMessage('With message');
        $log2->setContext(['key' => 'value']);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $nullLevelLogs = $this->repository->findBy(['logLevel' => null]);
        $nullMessageLogs = $this->repository->findBy(['message' => null]);

        $this->assertCount(1, $nullLevelLogs);
        $this->assertCount(1, $nullMessageLogs);
        $this->assertNull($nullLevelLogs[0]->getLogLevel());
        $this->assertNull($nullMessageLogs[0]->getMessage());
    }

    /**
     * 测试查询关联字段为 null
     */
    public function testFindByNullAssociationWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel('INFO');
        $log1->setMessage('No session');
        $log1->setContext([]);
        $log1->setLearnSession(null);
        $log1->setStudent(null);
        $log1->setRegistration(null);
        $log1->setLesson(null);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->flush();

        $noSessionLogs = $this->repository->findBy(['learnSession' => null]);
        $noStudentLogs = $this->repository->findBy(['student' => null]);
        $noRegistrationLogs = $this->repository->findBy(['registration' => null]);
        $noLessonLogs = $this->repository->findBy(['lesson' => null]);

        $this->assertCount(1, $noSessionLogs);
        $this->assertCount(1, $noStudentLogs);
        $this->assertCount(1, $noRegistrationLogs);
        $this->assertCount(1, $noLessonLogs);
    }

    /**
     * 测试 findOneBy 查找不存在的记录
     */
    public function testFindOneByWithNonExistentCriteriaReturnsNull(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Existing log');
        $log->setContext([]);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $nonExistent = $this->repository->findOneBy(['logLevel' => 'NONEXISTENT']);

        $this->assertNull($nonExistent);
    }

    /**
     * 测试 findBy 查找不存在的记录
     */
    public function testFindByWithNonExistentCriteriaReturnsEmptyArray(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Existing log');
        $log->setContext([]);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $nonExistent = $this->repository->findBy(['logLevel' => 'NONEXISTENT']);

        $this->assertIsArray($nonExistent);
        $this->assertEmpty($nonExistent);
    }

    /**
     * 测试按枚举类型查询
     */
    public function testFindByActionEnumWorksCorrectly(): void
    {
        $actions = [LearnAction::START, LearnAction::PAUSE, LearnAction::START];

        foreach ($actions as $index => $action) {
            $log = new LearnLog();
            $log->setAction($action);
            $log->setLogLevel('INFO');
            $log->setMessage("Log {$index}");
            $log->setContext([]);
            self::getEntityManager()->persist($log);
        }

        self::getEntityManager()->flush();

        $startLogs = $this->repository->findBy(['action' => LearnAction::START]);

        $this->assertCount(2, $startLogs);
        foreach ($startLogs as $log) {
            $this->assertEquals(LearnAction::START, $log->getAction());
        }
    }

    /**
     * 测试关联字段的 count 查询
     */
    public function testCountWithAssociationFieldsWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel('INFO');
        $log1->setMessage('With session');
        $log1->setContext([]);
        $log1->setLearnSession(null);
        $log1->setStudent(null);
        $log1->setRegistration(null);
        $log1->setLesson(null);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('INFO');
        $log2->setMessage('Without session');
        $log2->setContext([]);
        $log2->setLearnSession(null);
        $log2->setStudent(null);
        $log2->setRegistration(null);
        $log2->setLesson(null);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $noSessionCount = $this->repository->count(['learnSession' => null]);
        $noStudentCount = $this->repository->count(['student' => null]);
        $noRegistrationCount = $this->repository->count(['registration' => null]);
        $noLessonCount = $this->repository->count(['lesson' => null]);

        $this->assertEquals(2, $noSessionCount);
        $this->assertEquals(2, $noStudentCount);
        $this->assertEquals(2, $noRegistrationCount);
        $this->assertEquals(2, $noLessonCount);
    }

    /**
     * 测试关联字段的查询 - learnSession
     */
    public function testFindByLearnSessionWorksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Test log');
        $log->setContext([]);
        $log->setLearnSession(null);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $found = $this->repository->findBy(['learnSession' => null]);

        $this->assertCount(1, $found);
        $this->assertNull($found[0]->getLearnSession());
    }

    /**
     * 测试关联字段的查询 - student
     */
    public function testFindByStudentWorksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Test log');
        $log->setContext([]);
        $log->setStudent(null);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $found = $this->repository->findBy(['student' => null]);

        $this->assertCount(1, $found);
        $this->assertNull($found[0]->getStudent());
    }

    /**
     * 测试关联字段的查询 - registration
     */
    public function testFindByRegistrationWorksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Test log');
        $log->setContext([]);
        $log->setRegistration(null);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $found = $this->repository->findBy(['registration' => null]);

        $this->assertCount(1, $found);
        $this->assertNull($found[0]->getRegistration());
    }

    /**
     * 测试关联字段的查询 - lesson
     */
    public function testFindByLessonWorksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setAction(LearnAction::START);
        $log->setLogLevel('INFO');
        $log->setMessage('Test log');
        $log->setContext([]);
        $log->setLesson(null);

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        $found = $this->repository->findBy(['lesson' => null]);

        $this->assertCount(1, $found);
        $this->assertNull($found[0]->getLesson());
    }

    /**
     * 测试可空字段的 count IS NULL 查询
     */
    public function testCountWithNullableFieldsWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel(null);
        $log1->setMessage(null);
        $log1->setContext(null);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('INFO');
        $log2->setMessage('Not null');
        $log2->setContext(['key' => 'value']);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $nullLevelCount = $this->repository->count(['logLevel' => null]);
        $nullMessageCount = $this->repository->count(['message' => null]);
        $nullContextCount = $this->repository->count(['context' => null]);

        $this->assertEquals(1, $nullLevelCount);
        $this->assertEquals(1, $nullMessageCount);
        $this->assertEquals(1, $nullContextCount);
    }

    /**
     * 测试可空字段的 IS NULL 查询 - logLevel
     */
    public function testFindByLogLevelNullWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel(null);
        $log1->setMessage('Null level');
        $log1->setContext([]);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('INFO');
        $log2->setMessage('Non-null level');
        $log2->setContext([]);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $nullLogs = $this->repository->findBy(['logLevel' => null]);

        $this->assertCount(1, $nullLogs);
        $this->assertNull($nullLogs[0]->getLogLevel());
    }

    /**
     * 测试可空字段的 IS NULL 查询 - message
     */
    public function testFindByMessageNullWorksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setAction(LearnAction::START);
        $log1->setLogLevel('INFO');
        $log1->setMessage(null);
        $log1->setContext([]);

        $log2 = new LearnLog();
        $log2->setAction(LearnAction::PAUSE);
        $log2->setLogLevel('INFO');
        $log2->setMessage('Non-null message');
        $log2->setContext([]);

        self::getEntityManager()->persist($log1);
        self::getEntityManager()->persist($log2);
        self::getEntityManager()->flush();

        $nullLogs = $this->repository->findBy(['message' => null]);

        $this->assertCount(1, $nullLogs);
        $this->assertNull($nullLogs[0]->getMessage());
    }

    protected function createNewEntity(): object
    {
        $entity = new LearnLog();
        $entity->setAction(LearnAction::START);

        return $entity;
    }

    /** @return ServiceEntityRepository<LearnLog> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
