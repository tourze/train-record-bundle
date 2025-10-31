<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Cache\CacheInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Service\BehaviorDataProcessor;
use Tourze\TrainRecordBundle\Service\EffectiveTimeCalculator;

/**
 * @internal
 */
#[CoversClass(EffectiveTimeCalculator::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveTimeCalculatorTest extends AbstractIntegrationTestCase
{
    private EffectiveTimeCalculator $effectiveTimeCalculator;

    private BehaviorDataProcessor|MockObject $behaviorProcessor;

    private EffectiveStudyRecordRepository|MockObject $recordRepository;

    private CacheInterface|MockObject $cache;

    protected function onSetUp(): void
    {
        $this->behaviorProcessor = $this->createMock(BehaviorDataProcessor::class);
        $this->recordRepository = $this->createMock(EffectiveStudyRecordRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->effectiveTimeCalculator = self::getService(EffectiveTimeCalculator::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(EffectiveTimeCalculator::class);
        $this->assertInstanceOf(EffectiveTimeCalculator::class, $service);
    }

    public function testCalculateEffectiveTimeWithValidBehaviorData(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setTotalDuration(3600.0); // 1 hour

        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200, 'duration' => 10.0],
            ['action' => 'scroll', 'timestamp' => 1640995260, 'duration' => 5.0],
        ];

        // Mock behavior processor calculations
        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateFocusRatio')
            ->with($behaviorData)
            ->willReturn(0.8)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateInteractionRatio')
            ->with($behaviorData)
            ->willReturn(0.9)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateContinuityRatio')
            ->with($behaviorData)
            ->willReturn(0.95)
        ;

        $effectiveTime = $this->effectiveTimeCalculator->calculateEffectiveTime($record, $behaviorData);

        // Expected calculation: 3600 * 0.8 (filter) * 0.8 * 0.9 * 0.95 = 1958.4
        $this->assertEqualsWithDelta(1958.4, $effectiveTime, 0.1);
        $this->assertLessThanOrEqual($record->getTotalDuration(), $effectiveTime);
    }

    public function testCalculateEffectiveTimeWithEmptyBehaviorData(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setTotalDuration(1800.0); // 30 minutes

        $behaviorData = [];

        // Mock behavior processor calculations for empty data
        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateFocusRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateInteractionRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateContinuityRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $effectiveTime = $this->effectiveTimeCalculator->calculateEffectiveTime($record, $behaviorData);

        $this->assertEquals(0.0, $effectiveTime);
    }

    public function testCalculateEffectiveTimeCapsByTotalDuration(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setTotalDuration(600.0); // 10 minutes

        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200, 'duration' => 300.0],
        ];

        // Mock very high efficiency ratios that would exceed total duration
        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateFocusRatio')
            ->with($behaviorData)
            ->willReturn(1.0)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateInteractionRatio')
            ->with($behaviorData)
            ->willReturn(1.0)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('calculateContinuityRatio')
            ->with($behaviorData)
            ->willReturn(1.0)
        ;

        $effectiveTime = $this->effectiveTimeCalculator->calculateEffectiveTime($record, $behaviorData);

        // Should be capped at total duration
        $this->assertEquals(600.0, $effectiveTime);
    }

    public function testCheckDailyLimitWithinLimit(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setUserId('user123');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setEffectiveDuration(7200.0); // 2 hours

        // Mock repository to return current daily time
        $this->recordRepository
            ->expects($this->once())
            ->method('getDailyEffectiveTime')
            ->with('user123', $record->getStudyDate())
            ->willReturn(14400.0) // 4 hours already used
        ;

        // Mock cache to return default daily limit
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(self::stringContains('user_study_config_user123'))
            ->willReturnCallback(function (string $cacheKey, callable $cacheCallback) {
                return $cacheCallback();
            })
        ;

        $result = $this->effectiveTimeCalculator->checkDailyLimit($record);

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('reason', $result);
        $this->assertArrayNotHasKey('description', $result);
    }

    public function testCheckDailyLimitExceedsLimit(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setUserId('user456');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setEffectiveDuration(7200.0); // 2 hours
        $record->setTotalDuration(7200.0);

        // Mock repository to return high current daily time
        $this->recordRepository
            ->expects($this->once())
            ->method('getDailyEffectiveTime')
            ->with('user456', $record->getStudyDate())
            ->willReturn(25200.0) // 7 hours already used
        ;

        // Mock cache to return default daily limit (8 hours)
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(self::stringContains('user_study_config_user456'))
            ->willReturnCallback(function (string $cacheKey, callable $cacheCallback) {
                return $cacheCallback();
            })
        ;

        $result = $this->effectiveTimeCalculator->checkDailyLimit($record);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertStringContainsString('日学时累计超限', $result['description']);

        // Check that record was updated
        $this->assertEquals(0.0, $record->getEffectiveDuration());
        $this->assertEquals(7200.0, $record->getInvalidDuration());
    }

    public function testCheckDailyLimitPartiallyExceedsLimit(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setUserId('user789');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setEffectiveDuration(5400.0); // 1.5 hours
        $record->setTotalDuration(5400.0);

        // Mock repository to return current daily time that partially exceeds limit
        $this->recordRepository
            ->expects($this->once())
            ->method('getDailyEffectiveTime')
            ->with('user789', $record->getStudyDate())
            ->willReturn(27000.0) // 7.5 hours already used (0.5 hours until limit)
        ;

        // Mock cache to return default daily limit (8 hours)
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(self::stringContains('user_study_config_user789'))
            ->willReturnCallback(function (string $cacheKey, callable $cacheCallback) {
                return $cacheCallback();
            })
        ;

        $result = $this->effectiveTimeCalculator->checkDailyLimit($record);

        $this->assertTrue($result['valid']); // Still valid because some time remains

        // Check that record was updated with partial time
        $this->assertEquals(1800.0, $record->getEffectiveDuration()); // Only 0.5 hours allowed
        $this->assertEquals(3600.0, $record->getInvalidDuration()); // 1 hour invalid
        $this->assertEquals(StudyTimeStatus::PARTIAL, $record->getStatus());
        $description = $record->getDescription();
        $this->assertNotNull($description);
        $this->assertStringContainsString('部分时长超出日限制', $description);
    }

    #[DataProvider('dailyLimitScenarioProvider')]
    public function testCheckDailyLimitVariousScenarios(
        float $currentDailyTime,
        float $newEffectiveTime,
        float $dailyLimit,
        bool $expectedValid,
        ?InvalidTimeReason $expectedReason = null,
    ): void {
        $record = $this->createEffectiveStudyRecord();
        $record->setUserId('testuser');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setEffectiveDuration($newEffectiveTime);
        $record->setTotalDuration($newEffectiveTime);

        $this->recordRepository
            ->expects($this->once())
            ->method('getDailyEffectiveTime')
            ->willReturn($currentDailyTime)
        ;

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($dailyLimit) {
                return $dailyLimit;
            })
        ;

        $result = $this->effectiveTimeCalculator->checkDailyLimit($record);

        $this->assertEquals($expectedValid, $result['valid']);
        if (null !== $expectedReason) {
            $this->assertArrayHasKey('reason', $result);
            $this->assertEquals($expectedReason, $result['reason']);
        }
    }

    /**
     * @return array<string, array{float, float, float, bool, InvalidTimeReason|null}>
     */
    public static function dailyLimitScenarioProvider(): array
    {
        return [
            'well within limit' => [3600.0, 1800.0, 28800.0, true, null],
            'exactly at limit' => [27000.0, 1800.0, 28800.0, true, null],
            'slightly over limit' => [27000.0, 2700.0, 28800.0, true, null],
            'significantly over limit' => [28800.0, 3600.0, 28800.0, false, InvalidTimeReason::DAILY_LIMIT_EXCEEDED],
            'custom higher limit' => [32400.0, 3600.0, 36000.0, true, null],
        ];
    }

    public function testGetUserDailyLimitUsesCache(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $record->setUserId('cached_user');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));
        $record->setEffectiveDuration(1800.0);

        $this->recordRepository
            ->expects($this->once())
            ->method('getDailyEffectiveTime')
            ->willReturn(0.0)
        ;

        // Verify cache is called with correct key
        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with('user_study_config_cached_user', self::isInstanceOf(\Closure::class))
            ->willReturn(36000.0) // Custom 10-hour limit
        ;

        $result = $this->effectiveTimeCalculator->checkDailyLimit($record);
        $this->assertTrue($result['valid']);
    }

    private function createEffectiveStudyRecord(): EffectiveStudyRecord
    {
        $record = new EffectiveStudyRecord();
        $record->setUserId('test-user');
        $record->setStudyDate(new \DateTimeImmutable('2024-01-01'));

        // Create a mock session
        $session = $this->createMock(LearnSession::class);
        $session->method('getId')->willReturn('test-session-' . uniqid());
        $record->setSession($session);

        return $record;
    }
}
