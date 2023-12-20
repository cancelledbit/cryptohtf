<?php

namespace App\Service\Email;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class Registration
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function send(User $user): void
    {
        $email = new Email();
        $email->to($user->getEmail());
        $email->subject('Регистрация в vault');
        $email->html(
            <<<HTML
                <h3>Добро пожаловать в <a href="http://vault.alplight.ru">Vault</a></h3><br>
                <b>Логин:</b> {$user->getEmail()}<br>
                <b>Пароль:</b> {$user->getPlainPassword()}<br>
            HTML
        );
        $this->mailer->send($email);
    }
}
