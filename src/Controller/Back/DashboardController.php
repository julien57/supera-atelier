<?php

namespace App\Controller\Back;

use App\Repository\CommentRepository;
use App\Repository\EventRepository;
use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard", name="admin_dashboard")
     */
    public function index(EventRepository $eventRepository, CommentRepository $commentRepository, ReservationRepository $reservationRepository)
    {
        return $this->render('back/dashboard.html.twig', [
            'countEvents' => $eventRepository->getCountEvents(),
            'countComments' => $commentRepository->getCountComments(),
            'countReservations' => $reservationRepository->getCountReservations(),
            'lastEvents' => $eventRepository->findBy([], ['id' => 'DESC'], 5),
            'lastReservations' => $reservationRepository->findBy([], ['id' => 'DESC'], 5)
        ]);
    }
}
