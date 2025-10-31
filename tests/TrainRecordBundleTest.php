<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TrainRecordBundle\TrainRecordBundle;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(TrainRecordBundle::class)]
#[RunTestsInSeparateProcesses]
final class TrainRecordBundleTest extends AbstractBundleTestCase
{
}
