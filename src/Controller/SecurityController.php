<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\WorkshopDate;
use App\Form\EventFormType;
use App\Form\ForgotPasswordType;
use App\Form\UserType;
use App\Repository\EventRepository;
use App\Repository\GiftAmountRepository;
use App\Repository\UserRepository;
use App\Services\File\UploadFile;
use App\Services\Mail\SendMail;
use App\Services\Payment\StripeClient;
use App\Services\Slug;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\NotBlank;

class SecurityController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(EntityManagerInterface $em, SessionInterface $session)
    {
        $this->em = $em;
        $this->session = $session;
    }

    /**
     * @Route("/deposer-atelier/ajouter-dates", name="front_subscription_add_dates")
     */
    public function addDateCalendar(Request $request, SessionInterface $session)
    {
        if ($request->isMethod('POST')) {
            if ($request->get('start') && $request->get('end') && $request->get('datetoselect')) {

                $startAt = $request->get('datetoselect').' '.$request->get('start');
                $endAt = $request->get('datetoselect').' '.$request->get('end');

                $session->set($request->get('datetoselect'), $startAt.'--'.$endAt);

                return new JsonResponse([
                    'message' => 'ok',
                    'hourStart' => $request->get('start'),
                    'hourEnd' => $request->get('end'),
                    'dateSelected' => $request->get('datetoselect'),
                ]);
            }
        }
    }

    /**
     * @Route("/deposer-atelier/removedate", name="front_subscription_remove_dates")
     */
    public function removeDate(Request $request, SessionInterface $session)
    {
        $session->clear();

        return new JsonResponse([
            'message' => 'ok'
        ]);
    }

    /**
     * @Route("/deposer-atelier/atelier", name="event_security_subscription_step1")
     */
    public function subscriptionStep1(Request $request, SessionInterface $session)
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event, ['enterpriseField' => false, 'csrf_protection' => false])->handleRequest($request);

        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('token');

            if ($this->isCsrfTokenValid('delete-item', $submittedToken)) {
                $this->addFlash('danger', 'Jeton CRF non valide');
                return $this->redirectToRoute('event_security_subscription_step1');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($event->getPhotos() as $photo) {

                $imageName = UploadFile::uploadImageEvent($photo->getUrl());
                $photo->setUrl($imageName);
                $photo->setEvent($event);

                $this->em->persist($photo);
            }

            foreach ($session->all() as $dates) {
                $date = explode('--', $dates);

                if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/', $date[0])) {
                    $dateStart = new \DateTime($date[0]);
                    $dateEnd = new \DateTime($date[1]);

                    $interval = $dateStart->diff($dateEnd);
                    $minutes = $interval->i > 0 ? $interval->i : '00';
                    $duration = $interval->h.'h'.$minutes.'min';

                    $workshopDate = new WorkshopDate();
                    $workshopDate->setStartAt($dateStart);
                    $workshopDate->setEndAt($dateEnd);
                    $workshopDate->setDuration($duration);
                    $workshopDate->setEvent($event);
                    $event->addWorkshopDate($workshopDate);

                    $this->em->persist($workshopDate);
                }
            }

            $slug = Slug::slugify($event->getTitle());
            $event->setSlug($slug);

            $this->em->persist($event);
            $this->em->flush();

            $session->clear();

            $this->session->set('step1', true);
            $this->session->set('eventId', $event->getId());
            return $this->redirectToRoute('event_security_subscription_step2');
        }

        return $this->render('security/subscription_step1.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/deposer-atelier/compte", name="event_security_subscription_step2")
     */
    public function subscriptionStep2(Request $request, AuthenticationUtils $authenticationUtils, EventRepository $eventRepository, UserPasswordEncoderInterface $encoder)
    {
        if (!$this->session->get('step1') || !$this->session->get('eventId')) {
            return $this->redirectToRoute('event_security_subscription_step1');
        }


        $event = $eventRepository->find($this->session->get('eventId'));
        if (!$event) {
            return $this->redirectToRoute('event_security_subscription_step1');
        }

        if ($this->getUser() && $this->getUser()->getIsEnterprise()) {

            $this->session->set('isEnterprise', true);
            $event->setUser($this->getUser());
            $this->em->flush();

            return $this->redirectToRoute('event_security_subscription_success');
        }

        if ($this->getUser()) {
            $event->setUser($this->getUser());
            $this->em->flush();

            $this->session->set('step2', true);
            $this->session->set('userId', $this->getUser()->getId());
            return $this->redirectToRoute('event_security_subscription_success');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['validation_groups' => 'isEnterprise', 'fieldAdress' => true])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $event->setUser($user);
            $user->setIsEnterprise(true);
            $user->setRoles(['ROLE_ENTERPRISE']);
            $passwordEncoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($passwordEncoded);

            $this->em->persist($user);
            $this->em->flush();

            $this->session->set('step2', true);
            $this->session->set('userId', $user->getId());
            $this->session->remove('step1');

            return $this->redirectToRoute('event_security_subscription_success');
        }

        return $this->render('security/subscription_step2.html.twig', [
            'form' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/deposer-atelier/paiement", name="event_security_subscription_step_payment")
     */
    public function subscriptionStep3(SessionInterface $session, EventRepository $eventRepository, UserRepository $userRepository, Request $request, StripeClient $stripeClient)
    {
        if (!$session->get('step2') || !$session->get('userId') || !$session->get('eventId')) {
            return $this->redirectToRoute('event_security_subscription_step1');
        }

        $event = $eventRepository->findOneBy(['id' => $session->get('eventId')]);

        $form = $this->get('form.factory')
            ->createNamedBuilder('payment-form')
            ->add('token', HiddenType::class, [
                'constraints' => [new NotBlank(['message' => 'Veuillez renseigner vos informations de paiement'])],
            ])
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $user = $userRepository->findOneBy(['id' => $session->get('userId')]);
                $isPaid = $stripeClient->createPremiumCharge($user, $event, $request->get('payment-form')['token']);

                if (!$isPaid) {
                    $this->addFlash('danger', 'Erreur lors du paiement, vous pouvez tenter une nouvelle fois.');
                    return $this->redirectToRoute('event_security_subscription_step3');
                }

                $session->set('payment', 'success');
                return $this->redirectToRoute('event_security_subscription_success');
            }
        }

        return $this->render('security/subscription_step3.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'stripe_public_key' => getenv('STRIPE_PUBLIC_KEY')
        ]);
    }

    /**
     * @Route("/deposer-atelier/succes", name="event_security_subscription_success")
     */
    public function subscriptionSuccess(UserRepository $userRepository)
    {
        if ((!$this->session->get('step2') || !$this->session->get('userId')) && !$this->session->get('isEnterprise')) {
            return $this->redirectToRoute('event_security_subscription_step1');
        }

        $user = $userRepository->findOneBy(['id' => $this->session->get('userId')]);
        $this->session->clear();

        return $this->render('security/subscription_success.html.twig', [
            'user' => $user ? $user : $this->getUser()
        ]);
    }

    /**
     * @Route("/connexion", name="app_front_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/connexion.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/inscription", name="front_client_subscription")
     */
    public function clientSubscription(Request $request, UserPasswordEncoderInterface $encoder, GiftAmountRepository $giftAmountRepository)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['validation_groups' => 'notEnterprise', 'fieldAdress' => false])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setIsEnterprise(false);
            $user->setRoles(['ROLE_USER']);
            $passwordEncoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($passwordEncoded);

            if ($this->session->get('giftCode')) {
                $user->setUmberGiftCard($this->session->get('giftCode'));

                $gift = $giftAmountRepository->findOneBy(['code' => $this->session->get('giftCode')]);
                $this->session->clear();
                if ($gift && !$gift->getEvent()) {
                    $user->setMoneyGift($gift->getAmount());
                }
            }

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('front_home_index', ['inscription' => 'succes']);
        }

        return $this->render('security/subscription_client.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/mot-de-passe-oublie", name="front_client_forgot_password")
     */
    public function forgotPassword(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(ForgotPasswordType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $userExist = $userRepository->findOneBy(['email' => $form->get('mail')->getData()]);

            if (!$userExist) {
                $this->addFlash('notice', 'Aucun utilisateur PassionAtelier ne correspond à ce mail');
                return $this->redirectToRoute('front_client_forgot_password');
            }

            $dateNow = new \DateTime();
            $newPassword = $dateNow->format('m').uniqid().$dateNow->format('d');
            $passwordEncoded = $encoder->encodePassword($userExist, $newPassword);
            $userExist->setPassword($passwordEncoded);

            $this->em->flush();

            $message = (new \Swift_Message('Nouveau mot de passe'))
                ->setFrom('contact@passionatelier.fr')
                ->setTo($userExist->getEmail())
                ->setBody(
                    $this->renderView(
                    // templates/emails/registration.html.twig
                        'front/mail/forgot_password.html.twig',
                        ['password' => $newPassword, 'user' => $userExist]
                    ),
                    'text/html'
                );

            $mailer->send($message);

            $this->addFlash('success', 'Un nouveau mot de passe provisoire vient de vous être envoyé par mail.');
            return $this->redirectToRoute('app_front_login');
        }
        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
