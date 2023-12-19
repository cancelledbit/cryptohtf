<?php

namespace App\Service\EventListener;

use App\Entity\User;
use App\Service\Email\Registration;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'sendRegister', entity: User::class)]
class UserChangedNotifier {
    public function __construct(private Registration $registration) {
    }

    public function sendRegister(User $user, PostPersistEventArgs $args) {
        $this->registration->send($user);
    }
}