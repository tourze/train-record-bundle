<?php

namespace Tourze\TrainRecordBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeService;

#[AsCommand(
    name: 'effective-study-time:recalculate',
    description: '重新计算有效学时记录'
)]
class EffectiveStudyTimeRecalculateCommand extends Command
{
    protected const NAME = 'effective-study-time:recalculate';
    public function __construct(
                private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly EffectiveStudyTimeService $studyTimeService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'record-id',
                'r',
                InputOption::VALUE_OPTIONAL,
                '指定要重新计算的记录ID'
            )
            ->addOption(
                'user-id',
                'u',
                InputOption::VALUE_OPTIONAL,
                '指定要重新计算的用户ID'
            )
            ->addOption(
                'date',
                'd',
                InputOption::VALUE_OPTIONAL,
                '指定要重新计算的日期 (Y-m-d)'
            )
            ->addOption(
                'course-id',
                'c',
                InputOption::VALUE_OPTIONAL,
                '指定要重新计算的课程ID'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '批处理大小',
                50
            )
            ->addOption(
                'only-invalid',
                null,
                InputOption::VALUE_NONE,
                '只重新计算无效状态的记录'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '试运行模式，不实际更新数据'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recordId = $input->getOption('record-id');
        $userId = $input->getOption('user-id');
        $date = $input->getOption('date');
        $courseId = $input->getOption('course-id');
        $batchSize = (int) $input->getOption('batch-size');
        $onlyInvalid = (bool) $input->getOption('only-invalid');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('有效学时重新计算');

        if ($dryRun === true) {
            $io->note('运行在试运行模式，不会实际更新数据');
        }

        try {
            $processedCount = 0;
            $errorCount = 0;

            if ($recordId !== null) {
                // 重新计算单个记录
                [$processed, $errors] = $this->recalculateSingleRecord($recordId, $dryRun, $io);
                $processedCount += $processed;
                $errorCount += $errors;
            } else {
                // 获取要重新计算的记录
                $records = $this->getRecordsToRecalculate($userId, $date, $courseId, $onlyInvalid);
                
                $io->text(sprintf('找到 %d 条需要重新计算的记录', count($records)));
                
                [$processed, $errors] = $this->recalculateRecords($records, $batchSize, $dryRun, $io);
                $processedCount += $processed;
                $errorCount += $errors;
            }

            if ($processedCount > 0) {
                $io->success(sprintf(
                    '重新计算完成！处理了 %d 条记录，失败 %d 条',
                    $processedCount,
                    $errorCount
                ));
            } else {
                $io->note('没有找到需要重新计算的记录');
            }

            return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->logger->error('学时重新计算失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $io->error('重新计算失败: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 重新计算单个记录
     */
    private function recalculateSingleRecord(string $recordId, bool $dryRun, SymfonyStyle $io): array
    {
        $record = $this->recordRepository->find($recordId);
        if ($record === null) {
            $io->error("记录 {$recordId} 不存在");
            return [0, 1];
        }

        $io->text("重新计算记录: {$recordId}");

        if (!$dryRun) {
            try {
                $originalStatus = $record->getStatus();
                $originalEffectiveTime = $record->getEffectiveDuration();
                
                $this->studyTimeService->recalculateRecord($record);
                
                $io->text(sprintf(
                    '状态: %s -> %s, 有效时长: %.1f -> %.1f 分钟',
                    $originalStatus->getLabel(),
                    $record->getStatus()->getLabel(),
                    $originalEffectiveTime / 60,
                    $record->getEffectiveDuration() / 60
                ));
                
                return [1, 0];
            } catch (\Throwable $e) {
                $io->error("重新计算记录 {$recordId} 失败: " . $e->getMessage());
                return [0, 1];
            }
        }

        return [1, 0];
    }

    /**
     * 获取要重新计算的记录
     */
    private function getRecordsToRecalculate(?string $userId, ?string $date, ?string $courseId, bool $onlyInvalid): array
    {
        $criteria = [];
        
        if ($userId !== null) {
            $criteria['userId'] = $userId;
        }
        
        if ($courseId !== null) {
            $criteria['course'] = $courseId;
        }
        
        if ($onlyInvalid) {
            return $this->recordRepository->findBy(['status' => 'invalid']);
        }
        
        if ($date !== null) {
            $targetDate = new \DateTimeImmutable($date);
            return $this->recordRepository->findByUserAndDate($userId, $targetDate);
        }
        
        if (empty($criteria)) {
            // 如果没有指定条件，返回最近需要重新验证的记录
            $beforeDate = new \DateTimeImmutable('-7 days');
            return $this->recordRepository->findNeedingRevalidation($beforeDate);
        }
        
        return $this->recordRepository->findBy($criteria, ['createTime' => 'DESC'], 1000);
    }

    /**
     * 批量重新计算记录
     */
    private function recalculateRecords(array $records, int $batchSize, bool $dryRun, SymfonyStyle $io): array
    {
        $total = count($records);
        $processed = 0;
        $errors = 0;

        if ($total === 0) {
            return [0, 0];
        }

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($records, $batchSize) as $batch) {
            foreach ($batch as $record) {
                try {
                    if (!$dryRun) {
                        $this->studyTimeService->recalculateRecord($record);
                    }
                    
                    $processed++;
                } catch (\Throwable $e) {
                    $errors++;
                    
                    $this->logger->warning('重新计算记录失败', [
                        'record_id' => $record->getId(),
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

        return [$processed, $errors];
    }
} 