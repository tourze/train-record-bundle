# train-record-bundle 开发计划

## 1. 功能描述

培训记录管理包，负责学习过程的详细记录和追溯。实现学习行为监控、有效学习时长计算、防作弊检测、学习轨迹记录等功能，确保培训过程的真实性和有效性，满足安全生产培训的监管要求。

## 2. 完整能力要求

### 2.1 现有能力

- ✅ 学习会话管理（LearnSession）
- ✅ 学习日志记录（LearnLog）
- ✅ 学习时长统计（currentDuration, totalDuration）
- ✅ 学习完成状态跟踪
- ✅ IP地址和User-Agent记录
- ✅ 人脸检测关联
- ✅ 学员、课程、课时关联
- ✅ 时间戳和用户追踪

### 2.2 需要增强的能力

#### 2.2.1 符合规范要求的记录格式

- [ ] 学习记录标准化格式
- [ ] 人像抓拍记录存储
- [ ] IP地址详细记录和分析
- [ ] 有效学习时长精确计算
- [ ] 档案保存期限管理（不少于3年）
- [ ] 学习记录完整性验证

#### 2.2.2 同一账户多终端登录控制

- [ ] 设备指纹识别
- [ ] 多终端登录检测
- [ ] 学习进度同步
- [ ] 冲突处理机制
- [ ] 设备管理和限制

#### 2.2.3 防作弊监控和记录

- [ ] 学习窗口状态监控
- [ ] 页面切换检测和记录
- [ ] 无操作检测和自动锁屏
- [ ] 异常行为识别和处理
- [ ] 作弊行为分类和记录
- [ ] 作弊风险评估

#### 2.2.4 学习状态实时管理

- [ ] 实时学习状态更新
- [ ] 学习进度实时同步
- [ ] 异常状态自动处理
- [ ] 学习中断恢复机制
- [ ] 网络断线处理

#### 2.2.5 学习轨迹回放

- [ ] 学习过程完整记录
- [ ] 学习轨迹可视化
- [ ] 关键节点标记
- [ ] 异常事件高亮
- [ ] 回放速度控制

#### 2.2.6 数据统计和分析

- [ ] 学习效果分析
- [ ] 学习习惯分析
- [ ] 异常行为统计
- [ ] 学习质量评估
- [ ] 趋势分析报告

## 3. 现有实体设计分析

### 3.1 现有实体

#### LearnSession（学习会话）
- **字段**: id, student, registration, course, lesson, firstLearnTime, lastLearnTime, finished, finishTime, currentDuration, totalDuration
- **关联**: student, registration, course, lesson, faceDetects, learnLogs
- **特性**: 支持IP追踪、User-Agent记录、时间戳、用户追踪
- **约束**: 唯一约束（registration_id, lesson_id）

#### LearnLog（学习日志）
- **关联**: learnSession (多对一)
- **功能**: 记录学习过程中的详细日志

### 3.2 需要新增的实体

#### LearnBehavior（学习行为记录）
```php
class LearnBehavior
{
    private string $id;
    private LearnSession $session;
    private string $behaviorType;  // 行为类型（play, pause, seek, focus_lost, focus_gained, etc.）
    private array $behaviorData;  // 行为数据
    private float $timestamp;  // 行为发生时间戳
    private string $deviceFingerprint;  // 设备指纹
    private string $ipAddress;  // IP地址
    private string $userAgent;  // User-Agent
    private bool $isSuspicious;  // 是否可疑行为
    private \DateTimeInterface $createTime;
}
```

#### LearnDevice（学习设备）
```php
class LearnDevice
{
    private string $id;
    private string $userId;
    private string $deviceFingerprint;  // 设备指纹
    private string $deviceType;  // 设备类型（PC, Mobile, Tablet）
    private string $deviceInfo;  // 设备信息
    private string $browserInfo;  // 浏览器信息
    private string $osInfo;  // 操作系统信息
    private bool $isActive;  // 是否活跃
    private \DateTimeInterface $firstSeenTime;  // 首次见到时间
    private \DateTimeInterface $lastSeenTime;  // 最后见到时间
    private int $sessionCount;  // 会话数量
}
```

#### LearnProgress（学习进度）
```php
class LearnProgress
{
    private string $id;
    private string $userId;
    private Course $course;
    private Lesson $lesson;
    private float $progress;  // 进度百分比
    private float $watchedDuration;  // 已观看时长
    private float $effectiveDuration;  // 有效学习时长
    private array $watchedSegments;  // 已观看片段
    private bool $isCompleted;  // 是否完成
    private \DateTimeInterface $lastUpdateTime;
    private string $deviceFingerprint;  // 最后更新设备
}
```

#### LearnAnomaly（学习异常）
```php
class LearnAnomaly
{
    private string $id;
    private LearnSession $session;
    private string $anomalyType;  // 异常类型
    private string $anomalyDescription;  // 异常描述
    private array $anomalyData;  // 异常数据
    private string $severity;  // 严重程度（low, medium, high, critical）
    private bool $isResolved;  // 是否已解决
    private string $resolution;  // 解决方案
    private \DateTimeInterface $detectedTime;  // 检测时间
    private \DateTimeInterface $resolvedTime;  // 解决时间
}
```

#### LearnArchive（学习档案）
```php
class LearnArchive
{
    private string $id;
    private string $userId;
    private Course $course;
    private array $sessionSummary;  // 会话汇总
    private array $behaviorSummary;  // 行为汇总
    private array $anomalySummary;  // 异常汇总
    private float $totalEffectiveTime;  // 总有效学习时长
    private int $totalSessions;  // 总会话数
    private string $archiveStatus;  // 档案状态
    private \DateTimeInterface $archiveDate;  // 归档日期
    private \DateTimeInterface $expiryDate;  // 过期日期（3年后）
    private string $archivePath;  // 归档文件路径
}
```

#### LearnStatistics（学习统计）
```php
class LearnStatistics
{
    private string $id;
    private string $statisticsType;  // 统计类型（daily, weekly, monthly）
    private \DateTimeInterface $statisticsDate;  // 统计日期
    private array $userStatistics;  // 用户统计
    private array $courseStatistics;  // 课程统计
    private array $behaviorStatistics;  // 行为统计
    private array $anomalyStatistics;  // 异常统计
    private array $deviceStatistics;  // 设备统计
    private \DateTimeInterface $createTime;
}
```

## 4. 服务设计

### 4.1 现有服务增强

#### LearnSessionService
```php
class LearnSessionService
{
    // 现有方法保持不变
    
    // 新增方法
    public function startSession(string $userId, string $lessonId, array $deviceInfo): LearnSession;
    public function updateProgress(string $sessionId, float $currentTime, array $behaviorData): void;
    public function pauseSession(string $sessionId, string $reason): void;
    public function resumeSession(string $sessionId): void;
    public function finishSession(string $sessionId): void;
    public function detectMultipleDevices(string $userId): array;
    public function syncProgress(string $userId, string $courseId): void;
}
```

### 4.2 新增服务

#### LearnBehaviorService
```php
class LearnBehaviorService
{
    public function recordBehavior(string $sessionId, string $behaviorType, array $behaviorData): LearnBehavior;
    public function detectSuspiciousBehavior(string $sessionId, array $behaviors): array;
    public function analyzeBehaviorPattern(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
    public function generateBehaviorReport(string $sessionId): array;
    public function getBehaviorTimeline(string $sessionId): array;
}
```

#### LearnDeviceService
```php
class LearnDeviceService
{
    public function registerDevice(string $userId, array $deviceInfo): LearnDevice;
    public function generateDeviceFingerprint(array $deviceInfo): string;
    public function validateDevice(string $userId, string $deviceFingerprint): bool;
    public function getActiveDevices(string $userId): array;
    public function deactivateDevice(string $deviceId): void;
    public function checkDeviceLimit(string $userId): bool;
}
```

#### LearnProgressService
```php
class LearnProgressService
{
    public function updateProgress(string $userId, string $lessonId, float $progress, array $watchedSegments): LearnProgress;
    public function syncProgressAcrossDevices(string $userId, string $courseId): void;
    public function calculateEffectiveTime(array $watchedSegments, array $behaviors): float;
    public function getProgressSummary(string $userId, string $courseId): array;
    public function validateProgress(string $userId, string $lessonId): bool;
}
```

#### LearnAnomalyService
```php
class LearnAnomalyService
{
    public function detectAnomaly(string $sessionId, array $behaviorData): ?LearnAnomaly;
    public function classifyAnomaly(array $anomalyData): string;
    public function resolveAnomaly(string $anomalyId, string $resolution): void;
    public function getAnomalyReport(string $userId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
    public function getAnomalyTrends(): array;
}
```

#### LearnArchiveService
```php
class LearnArchiveService
{
    public function createArchive(string $userId, string $courseId): LearnArchive;
    public function archiveCompletedSessions(): void;
    public function getArchiveData(string $archiveId): array;
    public function exportArchive(string $archiveId, string $format): string;
    public function cleanupExpiredArchives(): void;
    public function validateArchiveIntegrity(string $archiveId): bool;
}
```

#### LearnAnalyticsService
```php
class LearnAnalyticsService
{
    public function generateDailyStatistics(\DateTimeInterface $date): LearnStatistics;
    public function analyzeLearningEffectiveness(string $userId, string $courseId): array;
    public function getLearningTrends(string $userId, int $days): array;
    public function getAnomalyInsights(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
    public function generateComplianceReport(string $courseId): array;
}
```

## 5. Command设计

### 5.1 数据处理命令

#### LearnDataProcessCommand
```php
class LearnDataProcessCommand extends Command
{
    protected static $defaultName = 'learn:data:process';
    
    // 处理学习数据，计算有效学习时长
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### LearnAnomalyDetectCommand
```php
class LearnAnomalyDetectCommand extends Command
{
    protected static $defaultName = 'learn:anomaly:detect';
    
    // 批量检测学习异常
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.2 归档和清理命令

#### LearnArchiveCommand
```php
class LearnArchiveCommand extends Command
{
    protected static $defaultName = 'learn:archive';
    
    // 归档完成的学习记录
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### LearnCleanupCommand
```php
class LearnCleanupCommand extends Command
{
    protected static $defaultName = 'learn:cleanup';
    
    // 清理过期的学习数据
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.3 统计和分析命令

#### LearnStatisticsCommand
```php
class LearnStatisticsCommand extends Command
{
    protected static $defaultName = 'learn:statistics';
    
    // 生成学习统计报告（每日执行）
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### LearnReportCommand
```php
class LearnReportCommand extends Command
{
    protected static $defaultName = 'learn:report';
    
    // 生成学习分析报告
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

### 5.4 监控和维护命令

#### LearnMonitorCommand
```php
class LearnMonitorCommand extends Command
{
    protected static $defaultName = 'learn:monitor';
    
    // 实时监控学习状态
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

#### LearnValidateCommand
```php
class LearnValidateCommand extends Command
{
    protected static $defaultName = 'learn:validate';
    
    // 验证学习记录完整性
    public function execute(InputInterface $input, OutputInterface $output): int;
}
```

## 6. 依赖包

- `train-course-bundle` - 课程管理
- `train-teacher-bundle` - 教师管理
- `face-detect-bundle` - 人脸检测
- `doctrine-logger-bundle` - 日志记录
- `doctrine-ip-bundle` - IP追踪
- `doctrine-user-agent-bundle` - User-Agent追踪

## 7. 测试计划

### 7.1 单元测试

- [ ] LearnSession实体测试
- [ ] LearnBehavior实体测试
- [ ] LearnBehaviorService测试
- [ ] LearnProgressService测试
- [ ] LearnAnomalyService测试

### 7.2 集成测试

- [ ] 学习会话完整流程测试
- [ ] 多设备同步测试
- [ ] 异常检测测试
- [ ] 进度计算测试

### 7.3 性能测试

- [ ] 大量并发学习会话测试
- [ ] 行为数据处理性能测试
- [ ] 实时同步性能测试
- [ ] 归档处理性能测试

## 8. 部署和运维

### 8.1 部署要求

- PHP 8.2+
- MySQL 8.0+ / PostgreSQL 14+
- Redis（缓存和实时数据）
- 足够的存储空间（行为数据量大）

### 8.2 监控指标

- 学习会话成功率
- 异常检测准确率
- 数据同步延迟
- 归档处理效率
- 存储空间使用率

### 8.3 数据备份

- [ ] 实时数据备份
- [ ] 归档数据备份
- [ ] 备份数据完整性验证
- [ ] 灾难恢复测试

---

**文档版本**: v1.0
**创建日期**: 2024年12月
**负责人**: 开发团队 