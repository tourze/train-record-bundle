<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;
use Tourze\TrainRecordBundle\Repository\LearnArchiveRepository;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;
use Tourze\TrainRecordBundle\Service\LearnArchiveService;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'train_record')]
class ArchiveOperationFactory
{
    public function __construct(
        private readonly LearnArchiveRepository $archiveRepository,
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LearnArchiveService $archiveService,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createOperation(string $action): ArchiveOperationInterface
    {
        return match ($action) {
            'create' => new CreateArchiveOperation(
                $this->archiveRepository,
                $this->sessionRepository,
                $this->archiveService,
                $this->logger,
                $this->entityManager
            ),
            'update' => new UpdateArchiveOperation(
                $this->archiveRepository,
                $this->sessionRepository,
                $this->archiveService,
                $this->logger,
                $this->entityManager
            ),
            'verify' => new VerifyArchiveOperation(
                $this->archiveRepository,
                $this->sessionRepository,
                $this->archiveService,
                $this->logger,
                $this->entityManager
            ),
            'export' => new ExportArchiveOperation(
                $this->archiveRepository,
                $this->sessionRepository,
                $this->archiveService,
                $this->logger,
                $this->entityManager
            ),
            'cleanup' => new CleanupArchiveOperation(
                $this->archiveRepository,
                $this->sessionRepository,
                $this->archiveService,
                $this->logger,
                $this->entityManager
            ),
            default => throw new UnsupportedActionException("不支持的操作类型: {$action}"),
        };
    }
}
