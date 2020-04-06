<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @Route("/make-admin", name="make_admin")
     */
    public function use(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $admin = $userRepository->find(1);
        $admin->setRoles(["ROLE_ADMIN"]);

        $em->flush();

        return $this->redirectToRoute('front_home_index');
    }
}
