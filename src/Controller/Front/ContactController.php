<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

final class ContactController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier){}
   
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $message = $request->request->get('message');
    
            if (empty($name) || empty($email) || empty($message)) {
                $this->addFlash('danger', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_contact'); // ⬅️ Safe: redirect after POST
            }
    
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('danger', 'Adresse e-mail invalide.');
                return $this->redirectToRoute('app_contact');
            }
    
            // Send email...
            try {
                $user = new User();
                 // generate a signed url and email it to the user
                    $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('samar.alkhalil@gmail.com', 'AdminMail'))
                        ->to(new Address('samar.alkhalil@gmail.com', 'AdminMail'))
                        ->replyTo($email)
                        ->subject('📩 Nouveau message via le formulaire de contact')
                        ->html($this->renderView('front/ressources/emails/contact.html.twig', [
                            'name' => $name,
                            'email' => $email,
                            'message' => $message,
                        ]))
                );
                $this->addFlash('success', 'Votre message a bien été envoyé !');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur lors de l\'envoi du message.');
                $logger->error('Erreur Mailjet : ' . $e->getMessage());
            }
    
           // return $this->redirectToRoute('app_contact'); // ✅ prevents re-POST on refresh
        }
    
        // Default GET response
        return $this->render('front/ressources/contact.html.twig');
    }
}
