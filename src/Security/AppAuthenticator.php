<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\GiftAmountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $redirection;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var GiftAmountRepository
     */
    private $giftAmountRepository;

    public function __construct(EntityManagerInterface $entityManager, GiftAmountRepository $giftAmountRepository, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, SessionInterface $session, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->session = $session;
        $this->giftAmountRepository = $giftAmountRepository;
    }

    public function supports(Request $request)
    {
        if ('event_security_subscription_step2' === $request->attributes->get('_route') && $request->get('_csrf_token') && $request->isMethod('POST')) {
            $this->redirection = 'event_security_subscription_step2';
            return true;
        }
        if ('front_home_index' === $request->attributes->get('_route') && $request->get('_csrf_token') && $request->isMethod('POST')) {
            $this->redirection = 'front_home_index';
            return true;
        }
        if ('front_gift_connection' === $request->attributes->get('_route') && $request->get('_csrf_token_auth') && $request->isMethod('POST')) {
            $this->redirection = 'front_gift_connection';
            return true;
        }
        return 'app_front_login' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'mail' => $request->request->get('mail'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token') ? $request->request->get('_csrf_token') : $request->request->get('_csrf_token_auth'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['mail']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['mail']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Adresse mail non reconnue.');
        }

        if (in_array('ROLE_ADMIN',$user->getRoles())) {
            $this->redirection = 'admin_dashboard';
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            if ($this->session->get('giftCode')) {
                $user->setUmberGiftCard($this->session->get('giftCode'));

                $gift = $this->giftAmountRepository->findOneBy(['code' => $this->session->get('giftCode')]);
                $this->session->clear();
                if ($gift && !$gift->getEvent()) {
                    $user->setMoneyGift($gift->getAmount());
                }
                $this->entityManager->flush();
            }
            return true;
        }
        return false;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($this->redirection === 'admin_dashboard' && in_array('ROLE_ADMIN',$token->getUser()->getRoles())) {
            return new RedirectResponse('/admin/dashboard');
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        if ($this->redirection === 'event_security_subscription_step2') {
            return new RedirectResponse('/deposer-atelier/compte');
        }

        if ($this->redirection === 'front_gift_connection') {
            return new RedirectResponse('/carte-cadeaux/connexion-inscription');
        }

        return new RedirectResponse('/');
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate('app_front_login');
    }
}
