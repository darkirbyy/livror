<?php

namespace App\Fixtures\Story\Command;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class DiscordNotifyStory extends Story
{
    public function __construct(private ManagerRegistry $managerRegistry) {}

    public function build(): void
    {
        // Disable PrePersit and PreUpdate event (prevent dateAdd and dateUpdate to be all equals)
        $lifecycleCallbacksList = [];
        foreach ([Game::class, Review::class] as $entityClass) {
            $lifecycleCallbacksList[$entityClass] = $this->managerRegistry->getManager()->getClassMetadata($entityClass)->lifecycleCallbacks;
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        }

        $user1 = TestStory::get('connected-user');
        $usersButUser1Id = array_map(fn(User $u) => $u->getId(), TestStory::getPool('other-users'));

        // Create 5 games NOT reviewed by user 1
        GameFactory::new()->withUsersId($usersButUser1Id, true)->many(5)->create();

        // Create 10 games only reviewed by user 1
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->many(10)
            ->create();

        // Reenable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks($lifecycleCallbacksList[$entityClass]);
        }
    }
}
