<?php

namespace Tourze\TrainRecordBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\TrainRecordBundle\DependencyInjection\TrainRecordExtension;

class TrainRecordBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new TrainRecordExtension();
    }
}
