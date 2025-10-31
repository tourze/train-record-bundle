<?php

namespace Tourze\TrainRecordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\TrainRecordBundle\Exception\UnsupportedOperatingSystemException;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(UnsupportedOperatingSystemException::class)]
#[RunTestsInSeparateProcesses]
final class UnsupportedOperatingSystemExceptionTest extends AbstractExceptionTestCase
{
}
