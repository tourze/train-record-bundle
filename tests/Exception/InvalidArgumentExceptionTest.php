<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
#[RunTestsInSeparateProcesses]
final class InvalidArgumentExceptionTest extends AbstractExceptionTestCase
{
}
