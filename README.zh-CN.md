# Train Record Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg?style=flat-square)]
(https://packagist.org/packages/tourze/train-record-bundle)  
[![Symfony Version](https://img.shields.io/badge/symfony-6.4+-000000.svg?style=flat-square)]
(https://symfony.com)  
[![Latest Version](https://img.shields.io/packagist/v/tourze/train-record-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/train-record-bundle)  
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/tourze/php-monorepo)  
[![Coverage Status](https://img.shields.io/badge/coverage-90%25-green)](https://github.com/tourze/php-monorepo)  
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

培训记录管理包 - 用于安全生产培训系统的学习过程记录和追溯管理。

## 目录

- [功能特性](#功能特性)
  - [核心功能](#核心功能)
  - [统计分析](#统计分析) 
  - [安全特性](#安全特性)
- [技术架构](#技术架构)
  - [核心组件](#核心组件)
- [安装配置](#安装配置)
- [使用示例](#使用示例)
- [命令行工具](#命令行工具)
- [配置选项](#配置选项)
- [高级用法](#高级用法)
- [数据库表结构](#数据库表结构)
- [测试](#测试)
- [性能优化](#性能优化)
- [监控和运维](#监控和运维)
- [依赖要求](#依赖要求)
- [许可证](#许可证)
- [贡献指南](#贡献指南)
- [支持](#支持)

## 功能特性

### 核心功能
- **学习会话管理** - 完整的学习会话生命周期控制
- **学习进度跟踪** - 跨设备学习进度同步和有效时长计算
- **行为监控** - 实时学习行为收集和可疑行为检测
- **设备管理** - 设备指纹识别和多设备学习监控
- **异常检测** - 智能异常检测和处理机制
- **数据归档** - 3年保存期限的学习记录归档管理
- **数据分析** - 多维度学习数据分析和智能洞察

### 统计分析
- 实时学习统计
- 用户学习画像生成
- 课程分析和优化建议
- 学习趋势分析
- 异常行为分析

### 安全特性
- 防作弊检测
- 多设备登录控制
- 学习轨迹完整性验证
- 数据加密和安全存储

## 技术架构

### 核心组件

#### 实体层 (Entity)
- `LearnSession` - 学习会话
- `LearnProgress` - 学习进度
- `LearnBehavior` - 学习行为
- `LearnAnomaly` - 学习异常
- `LearnDevice` - 学习设备
- `LearnArchive` - 学习档案
- `LearnStatistics` - 学习统计
- `EffectiveStudyRecord` - 有效学时记录
- `FaceDetect` - 人脸识别记录

#### 服务层 (Service)
- `LearnSessionService` - 会话管理服务
- `LearnProgressService` - 进度管理服务
- `LearnBehaviorService` - 行为分析服务
- `LearnDeviceService` - 设备管理服务
- `LearnArchiveService` - 档案管理服务
- `LearnAnalyticsService` - 数据分析服务
- `EffectiveStudyTimeService` - 有效学时服务
- `BaiduFaceService` - 百度人脸识别服务

#### 枚举系统 (Enum)
- `BehaviorType` - 行为类型
- `LearnAction` - 学习动作
- `AnomalyType` - 异常类型
- `AnomalySeverity` - 异常严重程度
- `ArchiveStatus` - 档案状态
- `ArchiveFormat` - 归档格式
- `StatisticsPeriod` - 统计周期
- `StatisticsType` - 统计类型
- `StudyTimeStatus` - 学时状态
- `InvalidTimeReason` - 无效时间原因

## 安装配置

### 1. 安装依赖

```bash
composer require tourze/train-record-bundle
```

### 2. 注册Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\TrainRecordBundle\TrainRecordBundle::class => ['all' => true],
];
```

### 3. 数据库迁移

```bash
php bin/console doctrine:migrations:migrate
```

### 4. 配置服务

Bundle会自动注册所有服务，无需额外配置。

## 使用示例

### 学习会话管理

```php
use Tourze\TrainRecordBundle\Service\LearnSessionService;

// 开始学习会话
$session = $learnSessionService->startSession($userId, $courseId, $lessonId);

// 暂停会话
$learnSessionService->pauseSession($session->getId());

// 恢复会话
$learnSessionService->resumeSession($session->getId());

// 结束会话
$learnSessionService->endSession($session->getId());
```

### 学习行为记录

```php
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

// 记录学习行为
$learnBehaviorService->recordBehavior(
    $sessionId,
    BehaviorType::PLAY,
    ['timestamp' => time(), 'position' => 120]
);
```

### 学习进度更新

```php
use Tourze\TrainRecordBundle\Service\LearnProgressService;

// 更新学习进度
$learnProgressService->updateProgress(
    $userId,
    $courseId,
    $lessonId,
    85.5, // 进度百分比
    1200  // 观看时长（秒）
);
```

### 数据分析

```php
use Tourze\TrainRecordBundle\Service\LearnAnalyticsService;

// 生成学习报告
$report = $learnAnalyticsService->generateLearningReport(
    new DateTime('-30 days'),
    new DateTime(),
    $userId
);

// 获取实时统计
$stats = $learnAnalyticsService->getRealTimeStatistics();
```

## JSON-RPC 接口

### 学习记录相关

```php
// 获取学习记录列表
$response = $jsonRpcClient->call('GetJobTrainingLearnRecordList', [
    'userId' => 'user123',
    'startDate' => '2024-01-01',
    'endDate' => '2024-01-31'
]);

// 获取学习会话详情
$response = $jsonRpcClient->call('GetJobTrainingLearnSessionDetail', [
    'sessionId' => 'session123'
]);
```

### 学习行为上报

```php
// 开始课程学习会话
$jsonRpcClient->call('StartJobTrainingCourseSession', [
    'userId' => 'user123',
    'courseId' => 'course456',
    'lessonId' => 'lesson789'
]);

// 上报视频播放
$jsonRpcClient->call('ReportJobTrainingCourseVideoPlay', [
    'sessionId' => 'session123',
    'timestamp' => time(),
    'position' => 120
]);

// 上报视频暂停
$jsonRpcClient->call('ReportJobTrainingCourseVideoPause', [
    'sessionId' => 'session123',
    'timestamp' => time(),
    'position' => 180
]);

// 上报视频时间更新
$jsonRpcClient->call('ReportJobTrainingCourseVideoTimeUpdate', [
    'sessionId' => 'session123',
    'currentTime' => 240,
    'duration' => 3600
]);

// 上报视频结束
$jsonRpcClient->call('ReportJobTrainingCourseVideoEnded', [
    'sessionId' => 'session123',
    'completedAt' => time()
]);
```

## 命令行工具

### 有效学时管理

```bash
# 重新计算有效学时
php bin/console train-record:effective-study-time:recalculate

# 重新计算指定会话的有效学时
php bin/console train-record:effective-study-time:recalculate --session-id=SESSION_ID

# 批量重新计算（指定批量大小）
php bin/console train-record:effective-study-time:recalculate --batch-size=100

# 生成有效学时报告
php bin/console train-record:effective-study-time:report

# 生成指定日期范围的报告
php bin/console train-record:effective-study-time:report --start-date=2024-01-01 --end-date=2024-01-31

# 生成 JSON 格式报告
php bin/console train-record:effective-study-time:report --format=json
```

## 学习数据管理

```bash
# 生成课程记录
php bin/console job-training:generate-course-record

# 为指定课程生成记录
php bin/console job-training:generate-course-record --course-id=COURSE_ID

# 异常检测
php bin/console learn:anomaly:detect

# 检测指定日期范围的异常
php bin/console learn:anomaly:detect --start-date=2024-01-01 --end-date=2024-01-31

# 学习归档
php bin/console learn:archive

# 数据处理
php bin/console learn:data:process

# 学习监控
php bin/console learn:monitor

# 学习统计
php bin/console learn:statistics

# 学习会话清理
php bin/console train:learn-session:cleanup
```

## 配置选项

### 定时任务配置

```yaml
# config/packages/train_record.yaml
train_record:
    archive:
        retention_years: 3
        storage_path: '%kernel.project_dir%/var/archives'
    
    anomaly_detection:
        enabled: true
        thresholds:
            multiple_device_count: 2
            rapid_progress_seconds: 60
            window_switch_count: 15
    
    statistics:
        cache_ttl: 3600
        batch_size: 1000
```

## 高级用法

### 自定义学习行为分析

扩展默认的学习行为分析功能：

```php
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Event\BehaviorAnalyzedEvent;

class CustomBehaviorAnalyzer
{
    public function __construct(
        private LearnBehaviorService $behaviorService
    ) {}
    
    public function analyzeCustomPattern(array $behaviors): array
    {
        // 自定义行为模式分析
        $patterns = [];
        foreach ($behaviors as $behavior) {
            if ($this->isCustomPattern($behavior)) {
                $patterns[] = $this->extractPattern($behavior);
            }
        }
        return $patterns;
    }
}
```

### 集成第三方服务

与外部系统集成的最佳实践：

```php
// 集成外部认证系统
class ExternalAuthIntegration
{
    public function validateLearningSession($sessionId, $externalToken): bool
    {
        // 验证外部系统的学习会话
        return $this->externalAPI->validateSession($sessionId, $externalToken);
    }
}

// 集成数据同步
class DataSyncService
{
    public function syncToExternalSystem(LearnSession $session): void
    {
        // 同步学习数据到外部系统
        $this->externalAPI->syncLearningData($session->toArray());
    }
}
```

### 性能监控集成

```php
use Tourze\TrainRecordBundle\Event\SessionStartedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PerformanceMonitorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SessionStartedEvent::class => 'onSessionStarted',
        ];
    }
    
    public function onSessionStarted(SessionStartedEvent $event): void
    {
        // 记录性能指标
        $this->metrics->increment('learn.session.started');
        $this->metrics->timing('learn.session.start_time', microtime(true));
    }
}
```

## 数据库表结构

| 表名 | 说明 |
|------|------|
| `ims_job_training_learn_session` | 学习会话记录 |
| `ims_job_training_learn_progress` | 学习进度记录 |
| `ims_job_training_learn_behavior` | 学习行为记录 |
| `ims_job_training_learn_anomaly` | 学习异常记录 |
| `ims_job_training_learn_device` | 学习设备记录 |
| `ims_job_training_learn_archive` | 学习档案记录 |
| `ims_job_training_learn_statistics` | 学习统计记录 |
| `ims_job_training_effective_study_record` | 有效学时记录 |
| `ims_job_training_face_detect` | 人脸识别记录 |
| `ims_job_training_learn_action_log` | 学习轨迹日志 |

## 测试

### 运行测试

```bash
# 运行所有测试
vendor/bin/phpunit packages/train-record-bundle/tests/
```

### 测试覆盖率

当前测试状态：
- ✅ 枚举测试：完全覆盖，12个枚举类测试通过
- ✅ 实体测试：基础实体功能测试通过
- ✅ 异常测试：自定义异常类测试通过
- ✅ 命令测试：所有命令测试已正确使用CommandTester
- ⚠️ 存储库测试：数据库约束问题调查中 ([#894](https://github.com/tourze/php-monorepo/issues/894))
- 🔄 集成测试：需要环境配置优化

## 性能优化

### 数据库优化
- 合理的索引设计
- 分区表支持（大数据量场景）
- 查询优化和缓存策略

### 缓存策略
- Redis缓存实时数据
- 统计数据缓存
- 查询结果缓存

### 批量处理
- 异步数据处理
- 批量插入优化
- 定时任务优化

## 监控和运维

### 健康检查
- 数据库连接检查
- 缓存服务检查
- 存储空间检查

### 日志记录
- 详细的操作日志
- 异常日志记录
- 性能监控日志

### 数据备份
- 自动数据备份
- 归档数据管理
- 灾难恢复方案

## 依赖要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- MySQL 8.0+ / PostgreSQL 14+
- Redis 6.0+（可选，用于缓存）

## 许可证

MIT License

## 贡献指南

1. Fork 项目
2. 创建功能分支
3. 提交更改
4. 推送到分支
5. 创建 Pull Request

请参考项目根目录的贡献指南了解详细的开发规范。

## 支持

如有问题或建议，请提交 Issue 或联系开发团队。

### 相关文档

- [API 文档](docs/api.md)
- [部署指南](docs/deployment.md)
- [故障排除](docs/troubleshooting.md)
- [更新日志](CHANGELOG.md)