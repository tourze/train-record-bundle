<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Entity\EffectiveStudyRecord;
use Tourze\TrainRecordBundle\Exception\ArgumentException;
use Tourze\TrainRecordBundle\Repository\EffectiveStudyRecordRepository;
use Tourze\TrainRecordBundle\Service\EffectiveStudyTimeService;

#[AsCommand(
    name: self::NAME,
    description: '重新计算有效学时记录'
)]
#[WithMonologChannel(channel: 'train_record')]
class EffectiveStudyTimeRecalculateCommand extends Command
{
    protected const NAME = 'train-record:effective-study-time:recalculate';

    public function __construct(
        private readonly EffectiveStudyRecordRepository $recordRepository,
        private readonly EffectiveStudyTimeService $studyTimeService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $options = $this->parseCommandOptions($input);

        $io->title('有效学时重新计算');

        if ($options['dryRun']) {
            $io->note('运行在试运行模式，不会实际更新数据');
        }

        try {
            [$processedCount, $errorCount] = $this->performRecalculation($options, $io);
            $this->displayResults($processedCount, $errorCount, $io);

            return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->handleError($e, $io);

            return Command::FAILURE;
        }
    }

    /**
     * 解析命令行选项
     * @return array{recordId: ?string, userId: ?string, date: ?string, courseId: ?string, batchSize: int, onlyInvalid: bool, dryRun: bool}
     */
    private function parseCommandOptions(InputInterface $input): array
    {
        $batchSizeOption = $input->getOption('batch-size');

        return [
            'recordId' => is_string($input->getOption('record-id')) ? $input->getOption('record-id') : null,
            'userId' => is_string($input->getOption('user-id')) ? $input->getOption('user-id') : null,
            'date' => is_string($input->getOption('date')) ? $input->getOption('date') : null,
            'courseId' => is_string($input->getOption('course-id')) ? $input->getOption('course-id') : null,
            'batchSize' => is_numeric($batchSizeOption) ? (int) $batchSizeOption : 50,
            'onlyInvalid' => (bool) $input->getOption('only-invalid'),
            'dryRun' => (bool) $input->getOption('dry-run'),
        ];
    }

    /**
     * 执行重新计算
     * @param array{recordId: ?string, userId: ?string, date: ?string, courseId: ?string, batchSize: int, onlyInvalid: bool, dryRun: bool} $options
     * @return array{int, int}
     */
    private function performRecalculation(array $options, SymfonyStyle $io): array
    {
        if (null !== $options['recordId']) {
            return $this->recalculateSingleRecord($options['recordId'], $options['dryRun'], $io);
        }

        $records = $this->getRecordsToRecalculate(
            $options['userId'],
            $options['date'],
            $options['courseId'],
            $options['onlyInvalid']
        );

        $io->text(sprintf('找到 %d 条需要重新计算的记录', count($records)));

        return $this->recalculateRecords($records, $options['batchSize'], $options['dryRun'], $io);
    }

    /**
     * 显示结果
     */
    private function displayResults(int $processedCount, int $errorCount, SymfonyStyle $io): void
    {
        if ($processedCount > 0) {
            $io->success(sprintf(
                '重新计算完成！处理了 %d 条记录，失败 %d 条',
                $processedCount,
                $errorCount
            ));
        } else {
            $io->note('没有找到需要重新计算的记录');
        }
    }

    /**
     * 处理错误
     */
    private function handleError(\Throwable $e, SymfonyStyle $io): void
    {
        $this->logger->error('学时重新计算失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $io->error('重新计算失败: ' . $e->getMessage());
    }

    /**
     * 重新计算单个记录
     * @return array{int, int}
     */
    private function recalculateSingleRecord(string $recordId, bool $dryRun, SymfonyStyle $io): array
    {
        $record = $this->recordRepository->find($recordId);
        if (null === $record) {
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
     * @return array<EffectiveStudyRecord>
     */
    private function getRecordsToRecalculate(?string $userId, ?string $date, ?string $courseId, bool $onlyInvalid): array
    {
        $criteria = [];

        if (null !== $userId) {
            $criteria['userId'] = $userId;
        }

        if (null !== $courseId) {
            $criteria['course'] = $courseId;
        }

        if ($onlyInvalid) {
            return $this->recordRepository->findBy(['status' => 'invalid']);
        }

        if (null !== $date) {
            return $this->getRecordsByDate($date, $userId);
        }

        if ([] === $criteria) {
            // 如果没有指定条件，返回最近需要重新验证的记录
            $beforeDate = new \DateTimeImmutable('-7 days');

            return $this->recordRepository->findNeedingRevalidation($beforeDate);
        }

        return $this->recordRepository->findBy($criteria, ['createTime' => 'DESC'], 1000);
    }

    /**
     * 根据日期获取记录
     * @return array<EffectiveStudyRecord>
     */
    private function getRecordsByDate(string $date, ?string $userId): array
    {
        $targetDate = new \DateTimeImmutable($date);

        if (null === $userId) {
            throw new ArgumentException('当按日期查询时必须提供用户ID');
        }

        return $this->recordRepository->findByUserAndDate($userId, $targetDate);
    }

    /**
     * 批量重新计算记录
     * @param array<EffectiveStudyRecord> $records
     * @return array{int, int}
     */
    private function recalculateRecords(array $records, int $batchSize, bool $dryRun, SymfonyStyle $io): array
    {
        $total = count($records);
        $processed = 0;
        $errors = 0;

        if (0 === $total) {
            return [0, 0];
        }

        if ($batchSize < 1) {
            throw new ArgumentException('批处理大小必须大于0');
        }

        $progressBar = $io->createProgressBar($total);
        $progressBar->start();

        foreach (array_chunk($records, $batchSize) as $batch) {
            [$batchProcessed, $batchErrors] = $this->processBatch($batch, $dryRun, $progressBar);
            $processed += $batchProcessed;
            $errors += $batchErrors;

            if (!$dryRun) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        return [$processed, $errors];
    }

    /**
     * @param array<EffectiveStudyRecord> $batch
     * @param ProgressBar $progressBar
     * @return array{int, int}
     */
    private function processBatch(array $batch, bool $dryRun, ProgressBar $progressBar): array
    {
        $processed = 0;
        $errors = 0;

        foreach ($batch as $record) {
            try {
                if (!$dryRun) {
                    $this->studyTimeService->recalculateRecord($record);
                }
                ++$processed;
            } catch (\Throwable $e) {
                ++$errors;
                $this->logger->warning('重新计算记录失败', [
                    'record_id' => $record->getId(),
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        return [$processed, $errors];
    }
}
