<?php

namespace Tourze\TrainRecordBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tourze\TrainRecordBundle\Exception\TrainRecordException;
use Tourze\TrainRecordBundle\Exception\UnsupportedOperatingSystemException;

class UnsupportedOperatingSystemExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new UnsupportedOperatingSystemException();
        $this->assertInstanceOf(UnsupportedOperatingSystemException::class, $exception);
    }

    public function testExtendsTrainRecordException(): void
    {
        $exception = new UnsupportedOperatingSystemException();
        $this->assertInstanceOf(TrainRecordException::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new UnsupportedOperatingSystemException();
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeInstantiatedWithMessage(): void
    {
        $message = 'Unsupported operating system';
        $exception = new UnsupportedOperatingSystemException($message);
        $this->assertEquals($message, $exception->getMessage());
    }
}