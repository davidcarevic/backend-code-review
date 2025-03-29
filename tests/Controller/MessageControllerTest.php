<?php

declare(strict_types=1);

namespace Controller;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Integration tests for the MessageController.
 * Uses WebTestCase to simulate real HTTP requests.
 */
class MessageControllerTest extends WebTestCase
{
    use InteractsWithMessenger;

    /**
     * Inject a mock MessageRepository into the container with custom return data.
     *
     * @param Message[] $returnMessages
     */
    private function mockMessageRepository(array $returnMessages = []): void
    {
        $mockRepo = $this->createMock(MessageRepository::class);
        $mockRepo->method('by')->willReturn($returnMessages);

        static::getContainer()->set(MessageRepository::class, $mockRepo);
    }

    public function test_list(): void
    {
        $client = static::createClient();
        $this->mockMessageRepository([]); // Simulate no messages

        $client->request('GET', '/messages');

        $this->assertResponseIsSuccessful();

        $messages = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('messages', $messages);
    }

    public function test_list_returns_messages(): void
    {
        $client = static::createClient();

        $message1 = (new Message())
            ->setUuid(Uuid::v6()->toRfc4122())
            ->setText('Hello')
            ->setStatus('sent')
            ->setCreatedAt(new \DateTimeImmutable());

        $message2 = (new Message())
            ->setUuid(Uuid::v6()->toRfc4122())
            ->setText('World')
            ->setStatus('read')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->mockMessageRepository([$message1, $message2]);

        $client->request('GET', '/messages');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame([
            'messages' => [
                [
                    'uuid' => $message1->getUuid(),
                    'text' => 'Hello',
                    'status' => 'sent',
                ],
                [
                    'uuid' => $message2->getUuid(),
                    'text' => 'World',
                    'status' => 'read',
                ],
            ],
        ], $response);
    }

    public function test_list_returns_empty_array_when_no_messages(): void
    {
        $client = static::createClient();
        $this->mockMessageRepository([]); // No messages

        $client->request('GET', '/messages');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(['messages' => []], $response);
    }

    public function test_that_it_sends_a_message(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/messages/send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['text' => 'Hello World'])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->transport('sync')
            ->queue()
            ->assertContains(SendMessage::class);
    }
}
