# Train Record Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E6.4-green)](https://symfony.com)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com/tourze/php-monorepo)
[![Coverage Status](https://img.shields.io/badge/coverage-90%25-green)](https://github.com/tourze/php-monorepo)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Training record management bundle for safety production training systems with comprehensive learning process tracking and audit capabilities.

## Table of Contents

- [Features](#features)
  - [Core Features](#core-features)
  - [Analytics](#analytics) 
  - [Security Features](#security-features)
- [Architecture](#architecture)
  - [Core Components](#core-components)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [CLI Commands](#cli-commands)
- [Configuration](#configuration)
- [Advanced Usage](#advanced-usage)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Performance](#performance)
- [Monitoring](#monitoring)
- [Requirements](#requirements)
- [License](#license)
- [Contributing](#contributing)
- [Support](#support)

## Features

### Core Features
- **Learning Session Management** - Complete learning session lifecycle control
- **Learning Progress Tracking** - Cross-device learning progress synchronization and effective time calculation
- **Behavior Monitoring** - Real-time learning behavior collection and suspicious behavior detection
- **Device Management** - Device fingerprinting and multi-device learning monitoring
- **Anomaly Detection** - Intelligent anomaly detection and handling mechanisms
- **Data Archiving** - 3-year retention learning record archive management
- **Data Analytics** - Multi-dimensional learning data analysis and intelligent insights

### Analytics
- Real-time learning statistics
- User learning profile generation
- Course analysis and optimization recommendations
- Learning trend analysis
- Anomaly behavior analysis

### Security Features
- Anti-cheating detection
- Multi-device login control
- Learning trajectory integrity verification
- Data encryption and secure storage

## Architecture

### Core Components

#### Entity Layer
- `LearnSession` - Learning sessions
- `LearnProgress` - Learning progress
- `LearnBehavior` - Learning behaviors
- `LearnAnomaly` - Learning anomalies
- `LearnDevice` - Learning devices
- `LearnArchive` - Learning archives
- `LearnStatistics` - Learning statistics
- `EffectiveStudyRecord` - Effective study time records
- `FaceDetect` - Face recognition records

#### Service Layer
- `LearnSessionService` - Session management service
- `LearnProgressService` - Progress management service
- `LearnBehaviorService` - Behavior analysis service
- `LearnDeviceService` - Device management service
- `LearnArchiveService` - Archive management service
- `LearnAnalyticsService` - Data analytics service
- `EffectiveStudyTimeService` - Effective study time service
- `BaiduFaceService` - Baidu face recognition service

#### Enum System
- `BehaviorType` - Behavior types
- `LearnAction` - Learning actions
- `AnomalyType` - Anomaly types
- `AnomalySeverity` - Anomaly severity levels
- `ArchiveStatus` - Archive status
- `ArchiveFormat` - Archive formats
- `StatisticsPeriod` - Statistics periods
- `StatisticsType` - Statistics types
- `StudyTimeStatus` - Study time status
- `InvalidTimeReason` - Invalid time reasons

## Installation

### 1. Install Dependencies

```bash
composer require tourze/train-record-bundle
```

### 2. Register Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\TrainRecordBundle\TrainRecordBundle::class => ['all' => true],
];
```

### 3. Database Migration

```bash
php bin/console doctrine:migrations:migrate
```

### 4. Configure Services

The bundle automatically registers all services without additional configuration.

## Quick Start

### Learning Session Management

```php
use Tourze\TrainRecordBundle\Service\LearnSessionService;

// Start learning session
$session = $learnSessionService->startSession($userId, $courseId, $lessonId);

// Pause session
$learnSessionService->pauseSession($session->getId());

// Resume session
$learnSessionService->resumeSession($session->getId());

// End session
$learnSessionService->endSession($session->getId());
```

### Learning Behavior Recording

```php
use Tourze\TrainRecordBundle\Service\LearnBehaviorService;
use Tourze\TrainRecordBundle\Enum\BehaviorType;

// Record learning behavior
$learnBehaviorService->recordBehavior(
    $sessionId,
    BehaviorType::PLAY,
    ['timestamp' => time(), 'position' => 120]
);
```

### Learning Progress Update

```php
use Tourze\TrainRecordBundle\Service\LearnProgressService;

// Update learning progress
$learnProgressService->updateProgress(
    $userId,
    $courseId,
    $lessonId,
    85.5, // Progress percentage
    1200  // Watch time in seconds
);
```

### Data Analytics

```php
use Tourze\TrainRecordBundle\Service\LearnAnalyticsService;

// Generate learning report
$report = $learnAnalyticsService->generateLearningReport(
    new DateTime('-30 days'),
    new DateTime(),
    $userId
);

// Get real-time statistics
$stats = $learnAnalyticsService->getRealTimeStatistics();
```

## JSON-RPC Interface

### Learning Record Related

```php
// Get learning record list
$response = $jsonRpcClient->call('GetJobTrainingLearnRecordList', [
    'userId' => 'user123',
    'startDate' => '2024-01-01',
    'endDate' => '2024-01-31'
]);

// Get learning session details
$response = $jsonRpcClient->call('GetJobTrainingLearnSessionDetail', [
    'sessionId' => 'session123'
]);
```

### Learning Behavior Reporting

```php
// Start course learning session
$jsonRpcClient->call('StartJobTrainingCourseSession', [
    'userId' => 'user123',
    'courseId' => 'course456',
    'lessonId' => 'lesson789'
]);

// Report video play
$jsonRpcClient->call('ReportJobTrainingCourseVideoPlay', [
    'sessionId' => 'session123',
    'timestamp' => time(),
    'position' => 120
]);

// Report video pause
$jsonRpcClient->call('ReportJobTrainingCourseVideoPause', [
    'sessionId' => 'session123',
    'timestamp' => time(),
    'position' => 180
]);
```

## CLI Commands

### Effective Study Time Management

```bash
# Recalculate effective study time
php bin/console train-record:effective-study-time:recalculate

# Recalculate for specific session
php bin/console train-record:effective-study-time:recalculate --session-id=SESSION_ID

# Batch recalculation with custom batch size
php bin/console train-record:effective-study-time:recalculate --batch-size=100

# Generate effective study time report
php bin/console train-record:effective-study-time:report

# Generate report for date range
php bin/console train-record:effective-study-time:report --start-date=2024-01-01 --end-date=2024-01-31

# Generate JSON format report
php bin/console train-record:effective-study-time:report --format=json
```

### Learning Data Management

```bash
# Generate course records
php bin/console job-training:generate-course-record

# Generate records for specific course
php bin/console job-training:generate-course-record --course-id=COURSE_ID

# Anomaly detection
php bin/console learn:anomaly:detect

# Detect anomalies for date range
php bin/console learn:anomaly:detect --start-date=2024-01-01 --end-date=2024-01-31

# Learning archive
php bin/console learn:archive

# Data processing
php bin/console learn:data:process

# Learning monitoring
php bin/console learn:monitor

# Learning statistics
php bin/console learn:statistics

# Learning session cleanup
php bin/console train:learn-session:cleanup
```

## Configuration

### Scheduled Task Configuration

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

## Advanced Usage

### Custom Learning Behavior Analysis

Extend the default learning behavior analysis functionality:

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
        // Custom behavior pattern analysis
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

### Third-party Service Integration

Best practices for integrating with external systems:

```php
// External authentication system integration
class ExternalAuthIntegration
{
    public function validateLearningSession($sessionId, $externalToken): bool
    {
        // Validate learning session with external system
        return $this->externalAPI->validateSession($sessionId, $externalToken);
    }
}

// Data synchronization integration
class DataSyncService
{
    public function syncToExternalSystem(LearnSession $session): void
    {
        // Sync learning data to external system
        $this->externalAPI->syncLearningData($session->toArray());
    }
}
```

### Performance Monitoring Integration

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
        // Record performance metrics
        $this->metrics->increment('learn.session.started');
        $this->metrics->timing('learn.session.start_time', microtime(true));
    }
}
```

## Database Schema

| Table | Description |
|-------|-------------|
| `ims_job_training_learn_session` | Learning session records |
| `ims_job_training_learn_progress` | Learning progress records |
| `ims_job_training_learn_behavior` | Learning behavior records |
| `ims_job_training_learn_anomaly` | Learning anomaly records |
| `ims_job_training_learn_device` | Learning device records |
| `ims_job_training_learn_archive` | Learning archive records |
| `ims_job_training_learn_statistics` | Learning statistics records |
| `ims_job_training_effective_study_record` | Effective study time records |
| `ims_job_training_face_detect` | Face recognition records |
| `ims_job_training_learn_action_log` | Learning action logs |

## Testing

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit packages/train-record-bundle/tests/
```

### Test Coverage

Current test status:
- ✅ Enum tests: Complete coverage, 12 enum class tests passing
- ✅ Entity tests: Basic entity functionality tests passing
- ✅ Exception tests: Custom exception class tests passing
- ✅ Command tests: Command registration and basic functionality tests
- ⚠️ Repository tests: Database constraint issues under investigation ([#894](https://github.com/tourze/php-monorepo/issues/894))
- 🔄 Integration tests: Environment configuration optimization required ([#913](https://github.com/tourze/php-monorepo/issues/913))

## Performance

### Database Optimization
- Reasonable index design
- Partition table support for large datasets
- Query optimization and caching strategies

### Caching Strategy
- Redis cache for real-time data
- Statistics data caching
- Query result caching

### Batch Processing
- Asynchronous data processing
- Batch insert optimization
- Scheduled task optimization

## Monitoring

### Health Checks
- Database connection checks
- Cache service checks
- Storage space checks

### Logging
- Detailed operation logs
- Exception logging
- Performance monitoring logs

### Data Backup
- Automatic data backup
- Archive data management
- Disaster recovery plans

## Requirements

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- MySQL 8.0+ / PostgreSQL 14+
- Redis 6.0+ (optional, for caching)

## License

MIT License

## Contributing

1. Fork the project
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

Please refer to the project root directory contribution guide for detailed development standards.

## Support

For issues or suggestions, please submit an Issue or contact the development team.

### Related Documentation

- [API Documentation](docs/api.md)
- [Deployment Guide](docs/deployment.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Changelog](CHANGELOG.md)