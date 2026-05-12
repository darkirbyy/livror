<?php

namespace App\Fixtures\Story\Home;

use App\Entity\Game;
use App\Entity\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Story;

final class HomeIndexStory extends Story
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

        // Create 20 games with "1" to "number of users" reviews
        $allUsersUuid = TestStory::getPool('all-users-uuid');
        GameFactory::new()->withUsersUuid($allUsersUuid, 1)->many(50)->create();

        // Reenable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks($lifecycleCallbacksList[$entityClass]);
        }
    }
}
