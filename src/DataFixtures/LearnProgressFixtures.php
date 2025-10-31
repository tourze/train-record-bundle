<?php

declare(strict_types=1);

namespace Tourze\TrainRecordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LearnProgressFixtures extends Fixture
{
    public const LEARN_PROGRESS_1 = 'learn_progress_1';
    public const LEARN_PROGRESS_2 = 'learn_progress_2';
    public const LEARN_PROGRESS_3 = 'learn_progress_3';

    public function load(ObjectManager $manager): void
    {
        // Skip fixture loading for LearnProgress as it requires proper Course and Lesson entities
        // which are not available in this bundle's context.
        // Tests should create their own LearnProgress entities with mocked dependencies.
    }
}
