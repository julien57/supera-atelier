<?php

namespace App\Controller\Back;

use App\Repository\CommentRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class CommentController extends AbstractController
{
    /**
     * @Route("/notes-et-avis/{page}", defaults={"page"=1}, requirements={"page"="\d+"}, name="back_comments_list")
     */
    public function list(int $page, CommentRepository $commentRepository, PaginatorInterface $paginator)
    {
        $pagination = $paginator->paginate(
            $commentRepository->findBy([], ['publishedAt' => 'DESC']), /* query NOT result */
            $page, /*page number*/
            25 /*limit per page*/
        );

        return $this->render('back/comment/list.html.twig', [
            'comments' => $pagination
        ]);
    }
}