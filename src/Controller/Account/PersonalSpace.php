<?php

namespace App\Controller\Account;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Entity\WorkshopDate;
use App\Form\EventFormType;
use App\Form\EventSpaceType;
use App\Form\UserSpaceType;
use App\Repository\EventRepository;
use App\Repository\GiftAmountRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Repository\WorkshopDateRepository;
use App\Services\File\UploadFile;
use App\Services\Slug;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PersonalSpace extends AbstractController
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
     * @Route("/mon-espace/accueil", name="front_space_home")
     */
    public function homeSpace(GiftAmountRepository $giftAmountRepository, UserRepository $userRepository)
    {
        $gift = $giftAmountRepository->findOneBy(['code' => $this->getUser()->getUmberGiftCard(), 'isValid' => true]);
        $userGift = $userRepository->find($this->getUser()->getId());

        return $this->render('account/personal_space.html.twig', [
            'gift' => $gift,
            'user' => $userGift
        ]);
    }

    /**
     * @Route("/mon-espace/mon-profil", name="front_space_profil")
     */
    public function profil(Request $request, UserPasswordEncoderInterface $encoder, EntityManagerInterface $em)
    {
        $isEnterprise = $this->getUser()->getIsEnterprise();
        $form = $this->createForm(UserSpaceType::class, $this->getUser(), ['validation_groups' => $isEnterprise ? 'isEnterprise' : 'notEnterprise'])
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            $this->addFlash('notice', 'Vos informations de profil ont bien été modifiés');
            return $this->redirectToRoute('front_space_home');
        }

        return $this->render('account/space_profil.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/mon-espace/ateliers-reserves", name="front_space_workshop_reserved")
     */
    public function workshopReserved(ReservationRepository $reservationRepository)
    {
        return $this->render('account/participant_workshop_reserved.html.twig', [
            'reservations' => $reservationRepository->findBy(['user' => $this->getUser()], ['id' => 'DESC'])
        ]);
    }

    /**
     * @Route("/mon-espace/ateliers-reserves/facture-{id}", name="front_space_workshop_facture")
     */
    public function generatePDF(Reservation $reservation)
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('front/pdf/facture.html.twig', [
            'reservation' => $reservation
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("mypdf.pdf", [
            "Attachment" => false
        ]);
        die();
    }

    /**
     * @Route("/mon-espace/mes-ateliers", name="front_space_workshop_enterprise")
     */
    public function myWorkShop(EventRepository $eventRepository)
    {
        $events = $eventRepository->findBy(['user' => $this->getUser()], ['id' => 'DESC']);

        return $this->render('account/enterprise_space_workshop.html.twig', [
            'events' => $events
        ]);
    }

    /**
     * @Route("/mon-espace/{id}", name="front_space_workshop_remove_date")
     */
    public function removeDate(WorkshopDate $workshopDate)
    {
        $this->em->remove($workshopDate);
        $this->em->flush();

        $this->addFlash('notice', 'Date d’atelier bien effacée.');
        return $this->redirectToRoute('front_space_workshop_enterprise');
    }

    /**
     * @Route("/mon-espace/atelier/edition/{id}", name="front_space_workshop_edit")
     */
    public function myWorkShopEdit(Request $request, Slug $slug, Event $event)
    {
        $this->denyAccessUnlessGranted('EDIT', $event);

        $form = $this->createForm(EventSpaceType::class, $event)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var WorkshopDate $date */
            foreach ($event->getWorkshopDates() as $workshopDate) {

                if (!$workshopDate->getDuration()) {

                    $interval = $workshopDate->getStartAt()->diff($workshopDate->getEndAt());

                    $minutes = $interval->i > 0 ? $interval->i : '00';
                    $duration = $interval->h.'h'.$minutes.'min';
                    $workshopDate->setDuration($duration);
                    $workshopDate->setEvent($event);

                    $this->em->persist($workshopDate);
                }
            }

            foreach ($event->getPhotos() as $photo) {
                if ($photo->getUrl()) {
                    $imageName = UploadFile::uploadImageEvent($photo->getUrl());
                    $photo->setUrl($imageName);
                    $photo->setEvent($event);

                    $this->em->persist($photo);
                } else {
                    $this->em->refresh($photo);
                }
            }

            $event->setSlug($slug->slugify($event->getTitle()));

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('notice', 'Atelier modifié avec succés !');
            return $this->redirectToRoute('front_space_workshop_enterprise');
        }

        return $this->render('account/enterprise_space_workshop_edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    /**
     * @Route("/mon-espace/atelier/suppression/{id}", name="front_space_workshop_remove")
     */
    public function removeWorkshop(Event $event)
    {
        $this->denyAccessUnlessGranted('EDIT', $event);

        $this->em->remove($event);
        $this->em->flush();

        $this->addFlash('notice', 'Atelier supprimé !');
        return $this->redirectToRoute('front_space_workshop_enterprise');
    }

    /**
     * @Route("/mon-espace/date-atelier/{id}", name="front_space_workshop_dates")
     */
    public function listDatesEvent(Event $event)
    {
        return $this->render('account/enterprise_space_dates_workshop.html.twig', [
            'event' => $event
        ]);
    }

    /**
     * @Route("/mon-espace/participants/{id}", name="front_space_workshop_participants")
     */
    public function listparticipants(WorkshopDate $workshopDate, ReservationRepository $reservationRepository)
    {
        $participants = $reservationRepository->findBy(['workshopDate' => $workshopDate]);

        return $this->render('account/entreprise_space_particpants.html.twig', [
            'participants' => $participants,
            'workdate' => $workshopDate
        ]);
    }
}