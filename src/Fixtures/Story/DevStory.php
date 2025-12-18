<?php

namespace App\Fixtures\Story;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\SteamFactory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class DevStory extends Story
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function build(): void
    {
        // Disable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        }

        // Fetch the users id available through the account connection
        $userRepository = $this->managerRegistry->getManager('account')->getRepository(User::class);
        $usersId = array_map(fn (User $u) => $u->getId(), $userRepository->findAll());

        // Create 20 games with "0" to "number of users" reviews, and 200 steam game
        GameFactory::new()->withUsersId($usersId, false)->many(20)->create();
        SteamFactory::new()
            ->sequence(array_map(fn ($i) => ['id' => $i], range(1, 200)))
            ->create();
    }
}
