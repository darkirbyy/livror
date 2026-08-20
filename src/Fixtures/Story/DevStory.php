<?php

namespace App\Fixtures\Story;

use App\Entity\Game;
use App\Entity\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\SteamFactory;
use App\Service\KeycloakManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Story;

final class DevStory extends Story
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private ManagerRegistry $managerRegistry,
        private Filesystem $filesystem,
        private KeycloakManagerInterface $keycloakManager,
    ) {}

    public function build(): void
    {
        // Remove all uploaded files
        $vichMappings = $this->parameterBag->get('vich_uploader.mappings');
        $this->filesystem->remove($vichMappings['attachments']['upload_destination']);

        // Disable PrePersit and PreUpdate event (prevent dateAdd and dateUpdate to be all equals)
        $lifecycleCallbacksList = [];
        foreach ([Game::class, Review::class] as $entityClass) {
            $lifecycleCallbacksList[$entityClass] = $this->managerRegistry->getManager()->getClassMetadata($entityClass)->lifecycleCallbacks;
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks([]);
        }

        // Fetch the users uuid through the current provider
        $usersUuid = array_column($this->keycloakManager->getUsersAuthorized(), 'uuid');

        // Create 50 games with "0" to "number of users" reviews, and 200 steam game
        GameFactory::new()->withUsersUuid($usersUuid, false, 'random')->many(50)->create();
        SteamFactory::new()->sequence(array_map(fn($i) => ['id' => $i], range(1, 200)))->create();

        // Reenable PrePersit and PreUpdate event
        foreach ([Game::class, Review::class] as $entityClass) {
            $this->managerRegistry->getManager()->getClassMetadata($entityClass)->setLifecycleCallbacks($lifecycleCallbacksList[$entityClass]);
        }
    }
}
