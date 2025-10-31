<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Command\Operation;

use Symfony\Component\Console\Style\SymfonyStyle;

interface ArchiveOperationInterface
{
    /**
     * 执行操作
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function execute(array $config, SymfonyStyle $io): array;

    /**
     * 验证配置是否有效
     *
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): void;
}
