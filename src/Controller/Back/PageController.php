<?php

namespace App\Controller\Back;

use App\Form\PageType;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/page")
 */
class PageController extends AbstractController
{
    /**
     * @Route("/presentation", name="back_page_presentation")
     */
    public function truc(PageRepository $pageRepository, Request $request, EntityManagerInterface $em)
    {
        $page = $pageRepository->find(['id' => 1]);
        $form = $this->createForm(PageType::class, $page)->handleRequest($request);

        if ($form->isSubmitted()) {
            $em->flush();

            $this->addFlash('success', 'Pages sauvegardées !');
            return $this->redirectToRoute('back_page_presentation');
        }

        return $this->render('back/page/presentation.html.twig', [
            'presentation' => $pageRepository->find(['id' => 1]),
            'form' => $form->createView()
        ]);
    }
}