<?php

namespace Tourze\TrainRecordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Service\BehaviorDataProcessor;
use Tourze\TrainRecordBundle\Service\StudyTimeValidator;

/**
 * @internal
 */
#[CoversClass(StudyTimeValidator::class)]
#[RunTestsInSeparateProcesses]
final class StudyTimeValidatorTest extends AbstractIntegrationTestCase
{
    private StudyTimeValidator $studyTimeValidator;

    private BehaviorDataProcessor|MockObject $behaviorProcessor;

    protected function onSetUp(): void
    {
        $this->behaviorProcessor = $this->createMock(BehaviorDataProcessor::class);
        self::getContainer()->set(BehaviorDataProcessor::class, $this->behaviorProcessor);
        $this->studyTimeValidator = self::getService(StudyTimeValidator::class);
    }

    public function testServiceCanBeInstantiated(): void
    {
        $service = self::getService(StudyTimeValidator::class);
        $this->assertInstanceOf(StudyTimeValidator::class, $service);
    }

    public function testValidateStudyTimeWithBrowsingOrTesting(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'browse_info', 'duration' => 5.0],
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(true)
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::BROWSING_WEB_INFO, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('浏览网页信息或在线测试期间不计入有效学时', $result['description']);
    }

    public function testValidateStudyTimeWithAuthenticationFailure(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'auth_failed', 'duration' => 0.0],
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(true)
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('身份验证失败后的学习时长', $result['description']);
    }

    public function testValidateStudyTimeWithInteractionTimeout(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'timestamp' => 1640995200],
            ['action' => 'click', 'timestamp' => 1640995501], // 301 seconds later
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->with($behaviorData, 300)
            ->willReturn([
                'valid' => false,
                'description' => '交互间隔超过300秒',
            ])
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::INTERACTION_TIMEOUT, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('交互间隔超过300秒', $result['description']);
    }

    public function testValidateStudyTimeWithIncompleteTest(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'scroll', 'duration' => 5.0],
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->with($behaviorData, 300)
            ->willReturn(['valid' => true])
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasCompletedTest')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        // Mock the validator to require test (override the default false)
        $validator = $this->getMockBuilder(StudyTimeValidator::class)
            ->setConstructorArgs([$this->behaviorProcessor])
            ->onlyMethods(['isTestRequired'])
            ->getMock()
        ;

        $validator
            ->expects($this->once())
            ->method('isTestRequired')
            ->with($record)
            ->willReturn(true)
        ;

        $result = $validator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('未完成课程测试的学习时长', $result['description']);
    }

    public function testValidateStudyTimeWithValidData(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'scroll', 'duration' => 5.0],
            ['action' => 'test_completed', 'duration' => 1.0],
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->with($behaviorData, 300)
            ->willReturn(['valid' => true])
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasCompletedTest')
            ->with($behaviorData)
            ->willReturn(true)
        ;

        // Create partial mock to control isTestRequired
        $validator = $this->getMockBuilder(StudyTimeValidator::class)
            ->setConstructorArgs([$this->behaviorProcessor])
            ->onlyMethods(['isTestRequired'])
            ->getMock()
        ;

        $validator
            ->expects($this->once())
            ->method('isTestRequired')
            ->with($record)
            ->willReturn(true)
        ;

        $result = $validator->validateStudyTime($record, $behaviorData);

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('reason', $result);
        $this->assertArrayNotHasKey('description', $result);
    }

    public function testValidateStudyTimeWithNoTestRequired(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [
            ['action' => 'click', 'duration' => 10.0],
            ['action' => 'scroll', 'duration' => 5.0],
        ];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->with($behaviorData, 300)
            ->willReturn(['valid' => true])
        ;

        // When no test is required, hasCompletedTest should not be called
        $this->behaviorProcessor
            ->expects($this->never())
            ->method('hasCompletedTest')
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertTrue($result['valid']);
    }

    /**
     * @param array<string, mixed> $interactionCheckResult
     */
    #[DataProvider('validationScenarioProvider')]
    public function testValidateStudyTimeVariousScenarios(
        bool $isBrowsingOrTesting,
        bool $hasAuthFailure,
        array $interactionCheckResult,
        bool $isTestRequired,
        bool $hasCompletedTest,
        bool $expectedValid,
        ?InvalidTimeReason $expectedReason = null,
    ): void {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [['action' => 'test']];

        $this->setupBehaviorProcessorMocks(
            $isBrowsingOrTesting,
            $hasAuthFailure,
            $interactionCheckResult,
            $hasCompletedTest,
            $isTestRequired
        );

        $validator = $this->createValidatorMock($isBrowsingOrTesting, $hasAuthFailure, $interactionCheckResult, $isTestRequired);
        $result = $validator->validateStudyTime($record, $behaviorData);

        $this->assertIsArray($result);
        $this->assertValidationResult($result, $expectedValid, $expectedReason);
    }

    /**
     * @return array<string, array{bool, bool, array<string, mixed>, bool, bool, bool, InvalidTimeReason|null}>
     */
    public static function validationScenarioProvider(): array
    {
        return [
            'browsing detected' => [
                true,  // isBrowsingOrTesting
                false, // hasAuthFailure
                ['valid' => true], // interactionCheckResult
                false, // isTestRequired
                false, // hasCompletedTest
                false, // expectedValid
                InvalidTimeReason::BROWSING_WEB_INFO, // expectedReason
            ],
            'auth failure' => [
                false, // isBrowsingOrTesting
                true,  // hasAuthFailure
                ['valid' => true], // interactionCheckResult
                false, // isTestRequired
                false, // hasCompletedTest
                false, // expectedValid
                InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, // expectedReason
            ],
            'interaction timeout' => [
                false, // isBrowsingOrTesting
                false, // hasAuthFailure
                ['valid' => false, 'description' => 'Timeout'], // interactionCheckResult
                false, // isTestRequired
                false, // hasCompletedTest
                false, // expectedValid
                InvalidTimeReason::INTERACTION_TIMEOUT, // expectedReason
            ],
            'test required but not completed' => [
                false, // isBrowsingOrTesting
                false, // hasAuthFailure
                ['valid' => true], // interactionCheckResult
                true,  // isTestRequired
                false, // hasCompletedTest
                false, // expectedValid
                InvalidTimeReason::INCOMPLETE_COURSE_TEST, // expectedReason
            ],
            'test required and completed' => [
                false, // isBrowsingOrTesting
                false, // hasAuthFailure
                ['valid' => true], // interactionCheckResult
                true,  // isTestRequired
                true,  // hasCompletedTest
                true,  // expectedValid
                null,  // expectedReason
            ],
            'no test required' => [
                false, // isBrowsingOrTesting
                false, // hasAuthFailure
                ['valid' => true], // interactionCheckResult
                false, // isTestRequired
                false, // hasCompletedTest
                true,  // expectedValid
                null,  // expectedReason
            ],
        ];
    }

    /**
     * @param array<string, mixed> $interactionCheckResult
     */
    private function setupBehaviorProcessorMocks(
        bool $isBrowsingOrTesting,
        bool $hasAuthFailure,
        array $interactionCheckResult,
        bool $hasCompletedTest,
        bool $isTestRequired,
    ): void {
        $this->behaviorProcessor->expects($this->once())->method('isBrowsingOrTesting')->willReturn($isBrowsingOrTesting);

        if (!$isBrowsingOrTesting) {
            $this->behaviorProcessor->expects($this->once())->method('hasAuthenticationFailure')->willReturn($hasAuthFailure);

            if (!$hasAuthFailure) {
                $this->behaviorProcessor->expects($this->once())->method('checkInteractionTimeout')->willReturn($interactionCheckResult);
                $this->setupTestCompletionMocks($interactionCheckResult, $isTestRequired, $hasCompletedTest);
            }
        }
    }

    /** @param array<string, mixed> $interactionCheckResult */
    private function setupTestCompletionMocks(array $interactionCheckResult, bool $isTestRequired, bool $hasCompletedTest): void
    {
        $isValid = $this->isInteractionValid($interactionCheckResult);
        if ($isValid && $isTestRequired) {
            $this->behaviorProcessor->expects($this->once())->method('hasCompletedTest')->willReturn($hasCompletedTest);
        } elseif ($isValid && !$isTestRequired) {
            $this->behaviorProcessor->expects($this->never())->method('hasCompletedTest');
        }
    }

    /** @param array<string, mixed> $interactionCheckResult */
    private function createValidatorMock(bool $isBrowsingOrTesting, bool $hasAuthFailure, array $interactionCheckResult, bool $isTestRequired): StudyTimeValidator
    {
        $validator = $this->getMockBuilder(StudyTimeValidator::class)
            ->setConstructorArgs([$this->behaviorProcessor])
            ->onlyMethods(['isTestRequired'])
            ->getMock()
        ;

        $isValid = $this->isInteractionValid($interactionCheckResult);
        if (!$isBrowsingOrTesting && !$hasAuthFailure && $isValid) {
            $validator->expects($this->once())->method('isTestRequired')->willReturn($isTestRequired);
        } else {
            $validator->expects($this->never())->method('isTestRequired');
        }

        return $validator;
    }

    /** @param array<string, mixed> $interactionCheckResult */
    private function isInteractionValid(array $interactionCheckResult): bool
    {
        $validFlag = $interactionCheckResult['valid'] ?? null;

        return is_bool($validFlag) && $validFlag;
    }

    /** @param array<string, mixed> $result */
    private function assertValidationResult(array $result, bool $expectedValid, ?InvalidTimeReason $expectedReason): void
    {
        $this->assertEquals($expectedValid, $result['valid']);
        if (null !== $expectedReason) {
            $this->assertArrayHasKey('reason', $result);
            $this->assertEquals($expectedReason, $result['reason']);
        } else {
            $this->assertArrayNotHasKey('reason', $result);
        }
    }

    public function testValidateStudyTimeWithEmptyBehaviorData(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->with($behaviorData)
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->with($behaviorData, 300)
            ->willReturn(['valid' => true])
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertTrue($result['valid']);
    }

    public function testIsTestRequiredDefaultImplementation(): void
    {
        $record = $this->createEffectiveStudyRecord();

        // Test the default implementation returns false
        $result = $this->studyTimeValidator->isTestRequired($record);

        $this->assertFalse($result);
    }

    public function testIsTestRequiredWithDifferentRecords(): void
    {
        // Test with multiple different records to ensure consistent behavior
        $record1 = $this->createEffectiveStudyRecord();
        $record1->setUserId('user1');

        $record2 = $this->createEffectiveStudyRecord();
        $record2->setUserId('user2');

        $this->assertFalse($this->studyTimeValidator->isTestRequired($record1));
        $this->assertFalse($this->studyTimeValidator->isTestRequired($record2));
    }

    public function testValidateStudyTimeWithInteractionTimeoutDefaultMessage(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [['action' => 'test']];

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('hasAuthenticationFailure')
            ->willReturn(false)
        ;

        $this->behaviorProcessor
            ->expects($this->once())
            ->method('checkInteractionTimeout')
            ->willReturn([
                'valid' => false,
                // No description provided to test default fallback
            ])
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::INTERACTION_TIMEOUT, $result['reason']);
        $this->assertArrayHasKey('description', $result);
        $this->assertEquals('Interaction timeout detected', $result['description']);
    }

    public function testValidateStudyTimeExecutionOrder(): void
    {
        $record = $this->createEffectiveStudyRecord();
        $behaviorData = [['action' => 'test']];

        // This test verifies that validation stops early when browsing is detected
        $this->behaviorProcessor
            ->expects($this->once())
            ->method('isBrowsingOrTesting')
            ->willReturn(true)
        ;

        // These methods should NOT be called because browsing was detected
        $this->behaviorProcessor
            ->expects($this->never())
            ->method('hasAuthenticationFailure')
        ;

        $this->behaviorProcessor
            ->expects($this->never())
            ->method('checkInteractionTimeout')
        ;

        $this->behaviorProcessor
            ->expects($this->never())
            ->method('hasCompletedTest')
        ;

        $result = $this->studyTimeValidator->validateStudyTime($record, $behaviorData);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('reason', $result);
        $this->assertEquals(InvalidTimeReason::BROWSING_WEB_INFO, $result['reason']);
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
