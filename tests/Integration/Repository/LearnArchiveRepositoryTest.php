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
use Tourze\TrainRecordBundle\Entity\LearnArchive;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * LearnArchiveRepository 集成测试
 */
class LearnArchiveRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LearnArchiveRepository $repository;

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
        
        $repository = $this->entityManager->getRepository(LearnArchive::class);
        $this->assertInstanceOf(LearnArchiveRepository::class, $repository);
        $this->repository = $repository;

        // 创建数据库表结构
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // 清理数据库
        $this->entityManager->createQuery('DELETE FROM ' . LearnArchive::class)->execute();
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * 创建测试档案
     */
    private function createTestArchive(
        string $userId = 'user001',
        string $courseId = 'course001',
        ArchiveStatus $status = ArchiveStatus::ACTIVE,
        ?\DateTimeInterface $expiryDate = null,
        ?\DateTimeInterface $lastVerificationTime = null,
        ArchiveFormat $format = ArchiveFormat::JSON
    ): LearnArchive {
        $archive = new LearnArchive();
        $archive->setUserId($userId);
        $archive->setCourseId($courseId);
        $archive->setArchiveStatus($status);
        $archive->setArchiveFormat($format);
        $archive->setArchiveDate(new \DateTimeImmutable());
        $archive->setExpiryDate($expiryDate ?? new \DateTimeImmutable('+3 years'));
        $archive->setLastVerificationTime($lastVerificationTime);
        $archive->setFileSize(1024 * 100); // 100KB
        $archive->setChecksum('abc123def456');
        $archive->setMetadata(json_encode(['version' => '1.0']));

        $this->entityManager->persist($archive);
        $this->entityManager->flush();

        return $archive;
    }

    /**
     * 测试根据用户查找档案
     */
    public function test_findByUser_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001');
        $this->createTestArchive('user001', 'course002');
        $this->createTestArchive('user002', 'course001');

        $results = $this->repository->findByUser('user001');

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertEquals('user001', $archive->getUserId());
        }
    }

    /**
     * 测试根据用户和课程查找档案
     */
    public function test_findByUserAndCourse_returnsCorrectRecord(): void
    {
        $this->createTestArchive('user001', 'course001');
        $this->createTestArchive('user001', 'course002');
        $this->createTestArchive('user002', 'course001');

        $result = $this->repository->findByUserAndCourse('user001', 'course001');

        $this->assertNotNull($result);
        $this->assertEquals('user001', $result->getUserId());
        $this->assertEquals('course001', $result->getCourseId());
    }

    /**
     * 测试查找不存在的用户和课程档案
     */
    public function test_findByUserAndCourse_withNonExistent_returnsNull(): void
    {
        $this->createTestArchive('user001', 'course001');

        $result = $this->repository->findByUserAndCourse('user001', 'course999');

        $this->assertNull($result);
    }

    /**
     * 测试查找已过期的档案
     */
    public function test_findExpired_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 week'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+1 day'));

        $results = $this->repository->findExpired();

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThan(new \DateTimeImmutable(), $archive->getExpiryDate());
        }
    }

    /**
     * 测试根据状态查找档案
     */
    public function test_findByStatus_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED);

        $results = $this->repository->findByStatus(ArchiveStatus::ACTIVE);

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertEquals(ArchiveStatus::ACTIVE, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试档案统计
     */
    public function test_getArchiveStats_returnsCorrectStats(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ARCHIVED);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED);

        $stats = $this->repository->getArchiveStats();

        $this->assertEquals(4, $stats['totalArchives']);
        $this->assertEquals(2, $stats['activeCount']);
        $this->assertEquals(1, $stats['archivedCount']);
        $this->assertEquals(1, $stats['expiredCount']);
    }

    /**
     * 测试查找需要验证的档案
     */
    public function test_findNeedVerification_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, null, null);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, null, new \DateTimeImmutable('-2 months'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, null, new \DateTimeImmutable('-2 weeks'));

        $results = $this->repository->findNeedVerification();

        $this->assertCount(2, $results);
        // 验证返回的是需要验证的档案（null或超过1个月）
        foreach ($results as $archive) {
            $lastVerification = $archive->getLastVerificationTime();
            $this->assertTrue(
                $lastVerification === null || 
                $lastVerification < new \DateTimeImmutable('-1 month')
            );
        }
    }

    /**
     * 测试查找即将过期的档案
     */
    public function test_findExpiringSoon_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+10 days'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+20 days'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+40 days'));
        $this->createTestArchive('user004', 'course004', ArchiveStatus::EXPIRED, new \DateTimeImmutable('+10 days'));

        $results = $this->repository->findExpiringSoon(30);

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThanOrEqual(new \DateTimeImmutable('+30 days'), $archive->getExpiryDate());
            $this->assertNotEquals(ArchiveStatus::EXPIRED, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试查找已过期需要更新状态的档案
     */
    public function test_findExpiredArchives_returnsCorrectRecords(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ARCHIVED, new \DateTimeImmutable('-1 week'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::EXPIRED, new \DateTimeImmutable('-1 month'));
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ACTIVE, new \DateTimeImmutable('+1 day'));

        $results = $this->repository->findExpiredArchives();

        $this->assertCount(2, $results);
        foreach ($results as $archive) {
            $this->assertLessThan(new \DateTimeImmutable(), $archive->getExpiryDate());
            $this->assertNotEquals(ArchiveStatus::EXPIRED, $archive->getArchiveStatus());
        }
    }

    /**
     * 测试按状态统计数量
     */
    public function test_countByStatus_returnsCorrectCount(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ARCHIVED);

        $activeCount = $this->repository->countByStatus(ArchiveStatus::ACTIVE);
        $archivedCount = $this->repository->countByStatus(ArchiveStatus::ARCHIVED);
        $expiredCount = $this->repository->countByStatus(ArchiveStatus::EXPIRED);

        $this->assertEquals(3, $activeCount);
        $this->assertEquals(1, $archivedCount);
        $this->assertEquals(0, $expiredCount);
    }

    /**
     * 测试获取格式分布
     */
    public function test_getFormatDistribution_returnsCorrectDistribution(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::JSON);
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::JSON);
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::XML);
        $this->createTestArchive('user004', 'course004', ArchiveStatus::ACTIVE, null, null, ArchiveFormat::CSV);

        $distribution = $this->repository->getFormatDistribution();

        $this->assertCount(3, $distribution);
        
        $formatCounts = [];
        foreach ($distribution as $item) {
            $formatCounts[$item['format']->value] = $item['count'];
        }
        
        $this->assertEquals(2, $formatCounts[ArchiveFormat::JSON->value]);
        $this->assertEquals(1, $formatCounts[ArchiveFormat::XML->value]);
        $this->assertEquals(1, $formatCounts[ArchiveFormat::CSV->value]);
    }

    /**
     * 测试空数据统计
     */
    public function test_getArchiveStats_withNoData_returnsZeros(): void
    {
        $stats = $this->repository->getArchiveStats();

        $this->assertEquals(0, $stats['totalArchives']);
        $this->assertEquals(0, $stats['activeCount']);
        $this->assertEquals(0, $stats['archivedCount']);
        $this->assertEquals(0, $stats['expiredCount']);
    }

    /**
     * 测试过期日期排序
     */
    public function test_findExpired_orderedByExpiryDate(): void
    {
        $this->createTestArchive('user001', 'course001', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-3 days'));
        $this->createTestArchive('user002', 'course002', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 day'));
        $this->createTestArchive('user003', 'course003', ArchiveStatus::ACTIVE, new \DateTimeImmutable('-1 week'));

        $results = $this->repository->findExpired();

        $this->assertCount(3, $results);
        // 验证按过期日期升序排列
        $previousDate = null;
        foreach ($results as $archive) {
            if ($previousDate !== null) {
                $this->assertGreaterThanOrEqual($previousDate, $archive->getExpiryDate());
            }
            $previousDate = $archive->getExpiryDate();
        }
    }
}