<?php

namespace Tourze\TrainRecordBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\TrainRecordBundle\Exception\UnsupportedOperatingSystemException;
use Twig\Environment;

#[AsCommand(name: self::NAME, description: '学成学时证明')]
class GenerateCourseRecordCommand extends Command
{
    protected const NAME = 'job-training:generate-course-record';
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 不同环境用不同的执行文件
        $projectRoot = $this->kernel->getProjectDir();
        if ((bool) mb_stristr(PHP_OS, 'DAR')) {
            $binFile = $projectRoot . '/vendor/suhanyu/wkhtmltopdf-amd64-mac-os/bin/wkhtmltopdf';
        } elseif ((bool) mb_stristr(PHP_OS, 'WIN')) {
            $binFile = $projectRoot . '/vendor/wemersonjanuario/wkhtmltopdf-windows/bin/64bit/wkhtmltopdf.exe';
        } elseif ((bool) mb_stristr(PHP_OS, 'LINUX')) {
            $binFile = $projectRoot . '/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64';
        } else {
            throw new UnsupportedOperatingSystemException('未知操作系统');
        }

        $outputFile = __DIR__ . '/output.pdf';
        @unlink($outputFile);

        $snappy = new \Knp\Snappy\Pdf($binFile);

        $snappy->setOption('header-left', '蔡大头 44092199104203415');
        // $snappy->setOption('header-html', __DIR__ . '/header.html');
        $snappy->setOption('header-spacing', '3');

        $snappy->setOption('footer-spacing', '3');
        $snappy->setOption('footer-center', '[page] / [toPage]');

        $html = $this->twig->render('@JobTraining/report/file.html.twig');
        $snappy->generateFromHtml($html, $outputFile);

        return Command::SUCCESS;
    }
}
