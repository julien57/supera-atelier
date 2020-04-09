<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\GiftAmount;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\AmountGiftType;
use App\Form\PersonalizeAmountGiftType;
use App\Form\UserType;
use App\Repository\EventRepository;
use App\Repository\GiftAmountRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDateRepository;
use App\Services\Payment\PayPal;
use App\Services\Payment\StripeClient;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("/carte-cadeaux")
 */
class GiftController extends AbstractController
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
     * @Route("/connexion-inscription", name="front_gift_connection")
     */
    public function index(AuthenticationUtils $authenticationUtils, Request $request, UserPasswordEncoderInterface $encoder)
    {
        $this->session->remove('btnOffertWorkshop');
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($this->getUser()) {
            $this->session->set('userId', $this->getUser()->getId());

            if ($this->session->get('choiceAmount')) {
                return $this->redirectToRoute('front_gift_choice_amount');
            } else {
                return $this->redirectToRoute('front_search_event');
            }
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['validation_groups' => 'isEnterprise', 'fieldAdress' => true])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setIsEnterprise(false);
            $user->setRoles(['ROLE_USER']);
            $passwordEncoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($passwordEncoded);

            $this->em->persist($user);
            $this->em->flush();

            $this->session->set('userId', $user->getId());

            return $this->redirectToRoute('front_gift_choice_amount');
        }

        return $this->render('front/gift/connexion.twig', [
            'form' => $form->createView(),
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/quoi-offrir", name="front_gift_choice_gift")
     */
    public function choiceGift()
    {
        $this->session->set('btnOffertWorkshop', 'btnOffertWorkshop');

        return $this->render('front/gift/choice_gift.html.twig');
    }

    /**
     * @Route("/choice-amount", name="redirect_choice_amount")
     */
    public function redirectAmount()
    {
        $this->session->set('choiceAmount', 'choiceAmount');

        return $this->redirectToRoute('front_gift_connection');
    }

    /**
     * @Route("/offrir-un-atelier/{id}", name="front_gift_choice_workshop")
     */
    public function choiceWorkshop(Event $event)
    {
        $this->session->set('eventIdGift', $event->getId());

        return $this->render('front/gift/choice_workshop.html.twig', [
            'event' => $event
        ]);
    }

    /**
     * @Route("/offrir-un-montant", name="front_gift_choice_amount")
     */
    public function choiceAmount(Request $request, UserRepository $userRepository, EventRepository $eventRepository)
    {
        if (!$this->session->get('userId')) {
            return $this->redirectToRoute('front_gift_connection');
        }

        $user = $userRepository->find($this->session->get('userId'));
        if (!$user) {
            return $this->redirectToRoute('front_gift_connection');
        }

        if ($this->session->get('eventIdGift')) {

            $eventGift = $eventRepository->find($this->session->get('eventIdGift'));
            if (!$eventGift) {
                return $this->redirectToRoute('front_home_index');
            }
            $newGift = new GiftAmount();
            $newGift->setAmount($eventGift->getPrice());
            $newGift->setCode(uniqid());
            $newGift->setUser($user);
            $newGift->setEvent($eventGift);

            $this->em->persist($newGift);
            $this->em->flush();

            $this->session->set('giftAmountId', $newGift->getId());

            return $this->redirectToRoute('front_gift_personalize');
        }

        $form = $this->createForm(AmountGiftType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$form->get('amount')->getData() && !$form->get('choiceAmount')->getData()) {
                $this->addFlash('notice', 'Veuillez choisir un montant.');
                return $this->redirectToRoute('front_gift_choice_amount');
            }
            if ($form->get('amount')->getData() && $form->get('choiceAmount')->getData()) {
                $this->addFlash('notice', 'Veuillez Définir un montant OU en sélectionner un.');
                return $this->redirectToRoute('front_gift_choice_amount');
            }
            $gift = new GiftAmount();
            if ($form->get('amount')->getData()) {
                $gift->setAmount($form->get('amount')->getData());

            } elseif ($form->get('choiceAmount')->getData()) {
                $gift->setAmount($form->get('choiceAmount')->getData());
            }

            $gift->setCode(uniqid());
            $gift->setUser($user);

            $this->em->persist($gift);
            $this->em->flush();

            $this->session->set('giftAmountId', $gift->getId());

            return $this->redirectToRoute('front_gift_personalize');
        }

        return $this->render('front/gift/choice_amount.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/personnalisation-carte", name="front_gift_personalize")
     */
    public function peronalizeCard(GiftAmountRepository $giftAmountRepository, Request $request)
    {
        if (!$this->session->get('userId') || !$this->session->get('giftAmountId')) {
            return $this->redirectToRoute('front_gift_connection');
        }
        $giftAmount = $giftAmountRepository->find($this->session->get('giftAmountId'));
        if (!$giftAmount) {
            return $this->redirectToRoute('front_gift_connection');
        }

        $form = $this->createForm(PersonalizeAmountGiftType::class, $giftAmount)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->flush();

            $this->session->set('fullInformation', true);
            return $this->redirectToRoute('front_gift_payment');
        }

        return $this->render('front/gift/personalize_card.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/paiement", name="front_gift_payment")
     */
    public function payment(Request $request, GiftAmountRepository $giftAmountRepository, UserRepository $userRepository, StripeClient $stripeClient)
    {
        if (!$this->session->get('userId') || !$this->session->get('giftAmountId') || !$this->session->get('fullInformation')) {
            return $this->redirectToRoute('front_gift_connection');
        }
        $giftAmount = $giftAmountRepository->find($this->session->get('giftAmountId'));
        if (!$giftAmount) {
            return $this->redirectToRoute('front_gift_connection');
        }

        $form = $this->get('form.factory')
            ->createNamedBuilder('payment-form')
            ->add('token', HiddenType::class, [
                'constraints' => [new NotBlank(['message' => 'Veuillez renseigner vos informations de paiement'])],
            ])
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $user = $userRepository->findOneBy(['id' => $this->session->get('userId')]);
                $isPaid = $stripeClient->createGiftAmount($user, $giftAmount, $request->get('payment-form')['token']);

                if (!$isPaid) {
                    $this->addFlash('danger', 'Erreur lors du paiement, vous pouvez tenter une nouvelle fois.');
                    return $this->redirectToRoute('front_gift_payment');
                }

                $this->session->set('payment', 'success');
                $this->session->set('payment', 'successStripe');
                return $this->redirectToRoute('front_gift_success');
            }
        }

        return $this->render('front/gift/payment.html.twig', [
            'form' => $form->createView(),
            'gift' => $giftAmount,
            'stripe_public_key' => getenv('STRIPE_PUBLIC_KEY')
        ]);
    }

    /**
     * @Route("/paiement-paypal", name="front_gift_payment_paypal")
     */
    public function PaypalPayment(Request $request, SessionInterface $session, PayPal $payPal, GiftAmountRepository $giftAmountRepository)
    {
        if (!$this->session->get('userId') || !$this->session->get('giftAmountId') || !$this->session->get('fullInformation')) {
            return $this->redirectToRoute('front_gift_connection');
        }
        $giftAmount = $giftAmountRepository->find($this->session->get('giftAmountId'));
        if (!$giftAmount) {
            return $this->redirectToRoute('front_gift_connection');
        }

        if ($request->isMethod('POST')) {

            $giftAmount->setIsValid(true);
            $giftAmount->setChargeId($request->get('id'));

            $this->em->flush();

            $session->set('payment', 'successPaypal');

            $session->set('payment', 'successPaypal');
            return new JsonResponse(['response' => 'statusOK']);
        }

        return $this->render('front/gift/payment_paypal.html.twig', [
            'gift' => $giftAmount
        ]);
    }

    /**
     * @Route("/paypal-payment/payment", name="front_gift_paypal_payment_payment")
     */
    public function paymentPaymentPaypal(PayPal $payPal, GiftAmountRepository $giftAmountRepository)
    {
        $giftAmount = $giftAmountRepository->find($this->session->get('giftAmountId'));
        $paymentId = $payPal->create('https://passionatelier.fr/carte-cadeaux/paypal-payment/payment', 'https://passionatelier.fr/carte-cadeaux/carte-cadeau/erreur', $giftAmount);
        return $this->json(['id' => $paymentId]);
    }

    /**
     * @Route("/paypal-payment/confirm-transaction", name="front_gift_paypal_confirm_transaction")
     */
    public function pay(PayPal $payPal, SessionInterface $session)
    {
        $request = Request::createFromGlobals();
        $session->set('isPaypal', true);
        $payPal->payment($request->request->all());
    }

    /**
     * @Route("/carte-cadeau/erreur", name="front_gift_error")
     */
    public function errorPayment()
    {
        return $this->render('front/gift/error_paypal.html.twig');
    }

    /**
     * @Route("/carte-cadeau/confirmation", name="front_gift_success")
     */
    public function frontGiftSuccess(UserRepository $userRepository, \Swift_Mailer $mailer, KernelInterface $kernel, GiftAmountRepository $giftAmountRepository)
    {
        if (!$this->session->get('userId')
            || !$this->session->get('giftAmountId')
            || !$this->session->get('fullInformation')
            && ($this->session->get('payment') !== 'successStripe' || $this->session->get('payment') !== 'successPaypal')) {
            return $this->redirectToRoute('front_gift_connection');
        }

        $user = $userRepository->find($this->session->get('userId'));
        if (!$user) {
            return $this->redirectToRoute('front_gift_connection');
        }
        $giftAmount = $giftAmountRepository->find($this->session->get('giftAmountId'));
        if (!$giftAmount) {
            return $this->redirectToRoute('front_gift_connection');
        }

        if ($this->session->get('isPaypal')) {
            $giftAmount->setIsValid(true);
            $this->em->flush();
        }

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($pdfOptions);

        $pdf = $this->session->get('eventIdGift') ? 'front/pdf/gift_card_workshop.html.twig' : 'front/pdf/gift_card_amount.html.twig';
        $html = $this->renderView($pdf, ['gift' => $giftAmount]);

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $publicDirectory = $kernel->getProjectDir().'/public/pdf';
        // e.g /var/www/project/public/mypdf.pdf
        $pdfFilepath =  $publicDirectory . '/carte-cadeau'.$user->getId().'.pdf';

        file_put_contents($pdfFilepath, $output);

        $template = $this->session->get('eventIdGift') ? 'front/mail/gift_card_workshop.html.twig' : 'front/mail/gift_card_amount.html.twig';

        $message = (new \Swift_Message('Carte cadeau PassionAtelier'))
            ->setFrom('contact@passionatelier.com')
            ->setTo($giftAmount->getUser()->getEmail())
            ->setBody(
                $this->renderView(
                    $template,
                    ['user' => $user, 'gift' => $giftAmount]
                ),
                'text/html'
            )
            ->attach(\Swift_Attachment::fromPath($pdfFilepath, 'application/pdf'))
        ;

        $mailer->send($message);

        $fileSystem = new Filesystem();
        if ($fileSystem->exists($pdfFilepath)) {
            $fileSystem->remove($pdfFilepath);
        }

        $this->session->clear();

        return $this->render('front/gift/success.html.twig', [
            'user' => $user
        ]);
    }
}
