<?php

namespace App\Controller\Back;

use App\Entity\Event;
use App\Entity\Photo;
use App\Entity\WorkshopDate;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use App\Repository\WorkshopDateRepository;
use App\Services\File\UploadFile;
use App\Services\Slug;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/admin")
 */
class EventController extends AbstractController
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
     * @Route("/evenements/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="admin_event_list")
     */
    public function list(int $page, EventRepository $eventRepository, PaginatorInterface $paginator)
    {
        $pagination = $paginator->paginate(
            $eventRepository->findBy([], ['id' => 'DESC']), /* query NOT result */
            $page, /*page number*/
            25 /*limit per page*/
        );

        return $this->render('back/event/list.html.twig', [
            'events' => $pagination,
        ]);
    }

    /**
     * @Route("/evenements/ajouter", name="admin_event_add")
     */
    public function add(Request $request, SessionInterface $session)
    {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event, ['csrf_protection' => false])->handleRequest($request);

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

                    $session->remove($dates);
                }
            }

            $slug = Slug::slugify($event->getTitle());
            $event->setSlug($slug);

            $this->em->persist($event);
            $this->em->flush();

            return $this->redirectToRoute('admin_event_list', ['action' => 'success']);
        }

        return $this->render('back/event/add.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    /**
     * @Route("/evenements/editer/{id}", name="admin_event_edit")
     */
    public function edit(Request $request, SessionInterface $session, Event $event, WorkshopDateRepository $workshopDateRepository)
    {
        $form = $this->createForm(EventFormType::class, $event, ['csrf_protection' => false])->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($event->getPhotos() as $photo) {

                if (!$photo->getEvent()) {
                    $imageName = UploadFile::uploadImageEvent($photo->getUrl());
                    $photo->setUrl($imageName);
                    $photo->setEvent($event);

                    $this->em->persist($photo);
                }
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
            $session->clear();

            $slug = Slug::slugify($event->getTitle());
            $event->setSlug($slug);
            $event->setEventType($form->get('eventType')->getData());

            $this->em->persist($event);
            $this->em->flush();

            return $this->redirectToRoute('admin_event_list', ['action' => 'success']);
        }

        return $this->render('back/event/edit.html.twig', [
            'form' => $form->createView(),
            'workshopDates' => $workshopDateRepository->getDatesEvent($event),
            'event' => $event
        ]);
    }

    /**
     * @Route("/deposer-atelier/removedate/{id}", name="back_event_remove_dates")
     */
    public function removeDate(Request $request, Event $event, SessionInterface $session)
    {
        $session->clear();
        foreach ($event->getWorkshopDates() as $date) {
            $this->em->remove($date);
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'ok'
        ]);
    }

    /**
     * @Route("/evenements/suppression/{id}", name="admin_event_remove")
     */
    public function remove(Event $event)
    {
        $this->em->remove($event);
        $this->em->flush();

        return $this->redirectToRoute('admin_event_list');
    }

    /**
     * @Route("/reservations/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="back_reservations_list")
     */
    public function listReservations(int $page, PaginatorInterface $paginator, ReservationRepository $reservationRepository)
    {
        $pagination = $paginator->paginate(
            $reservationRepository->findBy([], ['reservedAt' => 'DESC']), /* query NOT result */
            $page, /*page number*/
            25 /*limit per page*/
        );

        return $this->render('back/reservation/list.html.twig', [
            'reservations' => $pagination
        ]);
    }
}
