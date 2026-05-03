<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Lets routes marked PUBLIC_ACCESS in access_control work when the client sends
 * an expired or invalid Bearer token (browser still has localStorage token).
 */
final class PublicAwareJWTAuthenticator implements AuthenticatorInterface, AuthenticationEntryPointInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly JWTAuthenticator $inner,
        private readonly AccessMapInterface $accessMap,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $this->inner->supports($request);
    }

    public function authenticate(Request $request): Passport
    {
        return $this->inner->authenticate($request);
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return $this->inner->createToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->inner->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($this->isPublicAccessRoute($request)) {
            return null;
        }

        return $this->inner->onAuthenticationFailure($request, $exception);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->inner->start($request, $authException);
    }

    private function isPublicAccessRoute(Request $request): bool
    {
        [$attributes] = $this->accessMap->getPatterns($request);

        return \is_array($attributes)
            && \in_array(AuthenticatedVoter::PUBLIC_ACCESS, $attributes, true);
    }
}
