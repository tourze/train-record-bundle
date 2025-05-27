<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * 创建学习记录相关表
 */
final class Version20250527001_CreateLearnTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return '创建学习记录管理相关的数据表';
    }

    public function up(Schema $schema): void
    {
        // 学习设备表
        $this->addSql('CREATE TABLE learn_device (
            id VARCHAR(255) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            device_fingerprint VARCHAR(255) NOT NULL,
            device_type VARCHAR(50) NOT NULL,
            device_info JSON NOT NULL,
            browser_info JSON NOT NULL,
            os_info JSON NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            is_trusted TINYINT(1) NOT NULL DEFAULT 0,
            first_seen_time DATETIME NOT NULL,
            last_seen_time DATETIME NOT NULL,
            session_count INT NOT NULL DEFAULT 0,
            suspicious_count INT NOT NULL DEFAULT 0,
            create_time DATETIME NOT NULL,
            update_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_DEVICE_USER_FINGERPRINT (user_id, device_fingerprint),
            INDEX IDX_DEVICE_USER (user_id),
            INDEX IDX_DEVICE_FINGERPRINT (device_fingerprint),
            INDEX IDX_DEVICE_ACTIVE (is_active),
            INDEX IDX_DEVICE_LAST_SEEN (last_seen_time)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // 学习行为表
        $this->addSql('CREATE TABLE learn_behavior (
            id VARCHAR(255) NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            behavior_type VARCHAR(50) NOT NULL,
            behavior_data JSON NOT NULL,
            video_timestamp DOUBLE PRECISION NOT NULL DEFAULT 0,
            device_fingerprint VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NOT NULL,
            is_suspicious TINYINT(1) NOT NULL DEFAULT 0,
            suspicious_reason TEXT DEFAULT NULL,
            create_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_BEHAVIOR_SESSION (session_id),
            INDEX IDX_BEHAVIOR_TYPE (behavior_type),
            INDEX IDX_BEHAVIOR_CREATE_TIME (create_time),
            INDEX IDX_BEHAVIOR_SUSPICIOUS (is_suspicious),
            INDEX IDX_BEHAVIOR_DEVICE (device_fingerprint)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // 学习进度表
        $this->addSql('CREATE TABLE learn_progress (
            id VARCHAR(255) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            course_id VARCHAR(255) NOT NULL,
            lesson_id VARCHAR(255) NOT NULL,
            progress DOUBLE PRECISION NOT NULL DEFAULT 0,
            watched_duration DOUBLE PRECISION NOT NULL DEFAULT 0,
            effective_duration DOUBLE PRECISION NOT NULL DEFAULT 0,
            watched_segments JSON NOT NULL,
            progress_history JSON NOT NULL,
            is_completed TINYINT(1) NOT NULL DEFAULT 0,
            last_update_time DATETIME DEFAULT NULL,
            last_update_device VARCHAR(255) DEFAULT NULL,
            create_time DATETIME NOT NULL,
            update_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_PROGRESS_USER_LESSON (user_id, lesson_id),
            INDEX IDX_PROGRESS_USER (user_id),
            INDEX IDX_PROGRESS_COURSE (course_id),
            INDEX IDX_PROGRESS_LESSON (lesson_id),
            INDEX IDX_PROGRESS_COMPLETED (is_completed),
            INDEX IDX_PROGRESS_LAST_UPDATE (last_update_time)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // 学习异常表
        $this->addSql('CREATE TABLE learn_anomaly (
            id VARCHAR(255) NOT NULL,
            session_id VARCHAR(255) NOT NULL,
            anomaly_type VARCHAR(50) NOT NULL,
            anomaly_description TEXT NOT NULL,
            anomaly_data JSON NOT NULL,
            severity VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            is_auto_detected TINYINT(1) NOT NULL DEFAULT 1,
            resolution TEXT DEFAULT NULL,
            resolved_by VARCHAR(255) DEFAULT NULL,
            detected_time DATETIME NOT NULL,
            resolved_time DATETIME DEFAULT NULL,
            create_time DATETIME NOT NULL,
            update_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_ANOMALY_SESSION (session_id),
            INDEX IDX_ANOMALY_TYPE (anomaly_type),
            INDEX IDX_ANOMALY_SEVERITY (severity),
            INDEX IDX_ANOMALY_STATUS (status),
            INDEX IDX_ANOMALY_DETECTED_TIME (detected_time)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // 学习档案表
        $this->addSql('CREATE TABLE learn_archive (
            id VARCHAR(255) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            course_id VARCHAR(255) NOT NULL,
            session_summary JSON NOT NULL,
            behavior_summary JSON NOT NULL,
            anomaly_summary JSON NOT NULL,
            total_effective_time DOUBLE PRECISION NOT NULL DEFAULT 0,
            total_sessions INT NOT NULL DEFAULT 0,
            archive_status VARCHAR(20) NOT NULL,
            archive_format VARCHAR(10) NOT NULL,
            archive_date DATETIME NOT NULL,
            expiry_date DATETIME NOT NULL,
            archive_path VARCHAR(500) DEFAULT NULL,
            archive_hash VARCHAR(255) DEFAULT NULL,
            create_time DATETIME NOT NULL,
            update_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_ARCHIVE_USER_COURSE (user_id, course_id),
            INDEX IDX_ARCHIVE_USER (user_id),
            INDEX IDX_ARCHIVE_COURSE (course_id),
            INDEX IDX_ARCHIVE_STATUS (archive_status),
            INDEX IDX_ARCHIVE_EXPIRY (expiry_date)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // 学习统计表
        $this->addSql('CREATE TABLE learn_statistics (
            id VARCHAR(255) NOT NULL,
            statistics_type VARCHAR(50) NOT NULL,
            statistics_period VARCHAR(20) NOT NULL,
            statistics_date DATETIME NOT NULL,
            scope_id VARCHAR(255) NOT NULL DEFAULT "global",
            user_statistics JSON DEFAULT NULL,
            course_statistics JSON DEFAULT NULL,
            behavior_statistics JSON DEFAULT NULL,
            anomaly_statistics JSON DEFAULT NULL,
            device_statistics JSON DEFAULT NULL,
            progress_statistics JSON DEFAULT NULL,
            duration_statistics JSON DEFAULT NULL,
            extended_data JSON DEFAULT NULL,
            create_time DATETIME NOT NULL,
            update_time DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_STATISTICS_TYPE (statistics_type),
            INDEX IDX_STATISTICS_PERIOD (statistics_period),
            INDEX IDX_STATISTICS_DATE (statistics_date),
            INDEX IDX_STATISTICS_SCOPE (scope_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE learn_statistics');
        $this->addSql('DROP TABLE learn_archive');
        $this->addSql('DROP TABLE learn_anomaly');
        $this->addSql('DROP TABLE learn_progress');
        $this->addSql('DROP TABLE learn_behavior');
        $this->addSql('DROP TABLE learn_device');
    }
} 