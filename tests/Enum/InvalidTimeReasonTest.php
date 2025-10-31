<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;

/**
 * InvalidTimeReason 枚举测试
 *
 * @internal
 */
#[CoversClass(InvalidTimeReason::class)]
#[RunTestsInSeparateProcesses]
final class InvalidTimeReasonTest extends AbstractEnumTestCase
{
    #[TestWith([InvalidTimeReason::BROWSING_WEB_INFO, 'browsing_web_info', '浏览网页信息'])]
    #[TestWith([InvalidTimeReason::ONLINE_TESTING, 'online_testing', '在线测试时长'])]
    #[TestWith([InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, 'identity_verification_failed', '身份验证失败'])]
    #[TestWith([InvalidTimeReason::INTERACTION_TIMEOUT, 'interaction_timeout', '交互超时'])]
    #[TestWith([InvalidTimeReason::IDLE_TIMEOUT, 'idle_timeout', '空闲超时'])]
    #[TestWith([InvalidTimeReason::NO_ACTIVITY_DETECTED, 'no_activity_detected', '无活动检测'])]
    #[TestWith([InvalidTimeReason::DAILY_LIMIT_EXCEEDED, 'daily_limit_exceeded', '日累计时长超限'])]
    #[TestWith([InvalidTimeReason::INCOMPLETE_COURSE_TEST, 'incomplete_course_test', '未完成课程测试'])]
    #[TestWith([InvalidTimeReason::WINDOW_FOCUS_LOST, 'window_focus_lost', '窗口失去焦点'])]
    #[TestWith([InvalidTimeReason::PAGE_HIDDEN, 'page_hidden', '页面隐藏'])]
    #[TestWith([InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, 'multiple_device_login', '多设备登录'])]
    #[TestWith([InvalidTimeReason::NETWORK_DISCONNECTED, 'network_disconnected', '网络断开'])]
    #[TestWith([InvalidTimeReason::SYSTEM_ERROR, 'system_error', '系统错误'])]
    #[TestWith([InvalidTimeReason::SUSPICIOUS_BEHAVIOR, 'suspicious_behavior', '可疑行为'])]
    #[TestWith([InvalidTimeReason::MANUAL_EXCLUSION, 'manual_exclusion', '手动排除'])]
    public function testValueAndLabel(InvalidTimeReason $enum, string $expectedValue, string $expectedLabel): void
    {
        self::assertSame($expectedValue, $enum->value);
        self::assertSame($expectedLabel, $enum->getLabel());

        $array = $enum->toArray();
        self::assertSame(['value' => $expectedValue, 'label' => $expectedLabel], $array);
    }

    /**
     * 测试 toArray 方法
     */
    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = InvalidTimeReason::BROWSING_WEB_INFO->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('browsing_web_info', $result['value']);
        $this->assertEquals('浏览网页信息', $result['label']);
    }

    /**
     * 测试获取描述
     */
    public function testGetDescription(): void
    {
        self::assertSame('学员登录后浏览网页信息的时长不计入有效学习时长', InvalidTimeReason::BROWSING_WEB_INFO->getDescription());
        self::assertSame('在线测试过程中的时长不计入有效学习时长', InvalidTimeReason::ONLINE_TESTING->getDescription());
        self::assertSame('身份验证失败提示后的时长不计入有效学习时长', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getDescription());
        self::assertSame('与网络平台交互间隔超过预设值的时长不计入', InvalidTimeReason::INTERACTION_TIMEOUT->getDescription());
        self::assertSame('日累计有效学习时长超过预设值的超出部分不计入', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getDescription());
        self::assertSame('未完成相应课程在线测试时，整个课程学时不予认定', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getDescription());
    }

    /**
     * 测试获取规范依据分类
     */
    public function testGetRegulationCategory(): void
    {
        // 规范 a
        self::assertSame('regulation_a', InvalidTimeReason::BROWSING_WEB_INFO->getRegulationCategory());
        self::assertSame('regulation_a', InvalidTimeReason::ONLINE_TESTING->getRegulationCategory());

        // 规范 b
        self::assertSame('regulation_b', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getRegulationCategory());

        // 规范 c
        self::assertSame('regulation_c', InvalidTimeReason::INTERACTION_TIMEOUT->getRegulationCategory());
        self::assertSame('regulation_c', InvalidTimeReason::IDLE_TIMEOUT->getRegulationCategory());
        self::assertSame('regulation_c', InvalidTimeReason::NO_ACTIVITY_DETECTED->getRegulationCategory());

        // 规范 d
        self::assertSame('regulation_d', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getRegulationCategory());

        // 规范 e
        self::assertSame('regulation_e', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getRegulationCategory());

        // 技术原因
        self::assertSame('technical_reason', InvalidTimeReason::WINDOW_FOCUS_LOST->getRegulationCategory());
        self::assertSame('technical_reason', InvalidTimeReason::SYSTEM_ERROR->getRegulationCategory());
    }

    /**
     * 测试获取严重程度
     */
    public function testGetSeverity(): void
    {
        // critical 级别
        self::assertSame('critical', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getSeverity());
        self::assertSame('critical', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getSeverity());

        // high 级别
        self::assertSame('high', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getSeverity());
        self::assertSame('high', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->getSeverity());
        self::assertSame('high', InvalidTimeReason::SUSPICIOUS_BEHAVIOR->getSeverity());

        // medium 级别
        self::assertSame('medium', InvalidTimeReason::INTERACTION_TIMEOUT->getSeverity());
        self::assertSame('medium', InvalidTimeReason::IDLE_TIMEOUT->getSeverity());
        self::assertSame('medium', InvalidTimeReason::WINDOW_FOCUS_LOST->getSeverity());

        // low 级别
        self::assertSame('low', InvalidTimeReason::BROWSING_WEB_INFO->getSeverity());
        self::assertSame('low', InvalidTimeReason::ONLINE_TESTING->getSeverity());
    }

    /**
     * 测试是否影响整个课程认定
     */
    public function testAffectsWholeCourse(): void
    {
        // 影响整个课程
        self::assertTrue(InvalidTimeReason::INCOMPLETE_COURSE_TEST->affectsWholeCourse());
        self::assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->affectsWholeCourse());

        // 不影响整个课程
        self::assertFalse(InvalidTimeReason::BROWSING_WEB_INFO->affectsWholeCourse());
        self::assertFalse(InvalidTimeReason::IDLE_TIMEOUT->affectsWholeCourse());
        self::assertFalse(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->affectsWholeCourse());
        self::assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->affectsWholeCourse());
    }

    /**
     * 测试是否需要学员提示
     */
    public function testRequiresStudentNotification(): void
    {
        // 需要提示
        self::assertTrue(InvalidTimeReason::BROWSING_WEB_INFO->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::ONLINE_TESTING->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::INTERACTION_TIMEOUT->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::IDLE_TIMEOUT->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->requiresStudentNotification());
        self::assertTrue(InvalidTimeReason::INCOMPLETE_COURSE_TEST->requiresStudentNotification());

        // 不需要提示
        self::assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->requiresStudentNotification());
        self::assertFalse(InvalidTimeReason::PAGE_HIDDEN->requiresStudentNotification());
        self::assertFalse(InvalidTimeReason::SYSTEM_ERROR->requiresStudentNotification());
    }

    /**
     * 测试获取提示消息
     */
    public function testGetNotificationMessage(): void
    {
        self::assertSame('当前正在浏览网页信息，此时长不计入有效学习时长', InvalidTimeReason::BROWSING_WEB_INFO->getNotificationMessage());
        self::assertSame('当前正在进行在线测试，此时长不计入有效学习时长', InvalidTimeReason::ONLINE_TESTING->getNotificationMessage());
        self::assertSame('身份验证失败，请重新验证。验证失败期间的时长不计入有效学习时长', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getNotificationMessage());
        self::assertSame('检测到您长时间未与平台交互，此期间时长不计入有效学习时长', InvalidTimeReason::INTERACTION_TIMEOUT->getNotificationMessage());
        self::assertSame('检测到您长时间无操作，请点击继续学习', InvalidTimeReason::IDLE_TIMEOUT->getNotificationMessage());
        self::assertSame('您今日的有效学习时长已达到上限，超出部分不计入认定', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getNotificationMessage());
        self::assertSame('请完成课程在线测试，否则整个课程学时不予认定', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getNotificationMessage());
        self::assertSame('此时长不计入有效学习时长', InvalidTimeReason::WINDOW_FOCUS_LOST->getNotificationMessage());
    }

    /**
     * 测试获取规范要求的原因
     */
    public function testGetRegulationReasons(): void
    {
        $reasons = InvalidTimeReason::getRegulationReasons();

        self::assertCount(8, $reasons);
        self::assertContains(InvalidTimeReason::BROWSING_WEB_INFO, $reasons);
        self::assertContains(InvalidTimeReason::ONLINE_TESTING, $reasons);
        self::assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $reasons);
        self::assertContains(InvalidTimeReason::INTERACTION_TIMEOUT, $reasons);
        self::assertContains(InvalidTimeReason::IDLE_TIMEOUT, $reasons);
        self::assertContains(InvalidTimeReason::NO_ACTIVITY_DETECTED, $reasons);
        self::assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $reasons);
        self::assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $reasons);

        // 不应包含技术原因
        self::assertNotContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $reasons);
        self::assertNotContains(InvalidTimeReason::SYSTEM_ERROR, $reasons);
    }

    /**
     * 测试获取技术原因
     */
    public function testGetTechnicalReasons(): void
    {
        $reasons = InvalidTimeReason::getTechnicalReasons();

        self::assertCount(7, $reasons);
        self::assertContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $reasons);
        self::assertContains(InvalidTimeReason::PAGE_HIDDEN, $reasons);
        self::assertContains(InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, $reasons);
        self::assertContains(InvalidTimeReason::NETWORK_DISCONNECTED, $reasons);
        self::assertContains(InvalidTimeReason::SYSTEM_ERROR, $reasons);
        self::assertContains(InvalidTimeReason::SUSPICIOUS_BEHAVIOR, $reasons);
        self::assertContains(InvalidTimeReason::MANUAL_EXCLUSION, $reasons);

        // 不应包含规范原因
        self::assertNotContains(InvalidTimeReason::BROWSING_WEB_INFO, $reasons);
        self::assertNotContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $reasons);
    }

    /**
     * 测试按严重程度分组
     */
    public function testGetBySeverity(): void
    {
        // critical 级别
        $critical = InvalidTimeReason::getBySeverity('critical');
        self::assertCount(2, $critical);
        self::assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $critical);
        self::assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $critical);

        // high 级别
        $high = InvalidTimeReason::getBySeverity('high');
        self::assertCount(3, $high);
        self::assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $high);
        self::assertContains(InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, $high);
        self::assertContains(InvalidTimeReason::SUSPICIOUS_BEHAVIOR, $high);

        // medium 级别
        $medium = InvalidTimeReason::getBySeverity('medium');
        self::assertGreaterThanOrEqual(3, count($medium));
        self::assertContains(InvalidTimeReason::INTERACTION_TIMEOUT, $medium);
        self::assertContains(InvalidTimeReason::IDLE_TIMEOUT, $medium);
        self::assertContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $medium);
    }

    /**
     * 测试枚举 cases
     */
    public function testCases(): void
    {
        $cases = InvalidTimeReason::cases();

        self::assertCount(15, $cases);
        foreach ($cases as $case) {
            self::assertNotEmpty($case->getLabel());
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function testFrom(): void
    {
        self::assertSame(InvalidTimeReason::BROWSING_WEB_INFO, InvalidTimeReason::from('browsing_web_info'));
        self::assertSame(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, InvalidTimeReason::from('identity_verification_failed'));
        self::assertSame(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, InvalidTimeReason::from('daily_limit_exceeded'));
    }

    /**
     * 测试 tryFrom
     */
    public function testTryFrom(): void
    {
        self::assertSame(InvalidTimeReason::IDLE_TIMEOUT, InvalidTimeReason::tryFrom('idle_timeout'));
        self::assertSame(InvalidTimeReason::SYSTEM_ERROR, InvalidTimeReason::tryFrom('system_error'));
        self::assertNull(InvalidTimeReason::tryFrom('invalid_reason'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (InvalidTimeReason $case) => $case->value, InvalidTimeReason::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'Enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (InvalidTimeReason $case) => $case->getLabel(), InvalidTimeReason::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'Enum labels must be unique');
    }
}
