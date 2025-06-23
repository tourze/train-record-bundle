<?php

namespace Tourze\TrainRecordBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Enum\ArchiveFormat;
use Tourze\TrainRecordBundle\Enum\ArchiveStatus;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnArchiveService;

#[AsCommand(
    name: self::NAME,
    description: '归档完成的学习记录'
)]
class LearnArchiveCommand extends Command
{
    protected const NAME = 'learn:archive';
    public function __construct(
                private readonly LearnArchiveRepository $archiveRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnArchiveService $archiveService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定要归档的用户ID'
            )
            ->addOption(
                'course-id',
                'c',
                InputOption::VALUE_OPTIONAL,
                '指定要归档的课程ID'
            )
            ->addOption(
                'archive-id',
                'a',
                InputOption::VALUE_OPTIONAL,
                '指定要操作的档案ID'
            )
            ->addOption(
                'action',
                null,
                InputOption::VALUE_OPTIONAL,
                '操作类型 (create, update, verify, export, cleanup)',
                'create'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '归档格式 (json, xml, pdf)',
                'json'
            )
            ->addOption(
                'export-path',
                'p',
                InputOption::VALUE_OPTIONAL,
                '导出路径'
            )
            ->addOption(
                'days-before-expiry',
                'd',
                InputOption::VALUE_OPTIONAL,
                '过期前天数（用于清理）',
                30
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '批处理大小',
                20
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '试运行模式，不实际执行操作'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userId = $input->getOption('user-id');
        $courseId = $input->getOption('course-id');
        $archiveId = $input->getOption('archive-id');
        $action = $input->getOption('action');
        $format = $input->getOption('format');
        $exportPath = $input->getOption('export-path');
        $daysBeforeExpiry = (int) $input->getOption('days-before-expiry');
        $batchSize = (int) $input->getOption('batch-size');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('学习档案管理');

        if ((bool) $dryRun) {
            $io->note('运行在试运行模式，不会实际执行操作');
        }

        try {
            $result = match ($action) {
                'create' => $this->createArchives($userId, $courseId, $format, $batchSize, $dryRun, $io),
                'update' => $this->updateArchives($archiveId, $userId, $courseId, $batchSize, $dryRun, $io),
                'verify' => $this->verifyArchives($archiveId, $userId, $courseId, $batchSize, $io),
                'export' => $this->exportArchives($archiveId, $format, $exportPath, $dryRun, $io),
                'cleanup' => $this->cleanupArchives($daysBeforeExpiry, $dryRun, $io),
                default => throw new \InvalidArgumentException("不支持的操作类型: {$action}")
            };

            $io->success($result['message']);
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('学习档案管理失败', [
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('档案管理失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 创建档案
     */
    private function createArchives(
        ?string $userId,
        ?string $courseId,
        string $format,
        int $batchSize,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $io->section('创建学习档案');

        $archiveFormat = ArchiveFormat::from($format);
        $createdCount = 0;
        $errorCount = 0;

        if ($userId !== null && $courseId !== null) {
            // 创建指定用户和课程的档案
            $io->text("为用户 {$userId} 的课程 {$courseId} 创建档案");
            
            if (!$dryRun) {
                try {
                    $this->archiveService->createArchive($userId, $courseId, $archiveFormat->value);
                    $createdCount = 1;
                } catch (\Throwable $e) {
                    $errorCount = 1;
                    $io->error("创建档案失败: " . $e->getMessage());
                }
            } else {
                $createdCount = 1;
            }
        } else {
            // 批量创建档案
            $completedSessions = $this->getCompletedSessions($userId, $courseId);
            $userCourses = $this->groupSessionsByUserAndCourse($completedSessions);

            $io->text(sprintf("找到 %d 个用户-课程组合需要创建档案", count($userCourses)));

            $progressBar = $io->createProgressBar(count($userCourses));
            $progressBar->start();

            foreach (array_chunk($userCourses, $batchSize) as $batch) {
                foreach ($batch as $userCourse) {
                    try {
                        if (!$dryRun) {
                            // 检查是否已存在档案
                            $existingArchive = $this->archiveRepository->findByUserAndCourse(
                                $userCourse['userId'],
                                $userCourse['courseId']
                            );

                            if ($existingArchive === null) {
                                $this->archiveService->createArchive(
                                    $userCourse['userId'],
                                    $userCourse['courseId'],
                                    $archiveFormat->value
                                );
                                $createdCount++;
                            }
                        } else {
                            $createdCount++;
                        }
                    } catch (\Throwable $e) {
                        $errorCount++;
                        $this->logger->error('创建档案失败', [
                            'userId' => $userCourse['userId'],
                            'courseId' => $userCourse['courseId'],
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $progressBar->advance();
                }

                if (!$dryRun) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('创建过程中发生 %d 个错误', $errorCount));
        }

        return [
            'message' => sprintf('档案创建完成！成功创建 %d 个档案，失败 %d 个', $createdCount, $errorCount),
            'created' => $createdCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * 更新档案
     */
    private function updateArchives(
        ?string $archiveId,
        ?string $userId,
        ?string $courseId,
        int $batchSize,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $io->section('更新学习档案');

        $updatedCount = 0;
        $errorCount = 0;

        if ($archiveId !== null) {
            // 更新指定档案
            $io->text("更新档案: {$archiveId}");
            
            if (!$dryRun) {
                try {
                    $this->archiveService->updateArchive($archiveId);
                    $updatedCount = 1;
                } catch (\Throwable $e) {
                    $errorCount = 1;
                    $io->error("更新档案失败: " . $e->getMessage());
                }
            } else {
                $updatedCount = 1;
            }
        } else {
            // 批量更新档案
            $archives = $this->getArchivesToUpdate($userId, $courseId);
            
            $io->text(sprintf("找到 %d 个档案需要更新", count($archives)));

            $progressBar = $io->createProgressBar(count($archives));
            $progressBar->start();

            foreach (array_chunk($archives, $batchSize) as $batch) {
                foreach ($batch as $archive) {
                    try {
                        if (!$dryRun) {
                            $this->archiveService->updateArchive($archive->getId());
                            $updatedCount++;
                        } else {
                            $updatedCount++;
                        }
                    } catch (\Throwable $e) {
                        $errorCount++;
                        $this->logger->error('更新档案失败', [
                            'archiveId' => $archive->getId(),
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $progressBar->advance();
                }

                if (!$dryRun) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        if ($errorCount > 0) {
            $io->warning(sprintf('更新过程中发生 %d 个错误', $errorCount));
        }

        return [
            'message' => sprintf('档案更新完成！成功更新 %d 个档案，失败 %d 个', $updatedCount, $errorCount),
            'updated' => $updatedCount,
            'errors' => $errorCount,
        ];
    }

    /**
     * 验证档案完整性
     */
    private function verifyArchives(
        ?string $archiveId,
        ?string $userId,
        ?string $courseId,
        int $batchSize,
        SymfonyStyle $io
    ): array {
        $io->section('验证档案完整性');

        $verifiedCount = 0;
        $invalidCount = 0;
        $warningCount = 0;

        if ($archiveId !== null) {
            // 验证指定档案
            $io->text("验证档案: {$archiveId}");
            
            $result = $this->archiveService->verifyArchiveIntegrity($archiveId);
            if ((bool) $result['isValid']) {
                $verifiedCount = 1;
                $io->success('档案验证通过');
            } else {
                $invalidCount = 1;
                $io->error('档案验证失败: ' . implode(', ', $result['errors']));
            }

            if (!empty($result['warnings'])) {
                $warningCount = 1;
                $io->warning('档案警告: ' . implode(', ', $result['warnings']));
            }
        } else {
            // 批量验证档案
            $archives = $this->getArchivesToVerify($userId, $courseId);
            
            $io->text(sprintf("找到 %d 个档案需要验证", count($archives)));

            $progressBar = $io->createProgressBar(count($archives));
            $progressBar->start();

            foreach (array_chunk($archives, $batchSize) as $batch) {
                foreach ($batch as $archive) {
                    try {
                        $result = $this->archiveService->verifyArchiveIntegrity($archive->getId());
                        
                        if ((bool) $result['isValid']) {
                            $verifiedCount++;
                        } else {
                            $invalidCount++;
                        }

                        if (!empty($result['warnings'])) {
                            $warningCount++;
                        }
                    } catch (\Throwable $e) {
                        $invalidCount++;
                        $this->logger->error('验证档案失败', [
                            'archiveId' => $archive->getId(),
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $io->newLine(2);
        }

        return [
            'message' => sprintf(
                '档案验证完成！验证通过 %d 个，失败 %d 个，警告 %d 个',
                $verifiedCount,
                $invalidCount,
                $warningCount
            ),
            'verified' => $verifiedCount,
            'invalid' => $invalidCount,
            'warnings' => $warningCount,
        ];
    }

    /**
     * 导出档案
     */
    private function exportArchives(
        ?string $archiveId,
        string $format,
        ?string $exportPath,
        bool $dryRun,
        SymfonyStyle $io
    ): array {
        $io->section('导出档案');

        if ($archiveId === null) {
            throw new \InvalidArgumentException('导出操作需要指定档案ID');
        }

        $io->text("导出档案: {$archiveId}，格式: {$format}");

        if (!$dryRun) {
            $filePath = $this->archiveService->exportArchive($archiveId, $format);
            
            if ($exportPath !== null && $exportPath !== $filePath) {
                // 复制到指定路径
                if (!copy($filePath, $exportPath)) {
                    throw new \RuntimeException("无法复制文件到: {$exportPath}");
                }
                $filePath = $exportPath;
            }

            $io->success("档案已导出到: {$filePath}");
        } else {
            $io->success("试运行：档案将导出为 {$format} 格式");
        }

        return [
            'message' => '档案导出完成',
            'exported' => 1,
        ];
    }

    /**
     * 清理过期档案
     */
    private function cleanupArchives(int $daysBeforeExpiry, bool $dryRun, SymfonyStyle $io): array
    {
        $io->section('清理过期档案');

        $expiredArchives = $this->archiveRepository->findExpired();
        $expiringArchives = $this->archiveService->getExpiringArchives($daysBeforeExpiry);

        $io->text(sprintf("找到 %d 个已过期档案，%d 个即将过期档案", count($expiredArchives), count($expiringArchives)));

        $cleanedCount = 0;

        if (!empty($expiredArchives)) {
            $io->text('清理已过期档案...');
            
            if (!$dryRun) {
                $cleanedCount = $this->archiveService->cleanupExpiredArchives();
            } else {
                $cleanedCount = count($expiredArchives);
            }
        }

        if (!empty($expiringArchives)) {
            $io->warning(sprintf('有 %d 个档案将在 %d 天内过期，请及时处理', count($expiringArchives), $daysBeforeExpiry));
            
            foreach ($expiringArchives as $archive) {
                $daysLeft = (new \DateTimeImmutable())->diff($archive->getExpiryDate())->days;
                $io->text(sprintf(
                    '档案 %s (用户: %s, 课程: %s) 将在 %d 天后过期',
                    $archive->getId(),
                    $archive->getUserId(),
                    $archive->getCourse()->getId(),
                    $daysLeft
                ));
            }
        }

        return [
            'message' => sprintf('档案清理完成！清理了 %d 个过期档案', $cleanedCount),
            'cleaned' => $cleanedCount,
            'expiring' => count($expiringArchives),
        ];
    }

    /**
     * 获取已完成的学习会话
     */
    private function getCompletedSessions(?string $userId, ?string $courseId): array
    {
        // 简化实现，实际应该查询已完成的会话
        return $this->sessionRepository->findCompletedSessions();
    }

    /**
     * 按用户和课程分组会话
     */
    private function groupSessionsByUserAndCourse(array $sessions): array
    {
        $groups = [];
        
        foreach ($sessions as $session) {
            $key = $session->getStudent()->getUserIdentifier() . '_' . $session->getCourse()->getId();
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'userId' => $session->getStudent()->getUserIdentifier(),
                    'courseId' => $session->getCourse()->getId(),
                ];
            }
        }

        return array_values($groups);
    }

    /**
     * 获取需要更新的档案
     */
    private function getArchivesToUpdate(?string $userId, ?string $courseId): array
    {
        if ($userId !== null && $courseId !== null) {
            $archive = $this->archiveRepository->findByUserAndCourse($userId, $courseId);
            return ($archive !== null) ? [$archive] : [];
        } elseif ($userId !== null) {
            return $this->archiveRepository->findByUser($userId);
        } else {
            return $this->archiveRepository->findByStatus(ArchiveStatus::ACTIVE);
        }
    }

    /**
     * 获取需要验证的档案
     */
    private function getArchivesToVerify(?string $userId, ?string $courseId): array
    {
        return $this->getArchivesToUpdate($userId, $courseId);
    }
} 