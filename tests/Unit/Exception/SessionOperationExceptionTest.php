<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\TrainRecordBundle\Exception\SessionOperationException;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;

class SessionOperationExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new SessionOperationException();
        $this->assertInstanceOf(SessionOperationException::class, $exception);
    }

    public function testExtendsTrainRecordException(): void
    {
        $exception = new SessionOperationException();
        $this->assertInstanceOf(TrainRecordException::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new SessionOperationException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = 'Session operation failed';
        $exception = new SessionOperationException($message);
        $this->assertEquals($message, $exception->getMessage());
    }
}