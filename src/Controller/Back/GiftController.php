<?php

namespace App\Controller\Back;

use App\Repository\GiftAmountRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class GiftController extends AbstractController
{
    /**
     * @Route("/bons-cadeaux/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="back_gift_list")
     */
    public function list(int $page, GiftAmountRepository $giftAmountRepository, PaginatorInterface $paginator)
    {
        $pagination = $paginator->paginate(
            $giftAmountRepository->findBy([], ['createdAt' => 'DESC']), /* query NOT result */
            $page, /*page number*/
            25 /*limit per page*/
        );

        return $this->render('back/gift/list.html.twig', [
            'gifts' => $pagination
        ]);
    }
}