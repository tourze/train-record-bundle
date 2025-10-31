<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\ArgumentException;

/**
 * @internal
 */
#[CoversClass(ArgumentException::class)]
#[RunTestsInSeparateProcesses]
final class ArgumentExceptionTest extends AbstractExceptionTestCase
{
}
