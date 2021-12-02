<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use App\Entity\User;
use App\Form\ResetPasswordType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ResetPasswordController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    /**
     * @Route("/mot-de-passe-oublie", name="app_reset_password")
     */
    public function index(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        if ($request->get('email')) {
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));
            if ($user) {

                // 1. Enregistrer la demande de réinitialisation
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new DateTime());

                $this->entityManager->persist($reset_password);
                $this->entityManager->flush();

                // 2. Envoyer un mail à l'utilisateur avec un lien de réinitialisation
                $mail = new Mail();
                $url = $this->generateUrl('app_update_password', [
                    'token' => $reset_password->getToken(),
                ]);
                $content = "Bonjour ".$user->getFirstName().",<br>Vous avez demandé à réinitialiser votre mot de passe sur le site la Boutique Française.<br><br>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe</a>.";
                $mail->send($user->getEmail(),$user->getFirstName().' '.$user->getLastName(),'Réinitialiser votre mot de passe sur la Boutique Française', $content);

                $this->addFlash('notice', 'Vous allez recevoir par mail la procédure de réinitialisation de mot de passe.');

            } else {
                $this->addFlash('notice', 'Cette adresse mail est inconnue.');
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mon-mot-de-passe/{token}", name="app_update_password")
     */
    public function update(Request $request, $token, UserPasswordHasherInterface $hasher)
    {
        $reset_password = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$reset_password) {
            return $this->redirectToRoute('app_reset_password');
        }

        //vérification de la validité du lien par rapport à l'heure de la demande
        $now = new DateTime();
        if ($now > $reset_password->getCreatedAt()->modify('+ 3hours')) {
            $this->addFlash('notice', 'Votre demande de mot de passe a expiré. Merci de la renouveler.');
            return $this->redirectToRoute('app_reset_password');
        }

        //rendre une vue avec le changement de mot de passe
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPswd = $form->get('new_password')->getData();
            $password = $hasher->hashPassword($reset_password->getUser(), $newPswd);

            $reset_password->getUser()->setPassword($password);

            $this->entityManager->flush();

            $this->addFlash('notice', 'Votre mot de passe a été mis à jour.');

            return $this->redirectToRoute('app_login');

        }
        
        return $this->render('reset_password/update.html.twig', [
            'form' => $form->createView(),
        ]);
        
    }
}
