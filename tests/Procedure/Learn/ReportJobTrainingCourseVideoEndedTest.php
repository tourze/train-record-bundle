<?php

namespace Tourze\TrainRecordBundle\Tests\Procedure\Learn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainRecordBundle\Procedure\Learn\ReportJobTrainingCourseVideoEnded;

/**
 * ReportJobTrainingCourseVideoEnded 测试
 *
 * @internal
 */
#[CoversClass(ReportJobTrainingCourseVideoEnded::class)]
#[RunTestsInSeparateProcesses]
final class ReportJobTrainingCourseVideoEndedTest extends AbstractProcedureTestCase
{
    private ReportJobTrainingCourseVideoEnded $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(ReportJobTrainingCourseVideoEnded::class);
    }

    public function testExecuteThrowsExceptionWhenUserNotAuthenticated(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户类型错误');

        $this->procedure->execute();
    }
}
