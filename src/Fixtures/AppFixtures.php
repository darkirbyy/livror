<?php

namespace App\Fixtures;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Story\DefaultStory;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(private UserRepository $userRepo)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // todo : in the story or the fixtures ?
        // // Disable PrePersit and PreUpdate event
        // foreach ([Game::class, Review::class] as $entityClass) {
        //     $manager->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        // }

        // // Fetch the users id available through the account connection
        // $allUsersId = array_map(fn(User $u) => $u->getId(), $this->userRepo->findAll());

        // Load the default story
        DefaultStory::load();
    }
}
