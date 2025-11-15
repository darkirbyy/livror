<?php

namespace App\Fixtures;

use App\Fixtures\Story\DefaultStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultStory::load();
    }
}
