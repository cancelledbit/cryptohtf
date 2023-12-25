<?php

namespace App\Service\EntityEventListener;

use App\Entity\User;
use App\Service\Email\UserRegistration;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'sendRegister', entity: User::class)]
class UserChangedNotifier
{
    public function __construct(private UserRegistration $registration)
    {
    }

    public function sendRegister(User $user, PostPersistEventArgs $args): void
    {
        $this->registration->send($user);
    }
}
