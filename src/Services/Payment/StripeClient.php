<?php

namespace App\Services\Payment;

use App\Entity\Event;
use App\Entity\GiftAmount;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\WorkshopDate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StripeClient
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, SessionInterface $session)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->session = $session;
    }

    public function createPremiumCharge(User $user, WorkshopDate $workshopDate, $token, ?int $priceWithGiftCard = null)
    {
        $price = $priceWithGiftCard ? $priceWithGiftCard : $workshopDate->getEvent()->getPrice();

        try {
            Stripe::setApiKey('sk_live_iqktbBggPUkZFaKJ27jpF4rX00SUR2bAgT');
            $charge = Charge::create([
                'amount' => $price * 100,
                'currency' => 'EUR',
                'description' => 'Paiement atelier',
                'receipt_email' => $user->getEmail(),
                'source' => $token,
                'metadata' => ['integration_check' => 'accept_a_payment']
            ]);

        } catch (ApiErrorException $e) {
            $this->logger->error(sprintf('%s exception encountered when creating a premium payment: "%s"', get_class($e), $e->getMessage()), ['exception' => $e]);

            return false;
        }

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setWorkshopDate($workshopDate);
        $reservation->setChargeId($charge->id);
        $numberOrder = $reservation->getReservedAt()->format('s').$reservation->getReservedAt()->format('i').$reservation->getReservedAt()->format('h').$workshopDate->getId().$user->getId().$reservation->getId();
        $reservation->setOrderNumber($numberOrder);

        $this->em->persist($reservation);
        $this->em->flush();

        return true;
    }

    public function createGiftAmount(User $user, GiftAmount $giftAmount, $token)
    {
        try {
            Stripe::setApiKey('sk_live_iqktbBggPUkZFaKJ27jpF4rX00SUR2bAgT');
            $charge = Charge::create([
                'amount' => $giftAmount->getAmount() * 100,
                'currency' => 'EUR',
                'description' => 'Carte cadeau',
                'receipt_email' => $user->getEmail(),
                'source' => $token,
                'metadata' => ['integration_check' => 'accept_a_payment']
            ]);

        } catch (ApiErrorException $e) {
            $this->logger->error(sprintf('%s exception encountered when creating a premium payment: "%s"', get_class($e), $e->getMessage()), ['exception' => $e]);

            return false;
        }

        $giftAmount->setIsValid(true);
        $giftAmount->setChargeId($charge->id);

        $this->em->flush();

        return true;
    }
}
