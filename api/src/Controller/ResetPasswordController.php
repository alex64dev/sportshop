<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\AdminMailer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        AdminMailer $adminMailer,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->getString('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken(hash('sha256', $token));
                $user->setResetTokenExpiresAt(new DateTimeImmutable('+1 hour'));
                $entityManager->flush();

                $adminMailer->sendPasswordResetEmail($user, $token);
            }

            $this->addFlash('success', 'Si cette adresse existe, un email de réinitialisation a été envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_request.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $hashedToken = hash('sha256', $token);
        $user = $userRepository->findOneBy(['resetToken' => $hashedToken]);

        if (!$user || $user->getResetTokenExpiresAt() < new DateTimeImmutable()) {
            $this->addFlash('danger', 'Ce lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_forgot_password_request');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->getString('password');
            $passwordConfirm = $request->request->getString('password_confirm');

            if ($password !== $passwordConfirm) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
