<?php
declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Message\SendMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface; // Importing ClockInterface
use Symfony\Component\Uid\Uuid;

class SendMessageHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ClockInterface $clock; // ClockInterface mock
    private SendMessageHandler $handler;

    protected function setUp(): void
    {
        // Mock dependencies
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->clock = $this->createMock(ClockInterface::class); // ClockInterface mock

        // Instantiate handler with mocks
        $this->handler = new SendMessageHandler($this->entityManager, $this->clock);
    }

    public function testHandleSendsMessageSuccessfully(): void
    {
        // Arrange
        $messageText = 'Hello, PHPUnit!';
        $sendMessage = new SendMessage($messageText);

        // Simulate the current time with the Clock mock
        $currentDate = new \DateTimeImmutable('2025-03-28 12:00:00');
        $this->clock->method('now')->willReturn($currentDate);

        // Expect persist() and flush() to be called once
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($message) use ($messageText, $currentDate) {
                return $message instanceof Message 
                    && $message->getText() === $messageText
                    && $message->getStatus() === 'sent'
                    && $message->getCreatedAt() == $currentDate; // Checking the created timestamp
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->__invoke($sendMessage);
    }

    public function testHandleLogsAndThrowsOnFailure(): void
    {
        // Arrange
        $sendMessage = new SendMessage('Test failure');
        
        // Simulate an exception when persisting
        $this->entityManager
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));

        // Expect an exception to be thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to persist message: Database error');

        // Act
        $this->handler->__invoke($sendMessage);
    }
}
