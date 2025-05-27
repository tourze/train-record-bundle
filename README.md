# Train Record Bundle

培训记录管理包 - 用于安全生产培训系统的学习过程记录和追溯管理。

## 功能特性

### 🎯 核心功能
- **学习会话管理** - 完整的学习会话生命周期控制
- **学习进度跟踪** - 跨设备学习进度同步和有效时长计算
- **行为监控** - 实时学习行为收集和可疑行为检测
- **设备管理** - 设备指纹识别和多设备学习监控
- **异常检测** - 智能异常检测和处理机制
- **数据归档** - 3年保存期限的学习记录归档管理
- **数据分析** - 多维度学习数据分析和智能洞察

### 📊 统计分析
- 实时学习统计
- 用户学习画像生成
- 课程分析和优化建议
- 学习趋势分析
- 异常行为分析

### 🔒 安全特性
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

#### 服务层 (Service)
- `LearnSessionService` - 会话管理服务
- `LearnProgressService` - 进度管理服务
- `LearnBehaviorService` - 行为分析服务
- `LearnDeviceService` - 设备管理服务
- `LearnArchiveService` - 档案管理服务
- `LearnAnalyticsService` - 数据分析服务

#### 枚举系统 (Enum)
- `BehaviorType` - 行为类型
- `LearnAction` - 学习动作
- `AnomalyType` - 异常类型
- `AnomalySeverity` - 异常严重程度
- `ArchiveStatus` - 档案状态
- `ArchiveFormat` - 归档格式
- `StatisticsPeriod` - 统计周期
- `StatisticsType` - 统计类型

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

## 数据库表结构

| 表名 | 说明 |
|------|------|
| `job_training_learn_session` | 学习会话记录 |
| `job_training_learn_progress` | 学习进度记录 |
| `job_training_learn_behavior` | 学习行为记录 |
| `job_training_learn_anomaly` | 学习异常记录 |
| `job_training_learn_device` | 学习设备记录 |
| `job_training_learn_archive` | 学习档案记录 |
| `job_training_learn_statistics` | 学习统计记录 |

## 测试

### 运行测试

```bash
# 运行所有测试
vendor/bin/phpunit packages/train-record-bundle/tests/

# 运行单元测试
vendor/bin/phpunit packages/train-record-bundle/tests/Unit/

# 运行集成测试
vendor/bin/phpunit packages/train-record-bundle/tests/Integration/
```

### 测试覆盖率

当前测试状态：
- ✅ 枚举测试：8个测试，100个断言全部通过
- 🔄 服务测试：开发中
- 🔄 集成测试：开发中

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

- PHP 8.2+
- Symfony 6.0+
- Doctrine ORM 2.14+
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

## 支持

如有问题或建议，请提交 Issue 或联系开发团队。
