<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;
use Tourze\TrainRecordBundle\Exception\UnsupportedActionException;

class UnsupportedActionExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new UnsupportedActionException();
        $this->assertInstanceOf(UnsupportedActionException::class, $exception);
    }

    public function testExtendsTrainRecordException(): void
    {
        $exception = new UnsupportedActionException();
        $this->assertInstanceOf(TrainRecordException::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new UnsupportedActionException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = 'Unsupported action';
        $exception = new UnsupportedActionException($message);
        $this->assertEquals($message, $exception->getMessage());
    }
}