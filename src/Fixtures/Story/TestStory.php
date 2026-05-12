<?php

namespace App\Fixtures\Story;

use App\Dto\User;
use App\Service\KeycloakManagerInterface;
use Zenstruck\Foundry\Story;

final class TestStory extends Story
{
    public function __construct(private KeycloakManagerInterface $keycloakManager) {}

    public function build(): void
    {
        $users = $this->keycloakManager->getUsersAuthorized();
        $allUsersUuid = array_column($users, 'uuid');
        $connectedUserUuid = array_find($users, fn(User $u) => str_ends_with($u->uuid->toString(), '1'))->uuid;
        $otherUsersUuid =  array_column(array_filter($users, fn(User $u) => !str_ends_with($u->uuid->toString(), '1')), 'uuid');;
        $this->addToPool('all-users-uuid', $allUsersUuid);
        $this->addState('connected-user-uuid', $connectedUserUuid);
        $this->addToPool('other-users-uuid', $otherUsersUuid);
    }
}
