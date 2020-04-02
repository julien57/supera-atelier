<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\WorkshopDate;
use App\Form\InformationsClientType;
use App\Form\UserType;
use App\Repository\EventRepository;
use App\Repository\GiftAmountRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDateRepository;
use App\Services\Payment\StripeClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("/reservation")
 */
class ReservationController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/informations/{id}", name="front_reservation_informations")
     */
    public function infosReservation(WorkshopDate $workshopDate, Request $request, SessionInterface $session)
    {
        if (!$this->getUser()) {
            $this->addFlash('notice', 'Merci de vous connecter ou de vous inscrire pour réserver.');
            return $this->redirectToRoute('app_front_login');
        }
        $form = $this->createForm(InformationsClientType::class, $this->getUser(), ['validation_groups' => 'isEnterprise'])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->flush();

            $session->set('idUser', $this->getUser()->getId());
            $session->set('idWorkshopDate', $workshopDate->getId());
            return $this->redirectToRoute('front_reservation_paiement');
        }

        return $this->render('front/reservation/informations_reservation.html.twig', [
            'form' => $form->createView(),
            'workshopDate' => $workshopDate
        ]);
    }

    /**
     * @Route("/paiement", name="front_reservation_paiement")
     */
    public function paymentReservation(Request $request, SessionInterface $session,GiftAmountRepository $giftAmountRepository, UserRepository $userRepository, WorkshopDateRepository $workshopDateRepository, StripeClient $stripeClient)
    {
        if (!$session->get('idUser') || !$session->get('idWorkshopDate')) {
            return $this->redirectToRoute('front_home_index');
        }

        $workshopDate = $workshopDateRepository->find($session->get('idWorkshopDate'));

        $form = $this->get('form.factory')
            ->createNamedBuilder('payment-form')
            ->add('token', HiddenType::class, [
                'constraints' => [new NotBlank(['message' => 'Veuillez renseigner vos informations de paiement'])],
            ])
            ->getForm();

        $user = $userRepository->find($session->get('idUser'));
        $priceEvent = null;
        $restCardGift = null;
        $session->set('isPaypal', true);

        if ($user && $user->getMoneyGift() > 0) {
            $priceEvent = $workshopDate->getEvent()->getPrice() - $user->getMoneyGift();
            if ($priceEvent < 0) {
                $priceEvent = 0;
            } else {
                $session->set('ValidationWithoutPayment', true);
            }
            $session->set('priceEvent', $priceEvent);

            $restCardGift = $user->getMoneyGift() - $workshopDate->getEvent()->getPrice();
            if ($restCardGift < 0) {
                $restCardGift = 0;
            }
            $session->set('restGiftCard', $restCardGift);
        } elseif ($user->getUmberGiftCard()) {
            $giftAmount = $giftAmountRepository->findOneBy(['code' => $user->getUmberGiftCard()]);
            if ($giftAmount && $giftAmount->getIsValid()) {
                if ($giftAmount->getEvent() === $workshopDate->getEvent() && $giftAmount->getCode() === $user->getUmberGiftCard()) {
                    $priceEvent = 0;
                    $session->set('priceEvent', $priceEvent);
                    $session->set('ValidationWithoutPayment', true);
                    $session->set('useGiftCard', $giftAmount->getId());
                }
            }
        }

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                $user = $this->getUser();
                $isPaid = $stripeClient->createPremiumCharge($user, $workshopDate, $request->get('payment-form')['token'], $session->get('priceEvent'));

                if (!$isPaid) {
                    $this->addFlash('danger', 'Erreur lors du paiement, vous pouvez tenter une nouvelle fois.');
                    return $this->redirectToRoute('front_space_home', ['erreur' => 'erreur']);
                }

                if ($session->get('restGiftCard')) {
                    $user->setMoneyGift($session->get('restGiftCard'));
                }

                $this->em->flush();

                $session->set('payment', 'successStripe');
                return $this->redirectToRoute('front_reservation_reservation_succes');
            }
        }

        return $this->render('front/reservation/payment_reservation.html.twig', [
            'form' => $form->createView(),
            'workshopDate' => $workshopDate,
            'priceEvent' => $priceEvent,
            'restCardGift' => $restCardGift
        ]);
    }

    /**
     * @Route("/paiement-paypal", name="front_reservation_payment_paypal")
     */
    public function PaypalPayment(Request $request, SessionInterface $session, UserRepository $userRepository, WorkshopDateRepository $workshopDateRepository, GiftAmountRepository $giftAmountRepository)
    {
        if (!$session->get('idUser') || !$session->get('idWorkshopDate')) {
            return $this->redirectToRoute('front_home_index');
        }

        $workshopDate = $workshopDateRepository->find($session->get('idWorkshopDate'));
        $user = $userRepository->find($session->get('idUser'));
        $priceEvent = null;
        $restCardGift = null;

        if ($request->isMethod('POST')) {

            $reservation = new Reservation();
            $reservation->setUser($user);
            $reservation->setWorkshopDate($workshopDate);
            $reservation->setChargeId($request->get('id'));
            $numberOrder = $reservation->getReservedAt()->format('s').$reservation->getReservedAt()->format('i').$reservation->getReservedAt()->format('h').$workshopDate->getId().$user->getId().$reservation->getId();
            $reservation->setOrderNumber($numberOrder);

            if ($session->get('restGiftCard')) {
                $user->setMoneyGift($session->get('restGiftCard'));
            }

            $this->em->flush();

            $this->em->persist($reservation);
            $this->em->flush();

            $session->set('payment', 'successPaypal');
            return new JsonResponse(['response' => 'statusOK']);
        }

        if ($user && $user->getMoneyGift() > 0) {
            $priceEvent = $workshopDate->getEvent()->getPrice() - $user->getMoneyGift();
            if ($priceEvent < 0) {
                $priceEvent = 0;
            } else {
                $session->set('ValidationWithoutPayment', true);
            }
            $session->set('priceEvent', $priceEvent);

            $restCardGift = $user->getMoneyGift() - $workshopDate->getEvent()->getPrice();
            if ($restCardGift < 0) {
                $restCardGift = 0;
            }
            $session->set('restGiftCard', $restCardGift);
        } elseif ($user->getUmberGiftCard()) {
            $giftAmount = $giftAmountRepository->findOneBy(['code' => $user->getUmberGiftCard()]);
            if ($giftAmount && $giftAmount->getIsValid()) {
                if ($giftAmount->getEvent() === $workshopDate->getEvent() && $giftAmount->getCode() === $user->getUmberGiftCard()) {
                    $priceEvent = 0;
                    $session->set('priceEvent', $priceEvent);
                    $session->set('ValidationWithoutPayment', true);
                    $session->set('useGiftCard', $giftAmount->getId());
                }
            }
        }

        return $this->render('front/reservation/paypal_payment.html.twig', [
            'workshopDate' => $workshopDate,
            'priceEvent' => $priceEvent,
            'restCardGift' => $restCardGift
        ]);
    }

    /**
     * @Route("/reservation/validation-sans-paiement", name="front_reservation_without_payment")
     */
    public function reservationWithoutPayment(SessionInterface $session, WorkshopDateRepository $workshopDateRepository, UserRepository $userRepository, GiftAmountRepository $giftAmountRepository)
    {
        if (!$session->get('idUser') || !$session->get('idWorkshopDate') || !$session->get('ValidationWithoutPayment') ) {
            return $this->redirectToRoute('front_home_index');
        }

        $workshopDate = $workshopDateRepository->find($session->get('idWorkshopDate'));
        $user = $userRepository->find($session->get('idUser'));

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setWorkshopDate($workshopDate);
        $reservation->setChargeId('Reservation with gift card');
        $numberOrder = $reservation->getReservedAt()->format('ymd').$workshopDate->getId().$user->getId().$reservation->getId();
        $reservation->setOrderNumber($numberOrder);

        if ($session->get('useGiftCard')) {
            $giftCard = $giftAmountRepository->find($session->get('useGiftCard'));
            $giftCard->setIsValid(false);
        }

        $this->em->persist($reservation);
        $this->em->flush();

        $session->set('payment', 'success');
        return $this->redirectToRoute('front_reservation_reservation_succes');
    }

    /**
     * @Route("/reservation/succes", name="front_reservation_reservation_succes")
     */
    public function reservationSuccess(SessionInterface $session, UserRepository $userRepository, WorkshopDateRepository $workshopDateRepository)
    {
        if (!$session->get('idUser') || !$session->get('idWorkshopDate') && ($session->get('payment') !== 'successStripe' || $session->get('payment') !== 'successPaypal')) {
            return $this->redirectToRoute('front_home_index');
        }

        $user = $userRepository->findOneBy(['id' => $session->get('userId')]);
        $workshopDate = $workshopDateRepository->find($session->get('idWorkshopDate'));
        $session->clear();

        return $this->render('front/reservation/reservation_success.html.twig', [
            'user' => $user,
            'workshopDate' => $workshopDate
        ]);
    }
}
