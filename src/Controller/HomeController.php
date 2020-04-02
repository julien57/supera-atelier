<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="front_home_index")
     */
    public function index(EventTypeRepository $eventTypeRepository, EventRepository $eventRepository)
    {
        $topCategoriesEvents = $eventTypeRepository->topCategories();

        return $this->render('front/index.html.twig', [
            'categories' => $eventTypeRepository->findAll(),
            'topEvents' => $eventRepository->getTopEvents(),
            'topCategories' => $topCategoriesEvents
        ]);
    }
}
