<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\RuntimeException;

/**
 * @internal
 */
#[CoversClass(RuntimeException::class)]
#[RunTestsInSeparateProcesses]
final class RuntimeExceptionTest extends AbstractExceptionTestCase
{
}
