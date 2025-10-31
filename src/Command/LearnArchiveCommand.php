<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use Tourze\TrainRecordBundle\Command\Operation\ArchiveOperationFactory;

#[AsCommand(
    name: self::NAME,
    description: '归档完成的学习记录'
)]
#[AsCronTask(
    expression: '0 2 * * *'
)]
#[AsCronTask(
    expression: '0 1 * * *'
)]
#[AsCronTask(
    expression: '30 1 * * *'
)]
#[WithMonologChannel(channel: 'train_record')]
class LearnArchiveCommand extends Command
{
    protected const NAME = 'learn:archive';

    public function __construct(
        private readonly ArchiveOperationFactory $operationFactory,
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $config = $this->extractCommandConfig($input);

        $this->displayCommandIntro($io, $config['dryRun']);

        try {
            $operation = $this->operationFactory->createOperation($config['action']);
            $operation->validateConfig($config);

            $result = $operation->execute($config, $io);
            $message = $result['message'] ?? '操作完成';
            $io->success(is_scalar($message) ? (string) $message : '操作完成');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            return $this->handleExecutionError($e, $config['action'], $io);
        }
    }

    /**
     * @return array{
     *     userId: string|null,
     *     courseId: string|null,
     *     archiveId: string|null,
     *     action: string,
     *     format: string,
     *     exportPath: string|null,
     *     daysBeforeExpiry: int,
     *     batchSize: int,
     *     dryRun: bool
     * }
     */
    private function extractCommandConfig(InputInterface $input): array
    {
        return [
            'userId' => is_string($input->getOption('user-id')) ? $input->getOption('user-id') : null,
            'courseId' => is_string($input->getOption('course-id')) ? $input->getOption('course-id') : null,
            'archiveId' => is_string($input->getOption('archive-id')) ? $input->getOption('archive-id') : null,
            'action' => is_string($input->getOption('action')) ? $input->getOption('action') : 'list',
            'format' => is_string($input->getOption('format')) ? $input->getOption('format') : 'table',
            'exportPath' => is_string($input->getOption('export-path')) ? $input->getOption('export-path') : null,
            'daysBeforeExpiry' => is_numeric($input->getOption('days-before-expiry')) ? (int) $input->getOption('days-before-expiry') : 30,
            'batchSize' => is_numeric($input->getOption('batch-size')) ? (int) $input->getOption('batch-size') : 100,
            'dryRun' => (bool) $input->getOption('dry-run'),
        ];
    }

    private function displayCommandIntro(SymfonyStyle $io, bool $dryRun): void
    {
        $io->title('学习档案管理');

        if ($dryRun) {
            $io->note('运行在试运行模式，不会实际执行操作');
        }
    }

    private function handleExecutionError(\Throwable $e, string $action, SymfonyStyle $io): int
    {
        $this->logger->error('学习档案管理失败', [
            'action' => $action,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $io->error('档案管理失败: ' . $e->getMessage());

        return Command::FAILURE;
    }
}
