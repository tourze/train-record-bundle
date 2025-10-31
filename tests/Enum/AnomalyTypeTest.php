<?php

namespace Tourze\TrainRecordBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\TrainRecordBundle\Enum\AnomalySeverity;
use Tourze\TrainRecordBundle\Enum\AnomalyType;

/**
 * AnomalyType 枚举测试
 *
 * @internal
 */
#[CoversClass(AnomalyType::class)]
#[RunTestsInSeparateProcesses]
final class AnomalyTypeTest extends AbstractEnumTestCase
{
    public function testEnumCasesExist(): void
    {
        $cases = AnomalyType::cases();

        self::assertCount(17, $cases);
        self::assertContainsOnlyInstancesOf(AnomalyType::class, $cases);
    }

    #[TestWith([AnomalyType::MULTIPLE_DEVICE, 'multiple_device', '多设备登录'])]
    #[TestWith([AnomalyType::RAPID_PROGRESS, 'rapid_progress', '快速进度异常'])]
    #[TestWith([AnomalyType::WINDOW_SWITCH, 'window_switch', '窗口切换异常'])]
    #[TestWith([AnomalyType::IDLE_TIMEOUT, 'idle_timeout', '空闲超时'])]
    #[TestWith([AnomalyType::FACE_DETECT_FAIL, 'face_detect_fail', '人脸检测失败'])]
    #[TestWith([AnomalyType::NETWORK_ANOMALY, 'network_anomaly', '网络异常'])]
    #[TestWith([AnomalyType::SUSPICIOUS_BEHAVIOR, 'suspicious_behavior', '可疑行为'])]
    #[TestWith([AnomalyType::DEVICE_CHANGE, 'device_change', '设备切换'])]
    #[TestWith([AnomalyType::IP_CHANGE, 'ip_change', 'IP地址变更'])]
    #[TestWith([AnomalyType::TIME_ANOMALY, 'time_anomaly', '时间异常'])]
    #[TestWith([AnomalyType::PROGRESS_ROLLBACK, 'progress_rollback', '进度回退'])]
    #[TestWith([AnomalyType::CONCURRENT_SESSION, 'concurrent_session', '并发会话'])]
    #[TestWith([AnomalyType::INVALID_OPERATION, 'invalid_operation', '无效操作'])]
    #[TestWith([AnomalyType::SECURITY_VIOLATION, 'security_violation', '安全违规'])]
    #[TestWith([AnomalyType::DATA_INCONSISTENCY, 'data_inconsistency', '数据不一致'])]
    public function testValueAndLabel(AnomalyType $enum, string $expectedValue, string $expectedLabel): void
    {
        self::assertSame($expectedValue, $enum->value);
        self::assertSame($expectedLabel, $enum->getLabel());

        // Test toArray format
        $array = $enum->toArray();
        self::assertIsArray($array);
        self::assertCount(2, $array);
        self::assertArrayHasKey('value', $array);
        self::assertArrayHasKey('label', $array);
        self::assertSame($expectedValue, $array['value']);
        self::assertSame($expectedLabel, $array['label']);
    }

    /**
     * 测试获取描述
     */
    public function testGetDescription(): void
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
    public function testGetCategory(): void
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
    public function testGetDefaultSeverity(): void
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
    public function testRequiresImmediateAction(): void
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
    public function testGetAllTypes(): void
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
    public function testGetByCategory(): void
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

    public function testFromWithValidValue(): void
    {
        self::assertSame(AnomalyType::MULTIPLE_DEVICE, AnomalyType::from('multiple_device'));
        self::assertSame(AnomalyType::RAPID_PROGRESS, AnomalyType::from('rapid_progress'));
        self::assertSame(AnomalyType::SECURITY_VIOLATION, AnomalyType::from('security_violation'));
        self::assertSame(AnomalyType::WINDOW_SWITCH, AnomalyType::from('window_switch'));
        self::assertSame(AnomalyType::IDLE_TIMEOUT, AnomalyType::from('idle_timeout'));
    }

    public function testTryFromWithValidValue(): void
    {
        self::assertSame(AnomalyType::MULTIPLE_DEVICE, AnomalyType::tryFrom('multiple_device'));
        self::assertSame(AnomalyType::FACE_DETECT_FAIL, AnomalyType::tryFrom('face_detect_fail'));
        self::assertSame(AnomalyType::RAPID_PROGRESS, AnomalyType::tryFrom('rapid_progress'));
        self::assertSame(AnomalyType::SECURITY_VIOLATION, AnomalyType::tryFrom('security_violation'));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn (AnomalyType $case) => $case->value, AnomalyType::cases());
        $uniqueValues = array_unique($values);

        self::assertCount(count($values), $uniqueValues, 'All enum values must be unique');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn (AnomalyType $case) => $case->getLabel(), AnomalyType::cases());
        $uniqueLabels = array_unique($labels);

        self::assertCount(count($labels), $uniqueLabels, 'All enum labels must be unique');
    }

    public function testToSelectItemReturnsCorrectFormat(): void
    {
        $selectItem = AnomalyType::MULTIPLE_DEVICE->toSelectItem();

        self::assertIsArray($selectItem);
        self::assertCount(4, $selectItem);
        self::assertArrayHasKey('value', $selectItem);
        self::assertArrayHasKey('label', $selectItem);
        self::assertArrayHasKey('text', $selectItem);
        self::assertArrayHasKey('name', $selectItem);

        self::assertSame('multiple_device', $selectItem['value']);
        self::assertSame('多设备登录', $selectItem['label']);
        self::assertSame('多设备登录', $selectItem['text']);
        self::assertSame('多设备登录', $selectItem['name']);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $result = AnomalyType::MULTIPLE_DEVICE->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('multiple_device', $result['value']);
        $this->assertEquals('多设备登录', $result['label']);
    }

    /**
     * 测试徽章样式类
     */
    public function testGetBadgeClass(): void
    {
        // 危险等级 (bg-danger)
        $this->assertEquals('bg-danger', AnomalyType::SECURITY_VIOLATION->getBadgeClass());
        $this->assertEquals('bg-danger', AnomalyType::MULTIPLE_DEVICE->getBadgeClass());
        $this->assertEquals('bg-danger', AnomalyType::MULTIPLE_DEVICE_LOGIN->getBadgeClass());

        // 警告等级 (bg-warning)
        $this->assertEquals('bg-warning', AnomalyType::FACE_DETECT_FAIL->getBadgeClass());
        $this->assertEquals('bg-warning', AnomalyType::RAPID_PROGRESS->getBadgeClass());
        $this->assertEquals('bg-warning', AnomalyType::CONCURRENT_SESSION->getBadgeClass());
        $this->assertEquals('bg-warning', AnomalyType::FAST_FORWARD->getBadgeClass());

        // 信息等级 (bg-info)
        $this->assertEquals('bg-info', AnomalyType::WINDOW_SWITCH->getBadgeClass());
        $this->assertEquals('bg-info', AnomalyType::DEVICE_CHANGE->getBadgeClass());
        $this->assertEquals('bg-info', AnomalyType::SUSPICIOUS_BEHAVIOR->getBadgeClass());
        $this->assertEquals('bg-info', AnomalyType::PROGRESS_ROLLBACK->getBadgeClass());
        $this->assertEquals('bg-info', AnomalyType::DATA_INCONSISTENCY->getBadgeClass());

        // 次要等级 (bg-secondary)
        $this->assertEquals('bg-secondary', AnomalyType::IDLE_TIMEOUT->getBadgeClass());
        $this->assertEquals('bg-secondary', AnomalyType::NETWORK_ANOMALY->getBadgeClass());
        $this->assertEquals('bg-secondary', AnomalyType::IP_CHANGE->getBadgeClass());
        $this->assertEquals('bg-secondary', AnomalyType::TIME_ANOMALY->getBadgeClass());
        $this->assertEquals('bg-secondary', AnomalyType::INVALID_OPERATION->getBadgeClass());
    }

    /**
     * 测试徽章标识
     */
    public function testGetBadge(): void
    {
        // getBadge() 应该返回与 getBadgeClass() 相同的值
        $this->assertEquals(AnomalyType::SECURITY_VIOLATION->getBadgeClass(), AnomalyType::SECURITY_VIOLATION->getBadge());
        $this->assertEquals(AnomalyType::FACE_DETECT_FAIL->getBadgeClass(), AnomalyType::FACE_DETECT_FAIL->getBadge());
        $this->assertEquals(AnomalyType::WINDOW_SWITCH->getBadgeClass(), AnomalyType::WINDOW_SWITCH->getBadge());
        $this->assertEquals(AnomalyType::IDLE_TIMEOUT->getBadgeClass(), AnomalyType::IDLE_TIMEOUT->getBadge());
    }
}
