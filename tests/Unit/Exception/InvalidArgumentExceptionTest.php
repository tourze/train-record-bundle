<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\TrainRecordBundle\Exception\InvalidArgumentException;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;

class InvalidArgumentExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new InvalidArgumentException();
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testExtendsTrainRecordException(): void
    {
        $exception = new InvalidArgumentException();
        $this->assertInstanceOf(TrainRecordException::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new InvalidArgumentException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = 'Invalid argument provided';
        $exception = new InvalidArgumentException($message);
        $this->assertEquals($message, $exception->getMessage());
    }
}