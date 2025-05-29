<?php

namespace Tourze\TrainRecordBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\InvalidTimeReason;
use Tourze\TrainRecordBundle\Enum\StudyTimeStatus;

/**
 * 有效学时枚举类单元测试
 */
class EffectiveStudyTimeEnumTest extends TestCase
{
    public function test_invalid_time_reason_enum(): void
    {
        // 测试基本值
        $this->assertEquals('browsing_web_info', InvalidTimeReason::BROWSING_WEB_INFO->value);
        $this->assertEquals('online_testing', InvalidTimeReason::ONLINE_TESTING->value);
        $this->assertEquals('identity_verification_failed', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->value);
        $this->assertEquals('interaction_timeout', InvalidTimeReason::INTERACTION_TIMEOUT->value);
        $this->assertEquals('daily_limit_exceeded', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->value);
        $this->assertEquals('incomplete_course_test', InvalidTimeReason::INCOMPLETE_COURSE_TEST->value);
        
        // 测试标签
        $this->assertEquals('浏览网页信息', InvalidTimeReason::BROWSING_WEB_INFO->getLabel());
        $this->assertEquals('在线测试时长', InvalidTimeReason::ONLINE_TESTING->getLabel());
        $this->assertEquals('身份验证失败', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getLabel());
        $this->assertEquals('交互超时', InvalidTimeReason::INTERACTION_TIMEOUT->getLabel());
        $this->assertEquals('日累计时长超限', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getLabel());
        $this->assertEquals('未完成课程测试', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getLabel());
        
        // 测试描述
        $this->assertStringContainsString('浏览网页信息', InvalidTimeReason::BROWSING_WEB_INFO->getDescription());
        $this->assertStringContainsString('在线测试', InvalidTimeReason::ONLINE_TESTING->getDescription());
        $this->assertStringContainsString('身份验证失败', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getDescription());
    }

    public function test_invalid_time_reason_regulation_category(): void
    {
        // 测试规范分类
        $this->assertEquals('regulation_a', InvalidTimeReason::BROWSING_WEB_INFO->getRegulationCategory());
        $this->assertEquals('regulation_a', InvalidTimeReason::ONLINE_TESTING->getRegulationCategory());
        $this->assertEquals('regulation_b', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getRegulationCategory());
        $this->assertEquals('regulation_c', InvalidTimeReason::INTERACTION_TIMEOUT->getRegulationCategory());
        $this->assertEquals('regulation_d', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getRegulationCategory());
        $this->assertEquals('regulation_e', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getRegulationCategory());
        
        // 技术原因
        $this->assertEquals('technical_reason', InvalidTimeReason::WINDOW_FOCUS_LOST->getRegulationCategory());
        $this->assertEquals('technical_reason', InvalidTimeReason::PAGE_HIDDEN->getRegulationCategory());
        $this->assertEquals('technical_reason', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->getRegulationCategory());
    }

    public function test_invalid_time_reason_severity(): void
    {
        // 测试严重程度
        $this->assertEquals('critical', InvalidTimeReason::INCOMPLETE_COURSE_TEST->getSeverity());
        $this->assertEquals('critical', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getSeverity());
        $this->assertEquals('high', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getSeverity());
        $this->assertEquals('high', InvalidTimeReason::MULTIPLE_DEVICE_LOGIN->getSeverity());
        $this->assertEquals('medium', InvalidTimeReason::INTERACTION_TIMEOUT->getSeverity());
        $this->assertEquals('medium', InvalidTimeReason::WINDOW_FOCUS_LOST->getSeverity());
    }

    public function test_invalid_time_reason_affects_whole_course(): void
    {
        // 测试是否影响整个课程认定
        $this->assertTrue(InvalidTimeReason::INCOMPLETE_COURSE_TEST->affectsWholeCourse());
        $this->assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::INTERACTION_TIMEOUT->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->affectsWholeCourse());
        $this->assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->affectsWholeCourse());
    }

    public function test_invalid_time_reason_notification(): void
    {
        // 测试是否需要学员通知
        $this->assertTrue(InvalidTimeReason::BROWSING_WEB_INFO->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->requiresStudentNotification());
        $this->assertTrue(InvalidTimeReason::DAILY_LIMIT_EXCEEDED->requiresStudentNotification());
        $this->assertFalse(InvalidTimeReason::WINDOW_FOCUS_LOST->requiresStudentNotification());
        $this->assertFalse(InvalidTimeReason::SYSTEM_ERROR->requiresStudentNotification());
        
        // 测试通知消息
        $this->assertStringContainsString('浏览网页信息', InvalidTimeReason::BROWSING_WEB_INFO->getNotificationMessage());
        $this->assertStringContainsString('身份验证失败', InvalidTimeReason::IDENTITY_VERIFICATION_FAILED->getNotificationMessage());
        $this->assertStringContainsString('上限', InvalidTimeReason::DAILY_LIMIT_EXCEEDED->getNotificationMessage());
    }

    public function test_invalid_time_reason_static_methods(): void
    {
        // 测试获取规范要求的原因
        $regulationReasons = InvalidTimeReason::getRegulationReasons();
        $this->assertContains(InvalidTimeReason::BROWSING_WEB_INFO, $regulationReasons);
        $this->assertContains(InvalidTimeReason::ONLINE_TESTING, $regulationReasons);
        $this->assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $regulationReasons);
        $this->assertContains(InvalidTimeReason::INTERACTION_TIMEOUT, $regulationReasons);
        $this->assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $regulationReasons);
        $this->assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $regulationReasons);
        
        // 测试获取技术原因
        $technicalReasons = InvalidTimeReason::getTechnicalReasons();
        $this->assertContains(InvalidTimeReason::WINDOW_FOCUS_LOST, $technicalReasons);
        $this->assertContains(InvalidTimeReason::PAGE_HIDDEN, $technicalReasons);
        $this->assertContains(InvalidTimeReason::MULTIPLE_DEVICE_LOGIN, $technicalReasons);
        $this->assertContains(InvalidTimeReason::SYSTEM_ERROR, $technicalReasons);
        
        // 测试按严重程度分组
        $criticalReasons = InvalidTimeReason::getBySeverity('critical');
        $this->assertContains(InvalidTimeReason::INCOMPLETE_COURSE_TEST, $criticalReasons);
        $this->assertContains(InvalidTimeReason::IDENTITY_VERIFICATION_FAILED, $criticalReasons);
        
        $highReasons = InvalidTimeReason::getBySeverity('high');
        $this->assertContains(InvalidTimeReason::DAILY_LIMIT_EXCEEDED, $highReasons);
    }

    public function test_study_time_status_enum(): void
    {
        // 测试基本值
        $this->assertEquals('valid', StudyTimeStatus::VALID->value);
        $this->assertEquals('invalid', StudyTimeStatus::INVALID->value);
        $this->assertEquals('pending', StudyTimeStatus::PENDING->value);
        $this->assertEquals('partial', StudyTimeStatus::PARTIAL->value);
        $this->assertEquals('reviewing', StudyTimeStatus::REVIEWING->value);
        $this->assertEquals('approved', StudyTimeStatus::APPROVED->value);
        $this->assertEquals('rejected', StudyTimeStatus::REJECTED->value);
        
        // 测试标签
        $this->assertEquals('有效学时', StudyTimeStatus::VALID->getLabel());
        $this->assertEquals('无效学时', StudyTimeStatus::INVALID->getLabel());
        $this->assertEquals('待确认学时', StudyTimeStatus::PENDING->getLabel());
        $this->assertEquals('部分有效学时', StudyTimeStatus::PARTIAL->getLabel());
        $this->assertEquals('审核中', StudyTimeStatus::REVIEWING->getLabel());
        $this->assertEquals('已认定', StudyTimeStatus::APPROVED->getLabel());
        $this->assertEquals('已拒绝', StudyTimeStatus::REJECTED->getLabel());
        
        // 测试描述
        $this->assertStringContainsString('有效学习时长', StudyTimeStatus::VALID->getDescription());
        $this->assertStringContainsString('无效学习时长', StudyTimeStatus::INVALID->getDescription());
        $this->assertStringContainsString('等待', StudyTimeStatus::PENDING->getDescription());
    }

    public function test_study_time_status_colors_and_icons(): void
    {
        // 测试颜色
        $this->assertEquals('green', StudyTimeStatus::VALID->getColor());
        $this->assertEquals('green', StudyTimeStatus::APPROVED->getColor());
        $this->assertEquals('red', StudyTimeStatus::INVALID->getColor());
        $this->assertEquals('red', StudyTimeStatus::REJECTED->getColor());
        $this->assertEquals('orange', StudyTimeStatus::PENDING->getColor());
        $this->assertEquals('orange', StudyTimeStatus::REVIEWING->getColor());
        $this->assertEquals('yellow', StudyTimeStatus::PARTIAL->getColor());
        
        // 测试图标
        $this->assertEquals('check-circle', StudyTimeStatus::VALID->getIcon());
        $this->assertEquals('check-circle', StudyTimeStatus::APPROVED->getIcon());
        $this->assertEquals('x-circle', StudyTimeStatus::INVALID->getIcon());
        $this->assertEquals('x-circle', StudyTimeStatus::REJECTED->getIcon());
        $this->assertEquals('clock', StudyTimeStatus::PENDING->getIcon());
        $this->assertEquals('search', StudyTimeStatus::REVIEWING->getIcon());
        $this->assertEquals('pie-chart', StudyTimeStatus::PARTIAL->getIcon());
    }

    public function test_study_time_status_properties(): void
    {
        // 测试是否为最终状态
        $this->assertTrue(StudyTimeStatus::APPROVED->isFinal());
        $this->assertTrue(StudyTimeStatus::REJECTED->isFinal());
        $this->assertTrue(StudyTimeStatus::EXPIRED->isFinal());
        $this->assertFalse(StudyTimeStatus::VALID->isFinal());
        $this->assertFalse(StudyTimeStatus::PENDING->isFinal());
        
        // 测试是否可计入有效学时
        $this->assertTrue(StudyTimeStatus::VALID->isCountable());
        $this->assertTrue(StudyTimeStatus::PARTIAL->isCountable());
        $this->assertTrue(StudyTimeStatus::APPROVED->isCountable());
        $this->assertFalse(StudyTimeStatus::INVALID->isCountable());
        $this->assertFalse(StudyTimeStatus::REJECTED->isCountable());
        
        // 测试是否需要人工审核
        $this->assertTrue(StudyTimeStatus::PENDING->needsReview());
        $this->assertTrue(StudyTimeStatus::REVIEWING->needsReview());
        $this->assertFalse(StudyTimeStatus::VALID->needsReview());
        $this->assertFalse(StudyTimeStatus::APPROVED->needsReview());
        
        // 测试是否可修改
        $this->assertTrue(StudyTimeStatus::VALID->isModifiable());
        $this->assertTrue(StudyTimeStatus::PENDING->isModifiable());
        $this->assertFalse(StudyTimeStatus::APPROVED->isModifiable());
        $this->assertFalse(StudyTimeStatus::REJECTED->isModifiable());
        
        // 测试是否需要提醒学员
        $this->assertTrue(StudyTimeStatus::INVALID->requiresNotification());
        $this->assertTrue(StudyTimeStatus::REJECTED->requiresNotification());
        $this->assertFalse(StudyTimeStatus::VALID->requiresNotification());
        $this->assertFalse(StudyTimeStatus::APPROVED->requiresNotification());
    }

    public function test_study_time_status_transitions(): void
    {
        // 测试状态转换
        $pendingNextStatuses = StudyTimeStatus::PENDING->getNextPossibleStatuses();
        $this->assertContains(StudyTimeStatus::VALID, $pendingNextStatuses);
        $this->assertContains(StudyTimeStatus::INVALID, $pendingNextStatuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $pendingNextStatuses);
        $this->assertContains(StudyTimeStatus::REVIEWING, $pendingNextStatuses);
        
        $reviewingNextStatuses = StudyTimeStatus::REVIEWING->getNextPossibleStatuses();
        $this->assertContains(StudyTimeStatus::APPROVED, $reviewingNextStatuses);
        $this->assertContains(StudyTimeStatus::REJECTED, $reviewingNextStatuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $reviewingNextStatuses);
        
        // 测试状态转换验证
        $this->assertTrue(StudyTimeStatus::PENDING->canTransitionTo(StudyTimeStatus::VALID));
        $this->assertTrue(StudyTimeStatus::REVIEWING->canTransitionTo(StudyTimeStatus::APPROVED));
        $this->assertFalse(StudyTimeStatus::APPROVED->canTransitionTo(StudyTimeStatus::PENDING));
        $this->assertFalse(StudyTimeStatus::REJECTED->canTransitionTo(StudyTimeStatus::VALID));
    }

    public function test_study_time_status_static_methods(): void
    {
        // 测试获取所有状态
        $allStatuses = StudyTimeStatus::getAllStatuses();
        $this->assertGreaterThan(5, count($allStatuses));
        $this->assertContains(StudyTimeStatus::VALID, $allStatuses);
        $this->assertContains(StudyTimeStatus::APPROVED, $allStatuses);
        
        // 测试获取活跃状态
        $activeStatuses = StudyTimeStatus::getActiveStatuses();
        $this->assertContains(StudyTimeStatus::VALID, $activeStatuses);
        $this->assertContains(StudyTimeStatus::PENDING, $activeStatuses);
        $this->assertNotContains(StudyTimeStatus::APPROVED, $activeStatuses);
        $this->assertNotContains(StudyTimeStatus::REJECTED, $activeStatuses);
        
        // 测试获取最终状态
        $finalStatuses = StudyTimeStatus::getFinalStatuses();
        $this->assertContains(StudyTimeStatus::APPROVED, $finalStatuses);
        $this->assertContains(StudyTimeStatus::REJECTED, $finalStatuses);
        $this->assertContains(StudyTimeStatus::EXPIRED, $finalStatuses);
        $this->assertNotContains(StudyTimeStatus::VALID, $finalStatuses);
        
        // 测试获取可计入学时的状态
        $countableStatuses = StudyTimeStatus::getCountableStatuses();
        $this->assertContains(StudyTimeStatus::VALID, $countableStatuses);
        $this->assertContains(StudyTimeStatus::PARTIAL, $countableStatuses);
        $this->assertContains(StudyTimeStatus::APPROVED, $countableStatuses);
        $this->assertNotContains(StudyTimeStatus::INVALID, $countableStatuses);
        
        // 测试获取需要审核的状态
        $reviewStatuses = StudyTimeStatus::getReviewStatuses();
        $this->assertContains(StudyTimeStatus::PENDING, $reviewStatuses);
        $this->assertContains(StudyTimeStatus::REVIEWING, $reviewStatuses);
        $this->assertNotContains(StudyTimeStatus::VALID, $reviewStatuses);
    }

    public function test_study_time_status_from_string(): void
    {
        // 测试从字符串创建状态
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::fromString('valid'));
        $this->assertEquals(StudyTimeStatus::VALID, StudyTimeStatus::fromString('有效'));
        $this->assertEquals(StudyTimeStatus::INVALID, StudyTimeStatus::fromString('invalid'));
        $this->assertEquals(StudyTimeStatus::INVALID, StudyTimeStatus::fromString('无效'));
        $this->assertEquals(StudyTimeStatus::PENDING, StudyTimeStatus::fromString('pending'));
        $this->assertEquals(StudyTimeStatus::PENDING, StudyTimeStatus::fromString('待确认'));
        
        // 测试无效字符串
        $this->assertNull(StudyTimeStatus::fromString('unknown'));
        $this->assertNull(StudyTimeStatus::fromString(''));
    }
} 