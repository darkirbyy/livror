<?php

namespace App\Fixtures\Story\Command;

use App\Entity\Account\User;
use App\Entity\Game;
use App\Entity\Review;
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

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $otherUsersUuid = TestStory::getPool('other-users-uuid');

        // Create 5 games NOT reviewed by user 1 (=connected)
        GameFactory::new()->withUsersUuid($otherUsersUuid, true)->many(5)->create();

        // Create 10 games only reviewed by user 1 (=connected)
        GameFactory::new()->withUsersUuid([$connectedUserUuid], true)->many(10)->create();

        // Reenable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks($lifecycleCallbacksList[$entityClass]);
        }
    }
}
