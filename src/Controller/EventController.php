<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\EventType;
use App\Entity\GiftAmount;
use App\Form\CommentType;
use App\Form\GiftCardCodeType;
use App\Repository\CommentRepository;
use App\Repository\EventRepository;
use App\Repository\GiftAmountRepository;
use App\Repository\ReservationRepository;
use App\Repository\WorkshopDateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
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
     * @Route("/recherche/atelier/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="front_search_event")
     */
    public function searchEvent(int $page, Request $request, EventRepository $eventRepository, SessionInterface $session, PaginatorInterface $paginator)
    {
        if ($request->isMethod('POST')) {
            if ($request->get('city') || $request->get('date') || $request->get('category')) {

                $parameters = [
                    'city' => ($request->get('city') === '') ? null : $request->get('city'),
                    'date' => ($request->get('date') === '') ? null : $request->get('date'),
                    'category' => ($request->get('category') === '') ? null : $request->get('category'),
                ];

                $session->set('searchCity', $parameters['city']);
                $session->set('searchDate', $parameters['date']);
                $session->set('searchCategory', $parameters['category']);

                $events = $eventRepository->searchEvents($parameters);
            } else {
                $session->clear();
                $events = $eventRepository->getEventsNotPassed();
            }
        } else {
            if ($session->get('btnOffertWorkshop')) {
                $session->remove('searchCity');
                $session->remove('searchDate');
                $session->remove('searchCategory');
            } else {
                $session->clear();
            }

            $events = $eventRepository->getEventsNotPassed();
        }

        $pagination = $paginator->paginate(
            $events, /* query NOT result */
            $page, /*page number*/
            10 /*limit per page*/
        );

        return $this->render('front/event/events_category.html.twig', [
            'events' => $events,
            'pagination' => $pagination,
            'eventType' => $request->get('category') ? $request->get('category') : 'Recherche'
        ]);
    }

    /**
     * @Route("/evenements/categorie/{slug}/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="front_event_category")
     */
    public function eventsCategory(int $page, EventType $eventType, EventRepository $eventRepository, PaginatorInterface $paginator)
    {
        $events = $eventRepository->findBy(['eventType' => $eventType]);

        $pagination = $paginator->paginate(
            $events, /* query NOT result */
            $page, /*page number*/
            10 /*limit per page*/
        );

        return $this->render('front/event/events_category.html.twig', [
            'pagination' => $pagination,
            'eventType' => $eventType,
            'events' => $events
        ]);
    }

    /**
     * @Route("/evenement/{id}/{category}/{slug}", name="front_event_single")
     */
    public function single(Request $request, Event $event, CommentRepository $commentRepository, ReservationRepository $reservationRepository, SessionInterface $session, WorkshopDateRepository $workshopDateRepository)
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setEvent($event);
            $comment->setUser($this->getUser());

            $countComments = count($event->getComments());
            $noteEvent = $event->getNote();
            if (!$noteEvent || $countComments === 0) {
                $event->setNote($comment->getNoteUser());
            } else {
                $allNotes = ($countComments * $noteEvent) + $comment->getNoteUser();
                $noteFinal = $allNotes / ($countComments + 1);
                $event->setNote($noteFinal);
            }

            $this->em->persist($comment);
            $this->em->flush();

            $message = ($comment->getContent()) ? 'Merci pour votre commentaire et votre note !' : 'Merci pour votre note !';
            $this->addFlash('notice', $message);
            return $this->redirect($this->generateUrl('front_event_single', ['id' => $event->getId(), 'category' => $event->getEventType()->getSlug(), 'slug' => $event->getSlug(), '#review']));
        }

        $eventCommented = true;
        $messageComment = '';
        if ($this->getUser()) {
            $commentUser = $commentRepository->findOneBy(['user' => $this->getUser(), 'event' => $event]);
            if ($commentUser) {
                $eventCommented = true;
                $messageComment = 'Vous avez déjà noté cet atelier.';
            } else {
                $reservation = $reservationRepository->getReservationUser($this->getUser(), $event);
                if (empty($reservation)) {
                    $eventCommented = true;
                    $messageComment = 'Vous n’avez pas participé cet atelier.';
                } else {
                    $eventCommented = false;
                }
            }
        } else {
            $messageComment = 'Vous devez être connecté pour donner votre avis.';
        }

        return $this->render('front/event/single.html.twig', [
            'event' => $event,
            'workshopDates' => $workshopDateRepository->getDatesEvent($event),
            'form' => $form->createView(),
            'eventCommented' => $eventCommented,
            'messageComment' => $messageComment
        ]);
    }

    /**
     * @Route("/ma-carte-cadeau", name="front_event_mygift_card")
     */
    public function myGiftCard(Request $request, GiftAmountRepository $giftAmountRepository, CommentRepository $commentRepository)
    {
        $form = $this->createForm(GiftCardCodeType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = $form->get('code')->getData();

            /** @var GiftAmount|null $codeExist */
            $codeExist = $giftAmountRepository->findOneBy(['code' => $code]);
            if (!$codeExist) {
                $this->addFlash('notice', 'Le code entré n’est pas correct, veuillez vérifier de nouveau les caractères.');
                return $this->redirectToRoute('front_event_mygift_card');
            }

            if (!$codeExist->getIsValid()) {
                $this->addFlash('notice', 'Ce code n’est pas valide.');
                return $this->redirectToRoute('front_event_mygift_card');
            }

            $dateValid = $codeExist->getCreatedAt();
            $dateNow = new \DateTime();
            $interval = $dateValid->diff($dateNow);

            if ($codeExist->getIsValid() && $interval->y >= 1) {
                $this->addFlash('notice', 'Ce code a dépassé sa date de validité d\'un an.');
                return $this->redirectToRoute('front_event_mygift_card');
            }

            if ($this->getUser()) {
                $this->getUser()->setUmberGiftCard($codeExist->getCode());
                if (!$codeExist->getEvent()) {
                    $this->getUser()->setMoneyGift($codeExist->getAmount());
                }
                $this->em->flush();

            } else {
                $this->session->clear();
                $this->session->set('giftCode', $codeExist->getCode());
            }

            $event = $codeExist->getEvent();

            if ($event) {
                $bestComment = $commentRepository->getBestComment($event);
                return $this->render('front/event/gift_event.html.twig', [
                    'event' => $codeExist->getEvent(),
                    'bestComment' => $bestComment ? $bestComment : null
                ]);

            } else {
                return $this->render('front/event/gift_money.html.twig', [
                    'giftAmount' => $codeExist
                ]);
            }
        }

        return $this->render('front/event/my_gift_card.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
