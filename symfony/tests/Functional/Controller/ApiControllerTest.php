<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }

    private function createUser(string $keycloakId, string $username, array $roles): User
    {
        $repository = static::getContainer()->get(UserRepository::class);
        $user = $repository->findOneByKeycloakId($keycloakId);

        if (!$user instanceof User) {
            $user = new User();
            $user->setKeycloakId($keycloakId);
        }

        $user->setUsername($username);
        $user->setRoles($roles);
        $user->setEmail($username.'@example.com');
        $user->setLastLogin(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testMeReturnsUserProfileWhenAuthenticated(): void
    {
        $user = $this->createUser('user-1', 'user1', ['ROLE_USER']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('user1', $data['username']);
        self::assertSame('user1@example.com', $data['email']);
        self::assertContains('ROLE_USER', $data['roles']);
        self::assertArrayHasKey('lastLogin', $data);
    }

    public function testMeReturns401WhenUnauthenticated(): void
    {
        $this->client->request('GET', '/api/me');

        // access_control blocks and redirects to login via the
        // AuthenticationEntryPointInterface (307 Temporary Redirect)
        self::assertResponseStatusCodeSame(Response::HTTP_TEMPORARY_REDIRECT);
        self::assertResponseRedirects('/login');
    }

    public function testProtectedReturns200ForAdmin(): void
    {
        $user = $this->createUser('admin-1', 'admin1', ['ROLE_USER', 'ROLE_ADMIN']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/protected');

        self::assertResponseIsSuccessful();
        self::assertResponseFormatSame('json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('Welcome, admin! You have access to the protected resource.', $data['message']);
        self::assertSame('admin1', $data['username']);
    }

    public function testProtectedReturns403ForUser(): void
    {
        $user = $this->createUser('user-1', 'user1', ['ROLE_USER']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/protected');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertSame('forbidden', $data['error']);
        self::assertSame('Access denied. ADMIN role is required.', $data['message']);
    }

    public function testProtectedReturns401WhenUnauthenticated(): void
    {
        $this->client->request('GET', '/api/protected');

        // access_control blocks and redirects to login
        self::assertResponseStatusCodeSame(Response::HTTP_TEMPORARY_REDIRECT);
        self::assertResponseRedirects('/login');
    }
}
