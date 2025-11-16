<?php

namespace App\Fixtures\Story;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\GameFactory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'default')]
final class DefaultStory extends Story
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

        // Create 20 games with "0" to "number of users" reviews
        GameFactory::new()->withUsersId($usersId)->many(20)->create();
    }
}
