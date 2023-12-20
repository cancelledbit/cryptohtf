<?php

namespace App\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final class CommonProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function __invoke(LogRecord $record)
    {
        $userId = $this->security->getUser()?->getUserIdentifier();
        $record->extra['user'] = $userId;
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            return $record;
        }
        if (!$session->isStarted()) {
            return $record;
        }

        $sessionId = substr($session->getId(), 0, 8) ?: '????????';

        $record->extra['token'] = $sessionId.'-'.substr(uniqid('', true), -8);

        return $record;
    }
}
