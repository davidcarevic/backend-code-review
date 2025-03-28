<?php
declare(strict_types=1);

namespace App\Message;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable; // Instead of DateTime
use Psr\Clock\ClockInterface; // For testable timestamps

#[AsMessageHandler]
// Handles sending and persisting a message.
class SendMessageHandler
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ClockInterface $clock // Injected for testability
    ) {}

    public function __invoke(SendMessage $sendMessage): void
    {
        try {
            $message = new Message();
            $message->setUuid(Uuid::v6()->toRfc4122());
            $message->setText($sendMessage->text);
            $message->setStatus('sent');
            $message->setCreatedAt(new DateTimeImmutable($this->clock->now()->format('Y-m-d H:i:s')));

            $this->manager->persist($message);
            $this->manager->flush();
        } catch (\Exception $e) {
            // Logging could be added if needed
            throw new \RuntimeException('Failed to persist message: ' . $e->getMessage(), 0, $e);
        }
    }
}