<?php

namespace App\Controller\Back;

use App\Entity\EventType;
use App\Form\EventTypeType;
use App\Repository\EventTypeRepository;
use App\Services\File\UploadFile;
use App\Services\Slug;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class EventTypeController extends AbstractController
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
     * @Route("/categories", name="admin_events_type_list")
     */
    public function list(EventTypeRepository $eventTypeRepository)
    {
        return $this->render('back/event_type/list.html.twig', [
            'eventsType' => $eventTypeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/categories/ajouter", name="admin_events_type_add")
     */
    public function add(Request $request, Slug $slug)
    {
        $eventType = new EventType();
        $form = $this->createForm(EventTypeType::class, $eventType)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageName = UploadFile::uploadIconCategory($eventType->getIcon());
            $eventType->setIcon($imageName);

            $eventType->setSlug($slug->slugify($eventType->getName()));

            $this->em->persist($eventType);
            $this->em->flush();

            return $this->redirectToRoute('admin_events_type_list', ['action' => 'success']);
        }

        return $this->render('back/event_type/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/categories/editer/{id}", name="admin_events_type_edit")
     */
    public function edit(Request $request, Slug $slug, EventType $eventType)
    {
        $oldIcon = $eventType->getIcon();
        $form = $this->createForm(EventTypeType::class, $eventType)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$eventType->getIcon()) {
                $eventType->setIcon($oldIcon);
            } else {
                $newIcon = UploadFile::uploadIconCategory($eventType->getIcon());
                $eventType->setIcon($newIcon);
            }

            $eventType->setSlug($slug->slugify($eventType->getName()));

            $this->em->persist($eventType);
            $this->em->flush();

            return $this->redirectToRoute('admin_events_type_list', ['action' => 'editsuccess']);
        }

        return $this->render('back/event_type/add.html.twig', [
            'form' => $form->createView(),
            'eventType' => $eventType
        ]);
    }

    /**
     * @Route("/categories/suppression/{id}", name="admin_events_type_remove")
     */
    public function remove(EventType $eventType)
    {
        $this->em->remove($eventType);
        $this->em->flush();

        return $this->redirectToRoute('admin_events_type_list');
    }
}
