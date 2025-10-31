<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Service\BehaviorDataProcessor;
use Tourze\TrainRecordBundle\Service\QualityAssessor;

/**
 * @internal
 */
#[CoversClass(QualityAssessor::class)]
#[RunTestsInSeparateProcesses]
final class QualityAssessorTest extends AbstractIntegrationTestCase
{
    private QualityAssessor $qualityAssessor;

    private BehaviorDataProcessor|MockObject $behaviorProcessor;

    protected function onSetUp(): void
    {
        $this->behaviorProcessor = $this->createMock(BehaviorDataProcessor::class);
        $this->qualityAssessor = self::getService(QualityAssessor::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(QualityAssessor::class);
        $this->assertInstanceOf(QualityAssessor::class, $service);
    }

    public function testCalculateQualityScores(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200, 'duration' => 10.0],
            ['action' => 'scroll', 'timestamp' => 1640995260, 'duration' => 5.0],
        ];

        // Mock behavior processor methods
        $this->behaviorProcessor
            ->expects($this->exactly(2)) // Called twice: once in calculateQualityScore, once directly
            ->method('calculateFocusRatio')
            ->with($behaviorData)
            ->willReturn(0.8)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2)) // Called twice: once in calculateQualityScore, once directly
            ->method('calculateInteractionRatio')
            ->with($behaviorData)
            ->willReturn(0.6)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2)) // Called twice: once in calculateQualityScore, once directly
            ->method('calculateContinuityRatio')
            ->with($behaviorData)
            ->willReturn(0.9)
        ;

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        // Verify scores were set on the record
        $this->assertIsFloat($record->getQualityScore());
        $this->assertGreaterThanOrEqual(0.0, $record->getQualityScore());
        $this->assertLessThanOrEqual(10.0, $record->getQualityScore());

        $this->assertEquals(0.8, $record->getFocusScore());
        $this->assertEquals(0.6, $record->getInteractionScore());
        $this->assertEquals(0.9, $record->getContinuityScore());
    }

    #[DataProvider('qualityScoreCalculationProvider')]
    public function testCalculateQualityScoreCalculation(
        float $focusRatio,
        float $interactionRatio,
        float $continuityRatio,
        float $expectedQualityScore,
    ): void {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
        ];

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateFocusRatio')
            ->willReturn($focusRatio)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateInteractionRatio')
            ->willReturn($interactionRatio)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateContinuityRatio')
            ->willReturn($continuityRatio)
        ;

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        $this->assertEqualsWithDelta($expectedQualityScore, $record->getQualityScore(), 0.01);
    }

    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function qualityScoreCalculationProvider(): array
    {
        return [
            'perfect scores' => [1.0, 1.0, 1.0, 10.0],
            'average scores' => [0.7, 0.5, 0.8, 7.7],
            'low scores' => [0.3, 0.1, 0.2, 3.7],
            'minimum scores (zero)' => [0.0, 0.0, 0.0, 0.0],
            'baseline scores' => [0.5, 0.3, 0.5, 5.0],
        ];
    }

    public function testCalculateQualityScoresWithEmptyBehaviorData(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [];

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateFocusRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateInteractionRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateContinuityRatio')
            ->with($behaviorData)
            ->willReturn(0.0)
        ;

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        // With all zero ratios, quality score should be minimum (0.0)
        $this->assertEquals(0.0, $record->getQualityScore());
        $this->assertEquals(0.0, $record->getFocusScore());
        $this->assertEquals(0.0, $record->getInteractionScore());
        $this->assertEquals(0.0, $record->getContinuityScore());
    }

    #[DataProvider('qualityReviewProvider')]
    public function testNeedsQualityReview(
        float $qualityScore,
        float $focusScore,
        bool $expectedNeedsReview,
    ): void {
        $record = $this->createEffectiveStudyRecord();
        $record->setQualityScore($qualityScore);
        $record->setFocusScore($focusScore);

        $result = $this->qualityAssessor->needsQualityReview($record);

        $this->assertEquals($expectedNeedsReview, $result);
    }

    /**
     * @return array<string, array{float, float, bool}>
     */
    public static function qualityReviewProvider(): array
    {
        return [
            'high quality, high focus - no review' => [8.0, 0.8, false],
            'low quality, high focus - needs review' => [4.0, 0.8, true],
            'high quality, low focus - needs review' => [8.0, 0.5, true],
            'low quality, low focus - needs review' => [4.0, 0.5, true],
            'quality at threshold, focus above - no review' => [6.0, 0.8, false],
            'quality above, focus at threshold - no review' => [7.0, 0.7, false],
            'quality at threshold, focus at threshold - no review' => [6.0, 0.7, false],
            'quality just below threshold - needs review' => [5.99, 0.8, true],
            'focus just below threshold - needs review' => [7.0, 0.69, true],
            'both at minimum values - needs review' => [0.0, 0.0, true],
            'both at maximum values - no review' => [10.0, 1.0, false],
        ];
    }

    public function testNeedsQualityReviewWithUninitializedScores(): void
    {
        $record = $this->createEffectiveStudyRecord();
        // Don't set any scores, they should default to null/0

        // This should handle uninitialized scores gracefully
        $result = $this->qualityAssessor->needsQualityReview($record);

        // With default values (likely 0 or null), review should be needed
        $this->assertTrue($result);
    }

    public function testQualityScoreCalculationBounds(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [['action' => 'test']];

        // Test with extremely high ratios to verify upper bound
        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateFocusRatio')
            ->willReturn(2.0) // Unrealistic but tests bounds
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateInteractionRatio')
            ->willReturn(2.0)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateContinuityRatio')
            ->willReturn(2.0)
        ;

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        // Quality score should be capped at 10.0
        $this->assertEquals(10.0, $record->getQualityScore());
    }

    public function testQualityScoreCalculationNegativeBounds(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [['action' => 'test']];

        // Test with negative ratios to verify lower bound
        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateFocusRatio')
            ->willReturn(-0.5)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateInteractionRatio')
            ->willReturn(-0.2)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateContinuityRatio')
            ->willReturn(-1.0)
        ;

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        // Quality score should be capped at 0.0
        $this->assertEquals(0.0, $record->getQualityScore());
    }

    public function testCalculateQualityScoresUpdatesAllFields(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'scroll', 'duration' => 5.0],
        ];

        $focusRatio = 0.75;
        $interactionRatio = 0.65;
        $continuityRatio = 0.85;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateFocusRatio')
            ->willReturn($focusRatio)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateInteractionRatio')
            ->willReturn($interactionRatio)
        ;

        $this->behaviorProcessor
            ->expects($this->exactly(2))
            ->method('calculateContinuityRatio')
            ->willReturn($continuityRatio)
        ;

        // Verify initial state
        $this->assertNull($record->getQualityScore());
        $this->assertNull($record->getFocusScore());
        $this->assertNull($record->getInteractionScore());
        $this->assertNull($record->getContinuityScore());

        $this->qualityAssessor->calculateQualityScores($record, $behaviorData);

        // Verify all fields are updated
        $this->assertNotNull($record->getQualityScore());
        $this->assertEquals($focusRatio, $record->getFocusScore());
        $this->assertEquals($interactionRatio, $record->getInteractionScore());
        $this->assertEquals($continuityRatio, $record->getContinuityScore());
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
