<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;

/**
 * @internal
 */
#[CoversClass(TrainRecordException::class)]
#[RunTestsInSeparateProcesses]
final class TrainRecordExceptionTest extends AbstractExceptionTestCase
{
}
