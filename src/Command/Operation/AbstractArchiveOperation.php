<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Tourze\TrainRecordBundle\Entity\LearnSession;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnArchiveService;

#[WithMonologChannel(channel: 'train_record')]
abstract class AbstractArchiveOperation implements ArchiveOperationInterface
{
    public function __construct(
        protected readonly LearnArchiveRepository $archiveRepository,
        protected readonly LearnSessionRepository $sessionRepository,
        protected readonly LearnArchiveService $archiveService,
        protected readonly LoggerInterface $logger,
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 分批处理并定期刷新实体管理器
     * @param array<mixed> $items
     * @param callable(array<mixed>): array<string, int> $processor
     * @return array<string, int>
     */
    protected function processBatchWithFlush(array $items, int $batchSize, bool $dryRun, callable $processor): array
    {
        $results = [];

        foreach (array_chunk($items, max(1, $batchSize)) as $batch) {
            $batchResult = $processor($batch);

            foreach ($batchResult as $key => $value) {
                $results[$key] = ($results[$key] ?? 0) + $value;
            }

            if (!$dryRun) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        return $results;
    }

    /**
     * 创建进度条并处理项目
     * @param array<mixed> $items
     * @param callable(mixed): array<string, int> $processor
     * @return array<string, int>
     */
    protected function processWithProgress(array $items, callable $processor, ProgressBar $progressBar): array
    {
        $results = [];

        foreach ($items as $item) {
            $itemResult = $processor($item);

            foreach ($itemResult as $key => $value) {
                $results[$key] = ($results[$key] ?? 0) + $value;
            }

            $progressBar->advance();
        }

        return $results;
    }

    /**
     * 记录操作错误
     * @param array<string, mixed> $context
     */
    protected function logError(string $operation, array $context, \Throwable $e): void
    {
        $this->logger->error(sprintf('%s失败', $operation), array_merge($context, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]));
    }

    /**
     * 获取已完成的学习会话
     * @return array<LearnSession>
     */
    protected function getCompletedSessions(?string $userId = null, ?string $courseId = null): array
    {
        return $this->sessionRepository->findCompletedSessions();
    }

    /**
     * 按用户和课程分组会话
     * @param array<LearnSession> $sessions
     * @return array<array<string, mixed>>
     */
    protected function groupSessionsByUserAndCourse(array $sessions): array
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
}
