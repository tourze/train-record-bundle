<?php

namespace Tourze\TrainRecordBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\TrainRecordBundle\Repository\LearnSessionRepository;

#[AsCommand(
    name: self::NAME,
    description: '清理无效的学习会话（3分钟内未更新的活跃会话）',
)]
class LearnSessionCleanupCommand extends Command
{
    protected const NAME = 'train:learn-session:cleanup';
    private const INACTIVE_THRESHOLD_MINUTES = 3;

    public function __construct(
        private readonly LearnSessionRepository $sessionRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '模拟运行，只显示将被清理的会话，不实际执行'
            )
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_REQUIRED,
                '无效阈值（分钟）',
                self::INACTIVE_THRESHOLD_MINUTES
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $threshold = (int) $input->getOption('threshold');

        $io->title('清理无效的学习会话');
        
        if ((bool) $dryRun) {
            $io->note('模拟运行模式：不会实际修改数据');
        }

        $io->info(sprintf('查找 %d 分钟内未更新的活跃学习会话...', $threshold));

        try {
            // 查找超时的活跃会话
            $inactiveSessions = $this->sessionRepository->findInactiveActiveSessions($threshold);
            
            if ((bool) empty($inactiveSessions)) {
                $io->success('没有发现无效的学习会话');
                return Command::SUCCESS;
            }

            $io->section(sprintf('发现 %d 个无效的学习会话', count($inactiveSessions)));

            $table = [];
            foreach ($inactiveSessions as $session) {
                $table[] = [
                    $session->getId(),
                    $session->getStudent()->getNickName() ?? $session->getStudent()->getId(),
                    $session->getCourse()->getTitle(),
                    $session->getLesson()->getTitle(),
                    $session->getLastLearnTime()->format('Y-m-d H:i:s'),
                    $session->getCurrentDuration(),
                ];
            }

            $io->table(
                ['会话ID', '学员', '课程', '课时', '最后更新时间', '当前进度'],
                $table
            );

            if (!$dryRun) {
                $io->section('开始清理无效会话');
                
                $cleanedCount = 0;
                foreach ($inactiveSessions as $session) {
                    try {
                        // 将活跃状态设为 false
                        $session->setActive(false);
                        $this->sessionRepository->save($session, false);
                        
                        $this->logger->info('清理无效学习会话', [
                            'session_id' => $session->getId(),
                            'student_id' => $session->getStudent()->getId(),
                            'lesson_id' => $session->getLesson()->getId(),
                            'last_update' => $session->getLastLearnTime()->format('Y-m-d H:i:s'),
                        ]);
                        
                        $cleanedCount++;
                    } catch (\Exception $e) {
                        $io->error(sprintf('清理会话 %s 失败: %s', $session->getId(), $e->getMessage()));
                        $this->logger->error('清理学习会话失败', [
                            'session_id' => $session->getId(),
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // 批量提交
                $this->sessionRepository->flush();
                
                $io->success(sprintf('成功清理 %d 个无效的学习会话', $cleanedCount));
            } else {
                $io->warning(sprintf('模拟运行：将清理 %d 个无效的学习会话', count($inactiveSessions)));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('清理过程中发生错误: ' . $e->getMessage());
            $this->logger->error('学习会话清理命令执行失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }
}
