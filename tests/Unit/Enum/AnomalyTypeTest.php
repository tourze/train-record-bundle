<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * AnomalyType 枚举测试
 */
class AnomalyTypeTest extends TestCase
{
    /**
     * 测试枚举基本值
     */
    public function test_enum_values(): void
    {
        $this->assertEquals('multiple_device', AnomalyType::MULTIPLE_DEVICE->value);
        $this->assertEquals('rapid_progress', AnomalyType::RAPID_PROGRESS->value);
        $this->assertEquals('window_switch', AnomalyType::WINDOW_SWITCH->value);
        $this->assertEquals('idle_timeout', AnomalyType::IDLE_TIMEOUT->value);
        $this->assertEquals('face_detect_fail', AnomalyType::FACE_DETECT_FAIL->value);
        $this->assertEquals('network_anomaly', AnomalyType::NETWORK_ANOMALY->value);
        $this->assertEquals('suspicious_behavior', AnomalyType::SUSPICIOUS_BEHAVIOR->value);
        $this->assertEquals('device_change', AnomalyType::DEVICE_CHANGE->value);
        $this->assertEquals('ip_change', AnomalyType::IP_CHANGE->value);
        $this->assertEquals('time_anomaly', AnomalyType::TIME_ANOMALY->value);
        $this->assertEquals('progress_rollback', AnomalyType::PROGRESS_ROLLBACK->value);
        $this->assertEquals('concurrent_session', AnomalyType::CONCURRENT_SESSION->value);
        $this->assertEquals('invalid_operation', AnomalyType::INVALID_OPERATION->value);
        $this->assertEquals('security_violation', AnomalyType::SECURITY_VIOLATION->value);
        $this->assertEquals('data_inconsistency', AnomalyType::DATA_INCONSISTENCY->value);
    }

    /**
     * 测试获取标签
     */
    public function test_get_label(): void
    {
        $this->assertEquals('多设备登录', AnomalyType::MULTIPLE_DEVICE->getLabel());
        $this->assertEquals('快速进度异常', AnomalyType::RAPID_PROGRESS->getLabel());
        $this->assertEquals('窗口切换异常', AnomalyType::WINDOW_SWITCH->getLabel());
        $this->assertEquals('空闲超时', AnomalyType::IDLE_TIMEOUT->getLabel());
        $this->assertEquals('人脸检测失败', AnomalyType::FACE_DETECT_FAIL->getLabel());
        $this->assertEquals('网络异常', AnomalyType::NETWORK_ANOMALY->getLabel());
        $this->assertEquals('可疑行为', AnomalyType::SUSPICIOUS_BEHAVIOR->getLabel());
        $this->assertEquals('设备切换', AnomalyType::DEVICE_CHANGE->getLabel());
        $this->assertEquals('IP地址变更', AnomalyType::IP_CHANGE->getLabel());
        $this->assertEquals('时间异常', AnomalyType::TIME_ANOMALY->getLabel());
        $this->assertEquals('进度回退', AnomalyType::PROGRESS_ROLLBACK->getLabel());
        $this->assertEquals('并发会话', AnomalyType::CONCURRENT_SESSION->getLabel());
        $this->assertEquals('无效操作', AnomalyType::INVALID_OPERATION->getLabel());
        $this->assertEquals('安全违规', AnomalyType::SECURITY_VIOLATION->getLabel());
        $this->assertEquals('数据不一致', AnomalyType::DATA_INCONSISTENCY->getLabel());
    }

    /**
     * 测试获取描述
     */
    public function test_get_description(): void
    {
        $this->assertEquals('检测到用户在多个设备上同时学习', AnomalyType::MULTIPLE_DEVICE->getDescription());
        $this->assertEquals('学习进度推进过快，可能存在作弊行为', AnomalyType::RAPID_PROGRESS->getDescription());
        $this->assertEquals('频繁切换窗口或失去焦点', AnomalyType::WINDOW_SWITCH->getDescription());
        $this->assertEquals('长时间无操作，可能已离开学习', AnomalyType::IDLE_TIMEOUT->getDescription());
        $this->assertEquals('人脸识别验证失败', AnomalyType::FACE_DETECT_FAIL->getDescription());
        $this->assertEquals('网络连接异常或中断', AnomalyType::NETWORK_ANOMALY->getDescription());
        $this->assertEquals('检测到可疑的学习行为模式', AnomalyType::SUSPICIOUS_BEHAVIOR->getDescription());
        $this->assertEquals('学习过程中更换了设备', AnomalyType::DEVICE_CHANGE->getDescription());
        $this->assertEquals('学习过程中IP地址发生变更', AnomalyType::IP_CHANGE->getDescription());
        $this->assertEquals('学习时间记录异常', AnomalyType::TIME_ANOMALY->getDescription());
        $this->assertEquals('学习进度出现回退', AnomalyType::PROGRESS_ROLLBACK->getDescription());
        $this->assertEquals('检测到并发学习会话', AnomalyType::CONCURRENT_SESSION->getDescription());
        $this->assertEquals('执行了无效的学习操作', AnomalyType::INVALID_OPERATION->getDescription());
        $this->assertEquals('违反了安全策略', AnomalyType::SECURITY_VIOLATION->getDescription());
        $this->assertEquals('学习数据存在不一致', AnomalyType::DATA_INCONSISTENCY->getDescription());
    }

    /**
     * 测试获取分类
     */
    public function test_get_category(): void
    {
        // 设备相关
        $this->assertEquals('device', AnomalyType::MULTIPLE_DEVICE->getCategory());
        $this->assertEquals('device', AnomalyType::DEVICE_CHANGE->getCategory());
        $this->assertEquals('device', AnomalyType::CONCURRENT_SESSION->getCategory());
        
        // 进度相关
        $this->assertEquals('progress', AnomalyType::RAPID_PROGRESS->getCategory());
        $this->assertEquals('progress', AnomalyType::PROGRESS_ROLLBACK->getCategory());
        $this->assertEquals('progress', AnomalyType::TIME_ANOMALY->getCategory());
        
        // 行为相关
        $this->assertEquals('behavior', AnomalyType::WINDOW_SWITCH->getCategory());
        $this->assertEquals('behavior', AnomalyType::IDLE_TIMEOUT->getCategory());
        $this->assertEquals('behavior', AnomalyType::SUSPICIOUS_BEHAVIOR->getCategory());
        
        // 安全相关
        $this->assertEquals('security', AnomalyType::FACE_DETECT_FAIL->getCategory());
        $this->assertEquals('security', AnomalyType::SECURITY_VIOLATION->getCategory());
        
        // 网络相关
        $this->assertEquals('network', AnomalyType::NETWORK_ANOMALY->getCategory());
        $this->assertEquals('network', AnomalyType::IP_CHANGE->getCategory());
        
        // 系统相关
        $this->assertEquals('system', AnomalyType::INVALID_OPERATION->getCategory());
        $this->assertEquals('system', AnomalyType::DATA_INCONSISTENCY->getCategory());
    }

    /**
     * 测试获取默认严重程度
     */
    public function test_get_default_severity(): void
    {
        // CRITICAL 级别
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalyType::SECURITY_VIOLATION->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::CRITICAL, AnomalyType::MULTIPLE_DEVICE->getDefaultSeverity());
        
        // HIGH 级别
        $this->assertEquals(AnomalySeverity::HIGH, AnomalyType::FACE_DETECT_FAIL->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::HIGH, AnomalyType::RAPID_PROGRESS->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::HIGH, AnomalyType::CONCURRENT_SESSION->getDefaultSeverity());
        
        // MEDIUM 级别
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalyType::WINDOW_SWITCH->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalyType::DEVICE_CHANGE->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::MEDIUM, AnomalyType::SUSPICIOUS_BEHAVIOR->getDefaultSeverity());
        
        // LOW 级别
        $this->assertEquals(AnomalySeverity::LOW, AnomalyType::IDLE_TIMEOUT->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::LOW, AnomalyType::NETWORK_ANOMALY->getDefaultSeverity());
        $this->assertEquals(AnomalySeverity::LOW, AnomalyType::IP_CHANGE->getDefaultSeverity());
    }

    /**
     * 测试是否需要立即处理
     */
    public function test_requires_immediate_action(): void
    {
        // 需要立即处理
        $this->assertTrue(AnomalyType::SECURITY_VIOLATION->requiresImmediateAction());
        $this->assertTrue(AnomalyType::MULTIPLE_DEVICE->requiresImmediateAction());
        $this->assertTrue(AnomalyType::FACE_DETECT_FAIL->requiresImmediateAction());
        $this->assertTrue(AnomalyType::CONCURRENT_SESSION->requiresImmediateAction());
        
        // 不需要立即处理
        $this->assertFalse(AnomalyType::WINDOW_SWITCH->requiresImmediateAction());
        $this->assertFalse(AnomalyType::IDLE_TIMEOUT->requiresImmediateAction());
        $this->assertFalse(AnomalyType::NETWORK_ANOMALY->requiresImmediateAction());
        $this->assertFalse(AnomalyType::IP_CHANGE->requiresImmediateAction());
    }

    /**
     * 测试获取所有异常类型
     */
    public function test_get_all_types(): void
    {
        $types = AnomalyType::getAllTypes();
        
        $this->assertCount(15, $types);
        $this->assertContains(AnomalyType::MULTIPLE_DEVICE, $types);
        $this->assertContains(AnomalyType::RAPID_PROGRESS, $types);
        $this->assertContains(AnomalyType::WINDOW_SWITCH, $types);
        $this->assertContains(AnomalyType::IDLE_TIMEOUT, $types);
        $this->assertContains(AnomalyType::FACE_DETECT_FAIL, $types);
        $this->assertContains(AnomalyType::NETWORK_ANOMALY, $types);
        $this->assertContains(AnomalyType::SUSPICIOUS_BEHAVIOR, $types);
        $this->assertContains(AnomalyType::DEVICE_CHANGE, $types);
        $this->assertContains(AnomalyType::IP_CHANGE, $types);
        $this->assertContains(AnomalyType::TIME_ANOMALY, $types);
        $this->assertContains(AnomalyType::PROGRESS_ROLLBACK, $types);
        $this->assertContains(AnomalyType::CONCURRENT_SESSION, $types);
        $this->assertContains(AnomalyType::INVALID_OPERATION, $types);
        $this->assertContains(AnomalyType::SECURITY_VIOLATION, $types);
        $this->assertContains(AnomalyType::DATA_INCONSISTENCY, $types);
    }

    /**
     * 测试按分类获取异常类型
     */
    public function test_get_by_category(): void
    {
        // 设备类
        $deviceTypes = AnomalyType::getByCategory('device');
        $this->assertCount(3, $deviceTypes);
        $this->assertContains(AnomalyType::MULTIPLE_DEVICE, $deviceTypes);
        $this->assertContains(AnomalyType::DEVICE_CHANGE, $deviceTypes);
        $this->assertContains(AnomalyType::CONCURRENT_SESSION, $deviceTypes);
        
        // 进度类
        $progressTypes = AnomalyType::getByCategory('progress');
        $this->assertCount(3, $progressTypes);
        $this->assertContains(AnomalyType::RAPID_PROGRESS, $progressTypes);
        $this->assertContains(AnomalyType::PROGRESS_ROLLBACK, $progressTypes);
        $this->assertContains(AnomalyType::TIME_ANOMALY, $progressTypes);
        
        // 行为类
        $behaviorTypes = AnomalyType::getByCategory('behavior');
        $this->assertCount(3, $behaviorTypes);
        $this->assertContains(AnomalyType::WINDOW_SWITCH, $behaviorTypes);
        $this->assertContains(AnomalyType::IDLE_TIMEOUT, $behaviorTypes);
        $this->assertContains(AnomalyType::SUSPICIOUS_BEHAVIOR, $behaviorTypes);
        
        // 安全类
        $securityTypes = AnomalyType::getByCategory('security');
        $this->assertCount(2, $securityTypes);
        $this->assertContains(AnomalyType::FACE_DETECT_FAIL, $securityTypes);
        $this->assertContains(AnomalyType::SECURITY_VIOLATION, $securityTypes);
        
        // 网络类
        $networkTypes = AnomalyType::getByCategory('network');
        $this->assertCount(2, $networkTypes);
        $this->assertContains(AnomalyType::NETWORK_ANOMALY, $networkTypes);
        $this->assertContains(AnomalyType::IP_CHANGE, $networkTypes);
        
        // 系统类
        $systemTypes = AnomalyType::getByCategory('system');
        $this->assertCount(2, $systemTypes);
        $this->assertContains(AnomalyType::INVALID_OPERATION, $systemTypes);
        $this->assertContains(AnomalyType::DATA_INCONSISTENCY, $systemTypes);
        
        // 不存在的分类
        $unknownTypes = AnomalyType::getByCategory('unknown');
        $this->assertCount(0, $unknownTypes);
    }

    /**
     * 测试枚举 cases
     */
    public function test_cases(): void
    {
        $cases = AnomalyType::cases();
        
        $this->assertCount(15, $cases);
        foreach ($cases as $case) {
            $this->assertInstanceOf(AnomalyType::class, $case);
        }
    }

    /**
     * 测试从值创建枚举
     */
    public function test_from(): void
    {
        $this->assertEquals(AnomalyType::MULTIPLE_DEVICE, AnomalyType::from('multiple_device'));
        $this->assertEquals(AnomalyType::RAPID_PROGRESS, AnomalyType::from('rapid_progress'));
        $this->assertEquals(AnomalyType::SECURITY_VIOLATION, AnomalyType::from('security_violation'));
    }

    /**
     * 测试 tryFrom
     */
    public function test_try_from(): void
    {
        $this->assertEquals(AnomalyType::MULTIPLE_DEVICE, AnomalyType::tryFrom('multiple_device'));
        $this->assertEquals(AnomalyType::FACE_DETECT_FAIL, AnomalyType::tryFrom('face_detect_fail'));
        $this->assertNull(AnomalyType::tryFrom('invalid_type'));
    }
}