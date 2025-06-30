<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserAgentBundle\DoctrineUserAgentBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\TrainRecordBundle\Entity\LearnLog;
use Tourze\TrainRecordBundle\Repository\LearnLogRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnLogRepository 集成测试
 */
class LearnLogRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnLogRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineUserAgentBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Core bundles
            TrainRecordBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        
        $repository = $this->entityManager->getRepository(LearnLog::class);
        $this->assertInstanceOf(LearnLogRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnLog::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 测试仓储类存在
     */
    public function test_repository_exists(): void
    {
        $this->assertInstanceOf(LearnLogRepository::class, $this->repository);
    }

    /**
     * 测试创建和查找日志
     */
    public function test_createAndFind_worksCorrectly(): void
    {
        $log = new LearnLog();
        $log->setLogLevel('INFO');
        $log->setMessage('Test log message');
        $log->setContext(['session' => 'session001']);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $foundLog = $this->repository->find($log->getId());

        $this->assertNotNull($foundLog);
        $this->assertEquals('INFO', $foundLog->getLogLevel());
        $this->assertEquals('Test log message', $foundLog->getMessage());
    }

    /**
     * 测试 findOneBy 方法
     */
    public function test_findOneBy_worksCorrectly(): void
    {
        $log1 = new LearnLog();
        $log1->setLogLevel('ERROR');
        $log1->setMessage('Error message');
        $log1->setContext(['error_code' => 500]);

        $log2 = new LearnLog();
        $log2->setLogLevel('WARNING');
        $log2->setMessage('Warning message');
        $log2->setContext(['warning_type' => 'performance']);

        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->flush();

        $foundLog = $this->repository->findOneBy(['logLevel' => 'ERROR']);

        $this->assertNotNull($foundLog);
        $this->assertEquals('ERROR', $foundLog->getLogLevel());
        $this->assertEquals('Error message', $foundLog->getMessage());
    }

    /**
     * 测试 findAll 方法
     */
    public function test_findAll_returnsAllRecords(): void
    {
        $logs = [];
        for ($i = 1; $i <= 3; $i++) {
            $log = new LearnLog();
            $log->setLogLevel('INFO');
            $log->setMessage("Log message $i");
            $log->setContext(['index' => $i]);
            $this->entityManager->persist($log);
            $logs[] = $log;
        }

        $this->entityManager->flush();

        $allLogs = $this->repository->findAll();

        $this->assertCount(3, $allLogs);
    }

    /**
     * 测试 findBy 方法
     */
    public function test_findBy_withCriteria_returnsFilteredResults(): void
    {
        $levels = ['INFO', 'INFO', 'ERROR', 'WARNING'];
        
        foreach ($levels as $index => $level) {
            $log = new LearnLog();
            $log->setLogLevel($level);
            $log->setMessage("Message $index");
            $log->setContext(['index' => $index]);
            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();

        $infoLogs = $this->repository->findBy(['logLevel' => 'INFO']);

        $this->assertCount(2, $infoLogs);
        foreach ($infoLogs as $log) {
            $this->assertEquals('INFO', $log->getLogLevel());
        }
    }

    /**
     * 测试 findBy 方法的排序功能
     */
    public function test_findBy_withOrderBy_returnsSortedResults(): void
    {
        $messages = ['Charlie', 'Alpha', 'Bravo'];
        
        foreach ($messages as $message) {
            $log = new LearnLog();
            $log->setLogLevel('INFO');
            $log->setMessage($message);
            $log->setContext([]);
            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();

        $sortedLogs = $this->repository->findBy([], ['message' => 'ASC']);

        $this->assertCount(3, $sortedLogs);
        $this->assertEquals('Alpha', $sortedLogs[0]->getMessage());
        $this->assertEquals('Bravo', $sortedLogs[1]->getMessage());
        $this->assertEquals('Charlie', $sortedLogs[2]->getMessage());
    }

    /**
     * 测试 findBy 方法的限制功能
     */
    public function test_findBy_withLimit_returnsLimitedResults(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $log = new LearnLog();
            $log->setLogLevel('INFO');
            $log->setMessage("Message $i");
            $log->setContext(['index' => $i]);
            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();

        $limitedLogs = $this->repository->findBy([], null, 3);

        $this->assertCount(3, $limitedLogs);
    }

    /**
     * 测试 findBy 方法的偏移功能
     */
    public function test_findBy_withOffset_returnsOffsetResults(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $log = new LearnLog();
            $log->setLogLevel('INFO');
            $log->setMessage("Message $i");
            $log->setContext(['index' => $i]);
            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();

        $offsetLogs = $this->repository->findBy([], ['message' => 'ASC'], 2, 2);

        $this->assertCount(2, $offsetLogs);
        $this->assertEquals('Message 3', $offsetLogs[0]->getMessage());
        $this->assertEquals('Message 4', $offsetLogs[1]->getMessage());
    }

    /**
     * 测试空数据库查询
     */
    public function test_findAll_withEmptyDatabase_returnsEmptyArray(): void
    {
        $allLogs = $this->repository->findAll();

        $this->assertEmpty($allLogs);
    }

    /**
     * 测试查找不存在的记录
     */
    public function test_find_withNonExistentId_returnsNull(): void
    {
        $log = new LearnLog();
        $log->setLogLevel('INFO');
        $log->setMessage('Test');
        $log->setContext([]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $nonExistentLog = $this->repository->find('non-existent-id');

        $this->assertNull($nonExistentLog);
    }

    /**
     * 测试复杂查询条件
     */
    public function test_findBy_withMultipleCriteria_returnsCorrectResults(): void
    {
        $log1 = new LearnLog();
        $log1->setLogLevel('ERROR');
        $log1->setMessage('Database connection failed');
        $log1->setContext(['component' => 'database']);

        $log2 = new LearnLog();
        $log2->setLogLevel('ERROR');
        $log2->setMessage('File not found');
        $log2->setContext(['component' => 'filesystem']);

        $log3 = new LearnLog();
        $log3->setLogLevel('INFO');
        $log3->setMessage('User logged in');
        $log3->setContext(['component' => 'auth']);

        $this->entityManager->persist($log1);
        $this->entityManager->persist($log2);
        $this->entityManager->persist($log3);
        $this->entityManager->flush();

        $errorLogs = $this->repository->findBy(['logLevel' => 'ERROR']);

        $this->assertCount(2, $errorLogs);
        foreach ($errorLogs as $log) {
            $this->assertEquals('ERROR', $log->getLogLevel());
        }
    }
}