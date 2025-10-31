<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;

/**
 * @internal
 */
#[CoversClass(UnsupportedActionException::class)]
#[RunTestsInSeparateProcesses]
final class UnsupportedActionExceptionTest extends AbstractExceptionTestCase
{
}
