<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class AdminMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        #[Autowire('%env(MAIL_FROM)%')]
        private string $mailFrom,
    ) {
    }

    public function sendNewAdminEmail(User $user, string $generatedPassword): void
    {
        $html = $this->twig->render('emails/new_admin.html.twig', [
            'userEmail' => $user->getEmail(),
            'generatedPassword' => $generatedPassword,
        ]);

        $email = (new Email())
            ->from($this->mailFrom)
            ->to((string) $user->getEmail())
            ->subject('Création de votre compte administrateur — SportShop')
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user, string $resetToken): void
    {
        $html = $this->twig->render('emails/password_reset.html.twig', [
            'user' => $user,
            'resetToken' => $resetToken,
        ]);

        $email = (new Email())
            ->from($this->mailFrom)
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe — SportShop')
            ->html($html);

        $this->mailer->send($email);
    }
}
