<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Model\Contact;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="front_contact")
     */
    public function contact(Request $request, \Swift_Mailer $mailer)
    {
        $contactDTO = new Contact();
        $form = $this->createForm(ContactType::class, $contactDTO)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $message = (new \Swift_Message('Contact SuperAtelier'))
                ->setFrom($contactDTO->getEmail())
                ->setTo('contact@superatelier.fr')
                ->setBody(
                    $this->renderView(
                        'front/mail/contact.html.twig',
                        ['contact' => $contactDTO]
                    ),
                    'text/html'
                )
            ;

            $mailer->send($message);

            $this->addFlash('success', 'Votre message a bien été envoyé, nous vous répondrons dans les plus brefs délais.');
            return $this->redirectToRoute('front_contact');
        }

        return $this->render('front/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }
}