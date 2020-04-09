<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    /**
     * @Route("/presentation", name="front_presentation")
     */
    public function presentation(PageRepository $pageRepository)
    {
        return $this->render('front/page/presentation.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/qui-peut-deposer-un-atelier", name="front_depose_atelier")
     */
    public function deposeAtelier(PageRepository $pageRepository)
    {
        return $this->render('front/page/depose_atelier.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/avantages-deposer-un-atelier", name="front_avantage_depose_atelier")
     */
    public function avanatage(PageRepository $pageRepository)
    {
        return $this->render('front/page/avantage.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/mentions-legales", name="front_mentions")
     */
    public function mentions(PageRepository $pageRepository)
    {
        return $this->render('front/page/mentions.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/conditions-generales-de-vente", name="front_cgv")
     */
    public function cgv(PageRepository $pageRepository)
    {
        return $this->render('front/page/cgv.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/politique-cookies", name="front_cookies")
     */
    public function cookies(PageRepository $pageRepository)
    {
        return $this->render('front/page/cookies.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }

    /**
     * @Route("/comites-entreprise-et-groupes", name="front_groups")
     */
    public function ceGroups(PageRepository $pageRepository)
    {
        return $this->render('front/page/groups.html.twig', [
            'page' => $pageRepository->find(1)
        ]);
    }
}
