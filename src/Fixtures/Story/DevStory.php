<?php

namespace App\Fixtures\Story;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\SteamFactory;
use App\Fixtures\Factory\UserFactory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class DevStory extends Story
{
    public function __construct(private bool $mockHub, private ManagerRegistry $managerRegistry)
    {
    }

    public function build(): void
    {
        // Disable PrePersit and PreUpdate event (prevent dateAdd and dateUpdate to be all equals)
        $lifecycleCallbacksList = [];
        foreach ([Game::class, Review::class] as $entityClass) {
            $lifecycleCallbacksList[$entityClass] = $this->managerRegistry->getManager()->getClassMetadata($entityClass)->lifecycleCallbacks;
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        }

        if ($this->mockHub) {
            UserFactory::repository()->truncate();
            // Create four dummy users
            UserFactory::new()
                ->sequence(array_map(fn ($i) => ['username' => 'user' . $i, 'avatarPath' => 'https://lorempokemon.fakerapi.it/pokemon/256/' . $i], range(1, 4)))
                ->create();
            $usersId = array_map(fn (User $u) => $u->getId(), UserFactory::repository()->findAll());
        } else {
            // Fetch the users id available through the account connection
            $userRepository = $this->managerRegistry->getManager('account')->getRepository(User::class);
            $usersId = array_map(fn (User $u) => $u->getId(), $userRepository->findAll());
        }

        // Create 50 games with "0" to "number of users" reviews, and 200 steam game
        GameFactory::new()->withUsersId($usersId, false)->many(50)->create();
        SteamFactory::new()
            ->sequence(array_map(fn ($i) => ['id' => $i], range(1, 200)))
            ->create();

        // Reenable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks($lifecycleCallbacksList[$entityClass]);
        }
    }
}
