<?php

namespace Tourze\TrainRecordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * @internal
 */
#[CoversClass(EffectiveStudyRecord::class)]
#[RunTestsInSeparateProcesses]
final class EffectiveStudyRecordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new EffectiveStudyRecord();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'userId' => ['userId', 'user123'];
        yield 'studyDate' => ['studyDate', new \DateTimeImmutable()];
        yield 'startTime' => ['startTime', new \DateTimeImmutable()];
        yield 'endTime' => ['endTime', new \DateTimeImmutable()];
        yield 'totalDuration' => ['totalDuration', 3600.5];
        yield 'effectiveDuration' => ['effectiveDuration', 2400.75];
        yield 'invalidDuration' => ['invalidDuration', 1200.25];
        yield 'status' => ['status', StudyTimeStatus::VALID];
        yield 'invalidReason' => ['invalidReason', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN];
        yield 'description' => ['description', '测试描述'];
        yield 'qualityScore' => ['qualityScore', 8.5];
        yield 'focusScore' => ['focusScore', 0.8];
        yield 'interactionScore' => ['interactionScore', 0.7];
        yield 'continuityScore' => ['continuityScore', 0.9];
        yield 'evidenceData' => ['evidenceData', ['type' => 'test', 'data' => []]];
        yield 'behaviorStats' => ['behaviorStats', ['focus_time' => 1800]];
        yield 'validationResult' => ['validationResult', ['is_valid' => true]];
        yield 'reviewComment' => ['reviewComment', '审核意见'];
        yield 'reviewedBy' => ['reviewedBy', 'admin'];
        yield 'reviewTime' => ['reviewTime', new \DateTimeImmutable()];
        yield 'includeInDailyTotal' => ['includeInDailyTotal', true];
        yield 'studentNotified' => ['studentNotified', false];
        yield 'createTime' => ['createTime', new \DateTimeImmutable()];
        yield 'updateTime' => ['updateTime', new \DateTimeImmutable()];
    }

    public function testGetEffectiveRate(): void
    {
        $entity = new EffectiveStudyRecord();
        // 测试总时长为0的情况
        $entity->setTotalDuration(0.0);
        $this->assertEquals(0.0, $entity->getEffectiveRate());

        // 测试正常情况
        $entity->setTotalDuration(3600.0);
        $entity->setEffectiveDuration(2400.0);
        $this->assertEquals(0.6667, round($entity->getEffectiveRate(), 4));
    }

    public function testGetInvalidRate(): void
    {
        $entity = new EffectiveStudyRecord();
        // 测试总时长为0的情况
        $entity->setTotalDuration(0.0);
        $this->assertEquals(0.0, $entity->getInvalidRate());

        // 测试正常情况
        $entity->setTotalDuration(3600.0);
        $entity->setInvalidDuration(1200.0);
        $this->assertEquals(0.3333, round($entity->getInvalidRate(), 4));
    }

    public function testIsHighQuality(): void
    {
        $entity = new EffectiveStudyRecord();
        // 测试质量评分为null
        $this->assertFalse($entity->isHighQuality());

        // 测试低质量评分
        $entity->setQualityScore(7.5);
        $this->assertFalse($entity->isHighQuality());

        // 测试高质量评分
        $entity->setQualityScore(8.5);
        $this->assertTrue($entity->isHighQuality());
    }

    public function testNeedsReview(): void
    {
        $entity = new EffectiveStudyRecord();
        $entity->setStatus(StudyTimeStatus::PENDING);
        $this->assertTrue($entity->needsReview());

        $entity->setStatus(StudyTimeStatus::VALID);
        $this->assertFalse($entity->needsReview());
    }

    public function testMarkAsReviewed(): void
    {
        $entity = new EffectiveStudyRecord();
        $status = StudyTimeStatus::VALID;
        $reviewedBy = 'admin';
        $comment = '审核通过';

        $entity->markAsReviewed($status, $reviewedBy, $comment);

        $this->assertEquals($status, $entity->getStatus());
        $this->assertEquals($reviewedBy, $entity->getReviewedBy());
        $this->assertEquals($comment, $entity->getReviewComment());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getReviewTime());
    }

    public function testMarkAsInvalid(): void
    {
        $entity = new EffectiveStudyRecord();
        $entity->setTotalDuration(3600.0);
        $entity->setEffectiveDuration(2400.0);

        $reason = InvalidTimeReason::MULTIPLE_DEVICE_LOGIN;
        $description = '检测到多设备登录';

        $entity->markAsInvalid($reason, $description);

        $this->assertEquals(StudyTimeStatus::INVALID, $entity->getStatus());
        $this->assertEquals($reason, $entity->getInvalidReason());
        $this->assertEquals($description, $entity->getDescription());
        $this->assertEquals(0.0, $entity->getEffectiveDuration());
        $this->assertEquals(3600.0, $entity->getInvalidDuration());
        $this->assertFalse($entity->isIncludeInDailyTotal());
    }

    public function testMarkAsValid(): void
    {
        $entity = new EffectiveStudyRecord();
        $entity->setTotalDuration(3600.0);

        // 测试不指定有效时长
        $entity->markAsValid();
        $this->assertEquals(StudyTimeStatus::VALID, $entity->getStatus());
        $this->assertNull($entity->getInvalidReason());
        $this->assertEquals(3600.0, $entity->getEffectiveDuration());
        $this->assertEquals(0.0, $entity->getInvalidDuration());
        $this->assertTrue($entity->isIncludeInDailyTotal());

        // 测试指定有效时长
        $entity->markAsValid(2400.0);
        $this->assertEquals(2400.0, $entity->getEffectiveDuration());
        $this->assertEquals(1200.0, $entity->getInvalidDuration());
    }

    public function testAddEvidence(): void
    {
        $entity = new EffectiveStudyRecord();
        $type = 'screenshot';
        $data = ['filename' => 'evidence.png', 'size' => 1024];

        $entity->addEvidence($type, $data);

        $evidenceData = $entity->getEvidenceData();
        $this->assertIsArray($evidenceData);
        $this->assertCount(1, $evidenceData);
        $firstEvidence = reset($evidenceData);
        $this->assertIsArray($firstEvidence);
        $this->assertEquals($type, $firstEvidence['type']);
        $this->assertEquals($data, $firstEvidence['data']);
        $this->assertArrayHasKey('timestamp', $firstEvidence);
    }

    public function testGetFormattedDuration(): void
    {
        $entity = new EffectiveStudyRecord();
        // 测试不同时长格式
        $this->assertEquals('01:00:00', $entity->getFormattedDuration(3600));
        $this->assertEquals('01:30:45', $entity->getFormattedDuration(5445));
        $this->assertEquals('00:00:30', $entity->getFormattedDuration(30));
    }

    public function testGetEfficiencyDescription(): void
    {
        $entity = new EffectiveStudyRecord();
        $entity->setTotalDuration(100.0);

        // 测试优秀
        $entity->setEffectiveDuration(95.0);
        $this->assertEquals('优秀', $entity->getEfficiencyDescription());

        // 测试良好
        $entity->setEffectiveDuration(85.0);
        $this->assertEquals('良好', $entity->getEfficiencyDescription());

        // 测试一般
        $entity->setEffectiveDuration(70.0);
        $this->assertEquals('一般', $entity->getEfficiencyDescription());

        // 测试较差
        $entity->setEffectiveDuration(50.0);
        $this->assertEquals('较差', $entity->getEfficiencyDescription());

        // 测试很差
        $entity->setEffectiveDuration(20.0);
        $this->assertEquals('很差', $entity->getEfficiencyDescription());
    }

    public function testToString(): void
    {
        $entity = new EffectiveStudyRecord();
        $this->assertIsString((string) $entity);
        $this->assertStringStartsWith('esr_', (string) $entity);
    }
}
