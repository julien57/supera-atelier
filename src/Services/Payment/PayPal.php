<?php

namespace App\Services\Payment;

use App\Entity\GiftAmount;
use App\Entity\WorkshopDate;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PayPal
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function create(string $redirectUrl, string $cancelUrl, GiftAmount $giftAmount)
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'AUbcq9erAiBeHGCga3RDbso-tWKX8xh0qxaAeGbKOY2XtA0BuIb96WY1LAb1f_YBjex-c4JLw2IfPW3x',
                'EDQqEoCJ9n20zQvINDcB-UJ4k0DKaOeg7MnJNhwUQzd-TPEDd1YKV5C-n8znkDon7tHXn8N5sbRTIPXE'
            )
        );

        $apiContext->setConfig(
            array(
                'mode' => 'live',
                'log.LogEnabled' => true,
                'log.FileName' => '../../PayPal.log',
                'log.LogLevel' => 'DEBUG', // PLEASE USE FINE LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            )
        );

        $list = new ItemList();
        $item = (new Item())
            ->setName($giftAmount->getCode())
            ->setPrice($giftAmount->getAmount())
            ->setCurrency('EUR')
            ->setQuantity(1);
        $list->addItem($item);

        $details = (new Details())
            ->setSubtotal($giftAmount->getAmount());

        $amount = (new Amount())
            ->setTotal($giftAmount->getAmount())
            ->setCurrency('EUR')
            ->setDetails($details);

        $transaction = (new Transaction())
            ->setItemList($list)
            ->setDescription('Carte cadeau')
            ->setAmount($amount)
            ->setCustom($giftAmount->getCode());

        $payment = new Payment();
        $payment->setTransactions([$transaction]);
        $payment->setIntent('sale');
        $redirectUrls = (new RedirectUrls())
            ->setReturnUrl($redirectUrl)
            ->setCancelUrl($cancelUrl);
        $payment->setRedirectUrls($redirectUrls);
        $payment->setPayer((new Payer())->setPaymentMethod('paypal'));

        $payment->create($apiContext);

        return $payment->getId();
    }

    public function payment(array $request)
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'AUbcq9erAiBeHGCga3RDbso-tWKX8xh0qxaAeGbKOY2XtA0BuIb96WY1LAb1f_YBjex-c4JLw2IfPW3x',
                'EDQqEoCJ9n20zQvINDcB-UJ4k0DKaOeg7MnJNhwUQzd-TPEDd1YKV5C-n8znkDon7tHXn8N5sbRTIPXE'
            )
        );

        $payment = Payment::get($request['paymentID'], $apiContext);

        $execution = (new PaymentExecution())
            ->setPayerId($request['payerID'])
            ->setTransactions($payment->getTransactions());

        try {
            $payment->execute($execution, $apiContext);
            echo json_encode([
                'id' => $payment->getId()
            ]);
        } catch (PayPalConnectionException $e) {
            $this->urlGenerator->generate('front_gift_error');
            dump($e->getData());
        }
    }

    public function createReservation(string $redirectUrl, string $cancelUrl, WorkshopDate $workshopDate)
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'AUbcq9erAiBeHGCga3RDbso-tWKX8xh0qxaAeGbKOY2XtA0BuIb96WY1LAb1f_YBjex-c4JLw2IfPW3x',
                'EDQqEoCJ9n20zQvINDcB-UJ4k0DKaOeg7MnJNhwUQzd-TPEDd1YKV5C-n8znkDon7tHXn8N5sbRTIPXE'
            )
        );

        $apiContext->setConfig(
            array(
                'mode' => 'live',
                'log.LogEnabled' => true,
                'log.FileName' => '../../PayPal.log',
                'log.LogLevel' => 'DEBUG', // PLEASE USE FINE LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => true,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            )
        );

        $list = new ItemList();
        $item = (new Item())
            ->setName($workshopDate->getEvent()->getTitle())
            ->setPrice($workshopDate->getEvent()->getPrice())
            ->setCurrency('EUR')
            ->setQuantity(1);
        $list->addItem($item);

        $details = (new Details())
            ->setSubtotal($workshopDate->getEvent()->getPrice());

        $amount = (new Amount())
            ->setTotal($workshopDate->getEvent()->getPrice())
            ->setCurrency('EUR')
            ->setDetails($details);

        $transaction = (new Transaction())
            ->setItemList($list)
            ->setDescription('Carte cadeau')
            ->setAmount($amount)
            ->setCustom($workshopDate->getEvent()->getTitle());

        $payment = new Payment();
        $payment->setTransactions([$transaction]);
        $payment->setIntent('sale');
        $redirectUrls = (new RedirectUrls())
            ->setReturnUrl($redirectUrl)
            ->setCancelUrl($cancelUrl);
        $payment->setRedirectUrls($redirectUrls);
        $payment->setPayer((new Payer())->setPaymentMethod('paypal'));

        $payment->create($apiContext);

        return $payment->getId();
    }

    public function paymentReservation(array $request)
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                'AUbcq9erAiBeHGCga3RDbso-tWKX8xh0qxaAeGbKOY2XtA0BuIb96WY1LAb1f_YBjex-c4JLw2IfPW3x',
                'EDQqEoCJ9n20zQvINDcB-UJ4k0DKaOeg7MnJNhwUQzd-TPEDd1YKV5C-n8znkDon7tHXn8N5sbRTIPXE'
            )
        );

        $payment = Payment::get($request['paymentID'], $apiContext);

        $execution = (new PaymentExecution())
            ->setPayerId($request['payerID'])
            ->setTransactions($payment->getTransactions());

        try {
            $payment->execute($execution, $apiContext);
            echo json_encode([
                'id' => $payment->getId()
            ]);
        } catch (PayPalConnectionException $e) {
            $this->urlGenerator->generate('front_gift_error');
            dump($e->getData());
        }
    }
}
