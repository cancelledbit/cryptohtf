<?php

namespace App\Service\Vault\Event\Subscriber;

use App\Service\Vault\Event\Contract\VaultEvent;
use App\Service\Vault\Event\VaultLockedEvent;
use App\Service\Vault\Event\VaultRemovedEvent;
use App\Service\Vault\Event\VaultUnlockedEvent;
use App\Service\Vault\Event\VaultUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

class NotifierSubscriber implements EventSubscriberInterface
{
    /**
     * @param array<class-string, list<string>> $recipients
     */
    public function __construct(private ChatterInterface $chatter, private array $recipients)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            VaultUpdatedEvent::getName() => 'onVaultUpdated',
            VaultRemovedEvent::getName() => 'onVaultRemoved',
            VaultLockedEvent::getName() => 'onVaultLocked',
            VaultUnlockedEvent::getName() => 'onVaultUnlocked',
        ];
    }

    public function onVaultUpdated(VaultUpdatedEvent $event): void
    {
        $pass = $event->getPass();
        $username = $event->getVault()->getOwner()->getName();
        $action = $event->isNew() ? 'Создано' : 'Обновлено';
        $message = (new ChatMessage("{$action} хранилище для {$username} - ключ {$pass}"))->transport('telegram');
        $this->send($message, $event);
    }

    public function onVaultRemoved(VaultRemovedEvent $event): void
    {
        $username = $event->getVault()->getOwner()->getName();
        $message = (new ChatMessage("Удалено хранилище для {$username}"))->transport('telegram');
        $this->send($message, $event);
    }

    public function onVaultLocked(VaultLockedEvent $event): void
    {
        $username = $event->getVault()->getOwner()->getName();
        $message = (new ChatMessage("Закрыто хранилище для {$username}"))->transport('telegram');
        $this->send($message, $event);
    }

    public function onVaultUnlocked(VaultUnlockedEvent $event): void
    {
        $username = $event->getVault()->getOwner()->getName();
        $message = (new ChatMessage("Открыто хранилище для {$username}"))->transport('telegram');
        $this->send($message, $event);
    }

    private function send(ChatMessage $message, VaultEvent $event): void
    {
        $recipients = $this->getRecipients($event);
        if ($recipients) {
            foreach ($recipients as $recipient) {
                $options = new TelegramOptions();
                $options->chatId($recipient);
                $message->options($options);
                $this->chatter->send($message);
            }

            return;
        }
        $this->chatter->send($message);
    }

    /**
     * @return string[]|null
     */
    private function getRecipients(VaultEvent $event): ?array
    {
        if (array_key_exists($event::class, $this->recipients)) {
            return $this->recipients[$event::class];
        }

        return null;
    }
}
