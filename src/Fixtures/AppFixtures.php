<?php

namespace App\Fixtures;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Story\DefaultStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Disable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $manager->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        }

        // Load the default story
        DefaultStory::load();
    }
}
