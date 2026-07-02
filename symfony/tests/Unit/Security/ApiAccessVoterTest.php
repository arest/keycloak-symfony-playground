<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Core\Security\Voter\ApiAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ApiAccessVoterTest extends TestCase
{
    private ApiAccessVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ApiAccessVoter();
    }

    public function testAbstainOnUnsupportedAttribute(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $result = $this->voter->vote($token, 'me', ['UNSUPPORTED_ATTRIBUTE']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainOnUnsupportedSubject(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $result = $this->voter->vote($token, 'unknown', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDenyAnonymousUserForMe(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, 'me', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDenyAnonymousUserForProtected(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, 'protected', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAllowUserForMe(): void
    {
        $user = new User();
        $user->setKeycloakId('user-1');
        $user->setUsername('user1');
        $user->setRoles(['ROLE_USER']);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, 'me', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDenyUserForProtected(): void
    {
        $user = new User();
        $user->setKeycloakId('user-1');
        $user->setUsername('user1');
        $user->setRoles(['ROLE_USER']);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, 'protected', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAllowAdminForMe(): void
    {
        $user = new User();
        $user->setKeycloakId('admin-1');
        $user->setUsername('admin1');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, 'me', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAllowAdminForProtected(): void
    {
        $user = new User();
        $user->setKeycloakId('admin-1');
        $user->setUsername('admin1');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, 'protected', [ApiAccessVoter::ATTR_ROLE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }
}
