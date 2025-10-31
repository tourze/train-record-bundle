<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\SessionOperationException;

/**
 * @internal
 */
#[CoversClass(SessionOperationException::class)]
#[RunTestsInSeparateProcesses]
final class SessionOperationExceptionTest extends AbstractExceptionTestCase
{
}
