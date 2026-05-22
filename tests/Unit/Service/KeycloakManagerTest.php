<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\User;
use App\Security\KeycloakClient;
use App\Service\KeycloakManager;
use Mainick\KeycloakClientBundle\Representation\ClientRepresentation;
use Mainick\KeycloakClientBundle\Representation\Collection\ClientCollection;
use Mainick\KeycloakClientBundle\Representation\Collection\UserCollection;
use Mainick\KeycloakClientBundle\Representation\Type\Map;
use Mainick\KeycloakClientBundle\Representation\UserRepresentation;
use Mainick\KeycloakClientBundle\Service\ClientsService;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;

#[PU\AllowMockObjectsWithoutExpectations]
final class KeycloakManagerTest extends TestCase
{
    private static $clientId = 'livror-test';
    private KeycloakClient $keycloakClient;
    private CacheInterface $cache;

    private KeycloakManager $keycloakManager;

    public function setUp(): void
    {
        $this->keycloakClient = $this->createMock(KeycloakClient::class);
        $this->cache = new ArrayAdapter();

        $this->keycloakManager = new KeycloakManager(self::$clientId, $this->keycloakClient, $this->cache);
    }

    #[PU\Test]
    public function cacheFull(): void
    {
        $user1 = new User(Uuid::v4(), 'user1', '');
        $user2 = new User(Uuid::v4(), 'user2', '/url/to/picture');
        $cacheContent = [$user1->uuid->toString() => $user1, $user2->uuid->toString() => $user2];

        $this->cache->get('users-authorized', fn() => $cacheContent);
        $this->keycloakClient->expects($this->never())->method('clients');

        $usersAuthorized = $this->keycloakManager->getUsersAuthorized();
        $this->assertCount(2, $usersAuthorized);
        foreach (range(1, 2) as $index) {
            $varUser = 'user' . $index;
            $this->assertArrayHasKey($$varUser->uuid->toString(), $usersAuthorized);
            $this->assertInstanceOf(User::class, $usersAuthorized[$$varUser->uuid->toString()]);
            $this->assertEquals($$varUser, $usersAuthorized[$$varUser->uuid->toString()]);
        }
    }

    #[PU\Test]
    public function wrongClientNumber(): void
    {
        $clientCollection = new ClientCollection();

        $clients = $this->createMock(ClientsService::class);
        $clients->expects($this->once())->method('all')->willReturn($clientCollection);

        $this->keycloakClient->expects($this->once())->method('clients')->willReturn($clients);

        $this->expectException(\RuntimeException::class);
        $this->keycloakManager->getUsersAuthorized();
    }

    #[PU\Test]
    public function noUserAuthorized(): void
    {
        $clientCollection = new ClientCollection([new ClientRepresentation('1')]);
        $userCollection = new UserCollection([]);

        $clients = $this->createMock(ClientsService::class);
        $clients->expects($this->once())->method('all')->willReturn($clientCollection);
        $clients->expects($this->once())->method('getRoleUsers')->willReturn($userCollection);

        $this->keycloakClient->expects($this->exactly(2))->method('clients')->willReturn($clients);

        $this->expectException(\RuntimeException::class);
        $this->keycloakManager->getUsersAuthorized();
    }

    #[PU\Test]
    public function validUserAuthorized(): void
    {
        $user1 = new User(Uuid::v4(), 'user1', '');
        $user2 = new User(Uuid::v4(), 'user2', '/url/to/picture');

        $clientCollection = new ClientCollection([new ClientRepresentation('1')]);
        $userCollection = new UserCollection([
            new UserRepresentation($user1->uuid->toString(), $user1->username, attributes: new Map(['picture' => [$user1->avatarPath]])),
            new UserRepresentation($user2->uuid->toString(), $user2->username, attributes: new Map(['picture' => [$user2->avatarPath]])),
        ]);

        $clients = $this->createMock(ClientsService::class);
        $clients->expects($this->once())->method('all')->willReturn($clientCollection);
        $clients->expects($this->once())->method('getRoleUsers')->willReturn($userCollection);

        $this->keycloakClient->expects($this->exactly(2))->method('clients')->willReturn($clients);

        $usersAuthorized = $this->keycloakManager->getUsersAuthorized();
        $this->assertCount(2, $usersAuthorized);
        foreach (range(1, 2) as $index) {
            $varUser = 'user' . $index;
            $this->assertArrayHasKey($$varUser->uuid->toString(), $usersAuthorized);
            $this->assertInstanceOf(User::class, $usersAuthorized[$$varUser->uuid->toString()]);
            $this->assertEquals($$varUser, $usersAuthorized[$$varUser->uuid->toString()]);
        }
    }
}
