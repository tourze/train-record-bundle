<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;

class TrainRecordExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new TrainRecordException();
        $this->assertInstanceOf(TrainRecordException::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new TrainRecordException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = 'Train record error';
        $exception = new TrainRecordException($message);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testCanBeInstantiatedWithMessageAndCode(): void
    {
        $message = 'Train record error';
        $code = 500;
        $exception = new TrainRecordException($message, $code);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
}