<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;

/**
 * InvalidTimeReason 枚举测试
 */
class InvalidTimeReasonTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        // 规范要求的原因
        $this->assertEquals('browsing_web_info', InvalidTimeReason::BROWSING_WEB_INFO->value);
        $this->assertEquals('online_testing', InvalidTimeReason::ONLINE_TESTING->value);
        $this->assertEquals('identity_verification_failed', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->value);
        $this->assertEquals('interaction_timeout', InvalidTimeReason::INTERACTION_TIMEOUT->value);
        $this->assertEquals('idle_timeout', InvalidTimeReason::IDLE_TIMEOUT->value);
        $this->assertEquals('no_activity_detected', InvalidTimeReason::NO_ACTIVITY_DETECTED->value);
        $this->assertEquals('daily_limit_exceeded', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->value);
        $this->assertEquals('incomplete_course_test', InvalidTimeReason::INCOMPLETE_COURSE_TEST->value);
        
        // 技术原因
        $this->assertEquals('window_focus_lost', InvalidTimeReason::WINDOW_FOCUS_LOST->value);
        $this->assertEquals('page_hidden', InvalidTimeReason::PAGE_HIDDEN->value);
        $this->assertEquals('multiple_device_login', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->value);
        $this->assertEquals('network_disconnected', InvalidTimeReason::NETWORK_DISCONNECTED->value);
        $this->assertEquals('system_error', InvalidTimeReason::SYSTEM_ERROR->value);
        $this->assertEquals('suspicious_behavior', InvalidTimeReason::SUSPICIOUS_BEHAVIOR->value);
        $this->assertEquals('manual_exclusion', InvalidTimeReason::MANUAL_EXCLUSION->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('浏览网页信息', InvalidTimeReason::BROWSING_WEB_INFO->getLabel());
        $this->assertEquals('在线测试时长', InvalidTimeReason::ONLINE_TESTING->getLabel());
        $this->assertEquals('身份验证失败', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getLabel());
        $this->assertEquals('交互超时', InvalidTimeReason::INTERACTION_TIMEOUT->getLabel());
        $this->assertEquals('空闲超时', InvalidTimeReason::IDLE_TIMEOUT->getLabel());
        $this->assertEquals('无活动检测', InvalidTimeReason::NO_ACTIVITY_DETECTED->getLabel());
        $this->assertEquals('日累计时长超限', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getLabel());
        $this->assertEquals('未完成课程测试', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getLabel());
        $this->assertEquals('窗口失去焦点', InvalidTimeReason::WINDOW_FOCUS_LOST->getLabel());
        $this->assertEquals('页面隐藏', InvalidTimeReason::PAGE_HIDDEN->getLabel());
        $this->assertEquals('多设备登录', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->getLabel());
        $this->assertEquals('网络断开', InvalidTimeReason::NETWORK_DISCONNECTED->getLabel());
        $this->assertEquals('系统错误', InvalidTimeReason::SYSTEM_ERROR->getLabel());
        $this->assertEquals('可疑行为', InvalidTimeReason::SUSPICIOUS_BEHAVIOR->getLabel());
        $this->assertEquals('手动排除', InvalidTimeReason::MANUAL_EXCLUSION->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('学员登录后浏览网页信息的时长不计入有效学习时长', InvalidTimeReason::BROWSING_WEB_INFO->getDescription());
        $this->assertEquals('在线测试过程中的时长不计入有效学习时长', InvalidTimeReason::ONLINE_TESTING->getDescription());
        $this->assertEquals('身份验证失败提示后的时长不计入有效学习时长', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getDescription());
        $this->assertEquals('与网络平台交互间隔超过预设值的时长不计入', InvalidTimeReason::INTERACTION_TIMEOUT->getDescription());
        $this->assertEquals('日累计有效学习时长超过预设值的超出部分不计入', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getDescription());
        $this->assertEquals('未完成相应课程在线测试时，整个课程学时不予认定', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getDescription());
    }

    /**
     * 测试获取规范依据分类
     */
    public function test_get_regulation_category(): void
    {
        // 规范 a
        $this->assertEquals('regulation_a', InvalidTimeReason::BROWSING_WEB_INFO->getRegulationCategory());
        $this->assertEquals('regulation_a', InvalidTimeReason::ONLINE_TESTING->getRegulationCategory());
        
        // 规范 b
        $this->assertEquals('regulation_b', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getRegulationCategory());
        
        // 规范 c
        $this->assertEquals('regulation_c', InvalidTimeReason::INTERACTION_TIMEOUT->getRegulationCategory());
        $this->assertEquals('regulation_c', InvalidTimeReason::IDLE_TIMEOUT->getRegulationCategory());
        $this->assertEquals('regulation_c', InvalidTimeReason::NO_ACTIVITY_DETECTED->getRegulationCategory());
        
        // 规范 d
        $this->assertEquals('regulation_d', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getRegulationCategory());
        
        // 规范 e
        $this->assertEquals('regulation_e', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getRegulationCategory());
        
        // 技术原因
        $this->assertEquals('technical_reason', InvalidTimeReason::WINDOW_FOCUS_LOST->getRegulationCategory());
        $this->assertEquals('technical_reason', InvalidTimeReason::SYSTEM_ERROR->getRegulationCategory());
    }

    /**
     * 测试获取严重程度
     */
    public function test_get_severity(): void
    {
        // critical 级别
        $this->assertEquals('critical', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getSeverity());
        $this->assertEquals('critical', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getSeverity());
        
        // high 级别
        $this->assertEquals('high', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getSeverity());
        $this->assertEquals('high', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->getSeverity());
        $this->assertEquals('high', InvalidTimeReason::SUSPICIOUS_BEHAVIOR->getSeverity());
        
        // medium 级别
        $this->assertEquals('medium', InvalidTimeReason::INTERACTION_TIMEOUT->getSeverity());
        $this->assertEquals('medium', InvalidTimeReason::IDLE_TIMEOUT->getSeverity());
        $this->assertEquals('medium', InvalidTimeReason::WINDOW_FOCUS_LOST->getSeverity());
        
        // low 级别
        $this->assertEquals('low', InvalidTimeReason::BROWSING_WEB_INFO->getSeverity());
        $this->assertEquals('low', InvalidTimeReason::ONLINE_TESTING->getSeverity());
    }

    /**
     * 测试是否影响整个课程认定
     */
    public function test_affects_whole_course(): void
    {
        // 影响整个课程
        $this->assertTrue(InvalidTimeReason::INCOMPLETE_COURSE_TEST->affectsWholeCourse());
        $this->assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->affectsWholeCourse());
        
        // 不影响整个课程
        $this->assertFalse(InvalidTimeReason::BROWSING_WEB_INFO->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::IDLE_TIMEOUT->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->affectsWholeCourse());
    }

    /**
     * 测试是否需要学员提示
     */
    public function test_requires_student_notification(): void
    {
        // 需要提示
        $this->assertTrue(InvalidTimeReason::BROWSING_WEB_INFO->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::ONLINE_TESTING->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::INTERACTION_TIMEOUT->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::IDLE_TIMEOUT->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::INCOMPLETE_COURSE_TEST->requiresStudentNotification());
        
        // 不需要提示
        $this->assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->requiresStudentNotification());
        $this->assertFalse(InvalidTimeReason::PAGE_HIDDEN->requiresStudentNotification());
        $this->assertFalse(InvalidTimeReason::SYSTEM_ERROR->requiresStudentNotification());
    }

    /**
     * 测试获取提示消息
     */
    public function test_get_notification_message(): void
    {
        $this->assertEquals('当前正在浏览网页信息，此时长不计入有效学习时长', InvalidTimeReason::BROWSING_WEB_INFO->getNotificationMessage());
        $this->assertEquals('当前正在进行在线测试，此时长不计入有效学习时长', InvalidTimeReason::ONLINE_TESTING->getNotificationMessage());
        $this->assertEquals('身份验证失败，请重新验证。验证失败期间的时长不计入有效学习时长', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getNotificationMessage());
        $this->assertEquals('检测到您长时间未与平台交互，此期间时长不计入有效学习时长', InvalidTimeReason::INTERACTION_TIMEOUT->getNotificationMessage());
        $this->assertEquals('检测到您长时间无操作，请点击继续学习', InvalidTimeReason::IDLE_TIMEOUT->getNotificationMessage());
        $this->assertEquals('您今日的有效学习时长已达到上限，超出部分不计入认定', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getNotificationMessage());
        $this->assertEquals('请完成课程在线测试，否则整个课程学时不予认定', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getNotificationMessage());
        $this->assertEquals('此时长不计入有效学习时长', InvalidTimeReason::WINDOW_FOCUS_LOST->getNotificationMessage());
    }

    /**
     * 测试获取规范要求的原因
     */
    public function test_get_regulation_reasons(): void
    {
        $reasons = InvalidTimeReason::getRegulationReasons();
        
        $this->assertCount(8, $reasons);
        $this->assertContains(InvalidTimeReason::BROWSING_WEB_INFO, $reasons);
        $this->assertContains(InvalidTimeReason::ONLINE_TESTING, $reasons);
        $this->assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $reasons);
        $this->assertContains(InvalidTimeReason::INTERACTION_TIMEOUT, $reasons);
        $this->assertContains(InvalidTimeReason::IDLE_TIMEOUT, $reasons);
        $this->assertContains(InvalidTimeReason::NO_ACTIVITY_DETECTED, $reasons);
        $this->assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $reasons);
        $this->assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $reasons);
        
        // 不应包含技术原因
        $this->assertNotContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $reasons);
        $this->assertNotContains(InvalidTimeReason::SYSTEM_ERROR, $reasons);
    }

    /**
     * 测试获取技术原因
     */
    public function test_get_technical_reasons(): void
    {
        $reasons = InvalidTimeReason::getTechnicalReasons();
        
        $this->assertCount(7, $reasons);
        $this->assertContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $reasons);
        $this->assertContains(InvalidTimeReason::PAGE_HIDDEN, $reasons);
        $this->assertContains(InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, $reasons);
        $this->assertContains(InvalidTimeReason::NETWORK_DISCONNECTED, $reasons);
        $this->assertContains(InvalidTimeReason::SYSTEM_ERROR, $reasons);
        $this->assertContains(InvalidTimeReason::SUSPICIOUS_BEHAVIOR, $reasons);
        $this->assertContains(InvalidTimeReason::MANUAL_EXCLUSION, $reasons);
        
        // 不应包含规范原因
        $this->assertNotContains(InvalidTimeReason::BROWSING_WEB_INFO, $reasons);
        $this->assertNotContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $reasons);
    }

    /**
     * 测试按严重程度分组
     */
    public function test_get_by_severity(): void
    {
        // critical 级别
        $critical = InvalidTimeReason::getBySeverity('critical');
        $this->assertCount(2, $critical);
        $this->assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $critical);
        $this->assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $critical);
        
        // high 级别
        $high = InvalidTimeReason::getBySeverity('high');
        $this->assertCount(3, $high);
        $this->assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $high);
        $this->assertContains(InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, $high);
        $this->assertContains(InvalidTimeReason::SUSPICIOUS_BEHAVIOR, $high);
        
        // medium 级别
        $medium = InvalidTimeReason::getBySeverity('medium');
        $this->assertGreaterThanOrEqual(3, count($medium));
        $this->assertContains(InvalidTimeReason::INTERACTION_TIMEOUT, $medium);
        $this->assertContains(InvalidTimeReason::IDLE_TIMEOUT, $medium);
        $this->assertContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $medium);
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = InvalidTimeReason::cases();
        
        $this->assertCount(15, $cases);
        foreach ($cases as $case) {
            $this->assertInstanceOf(InvalidTimeReason::class, $case);
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(InvalidTimeReason::BROWSING_WEB_INFO, InvalidTimeReason::from('browsing_web_info'));
        $this->assertEquals(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, InvalidTimeReason::from('identity_verification_failed'));
        $this->assertEquals(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, InvalidTimeReason::from('daily_limit_exceeded'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(InvalidTimeReason::IDLE_TIMEOUT, InvalidTimeReason::tryFrom('idle_timeout'));
        $this->assertEquals(InvalidTimeReason::SYSTEM_ERROR, InvalidTimeReason::tryFrom('system_error'));
        $this->assertNull(InvalidTimeReason::tryFrom('invalid_reason'));
    }
}