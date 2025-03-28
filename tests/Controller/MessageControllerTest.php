<?php

declare(strict_types=1);

namespace Controller;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;
use Symfony\Component\HttpFoundation\Response;

class MessageControllerTest extends WebTestCase
{
    use InteractsWithMessenger;

    private $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the MessageRepository dependency
        $this->messageRepository = $this->createMock(MessageRepository::class);
    }

    /**
     * Test that the /messages endpoint returns a successful response.
     */
    public function test_list(): void
    {
        $client = static::createClient();
        $client->request('GET', '/messages'); // Make sure this matches your controller's route

        $this->assertResponseIsSuccessful();

        // Decode the response and check its structure
        $response = $client->getResponse()->getContent();
        $messages = json_decode($response, true);

        // Assert that the response is an array and contains the messages key
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('messages', $messages);
    }

    /**
     * Test that the /messages endpoint returns messages when they exist.
     */
    public function test_list_returns_messages(): void
    {
        $client = static::createClient();

        // Mock some messages to return from the repository
        $message1 = new Message();
        $message1->setText('Hello');
        
        $message2 = new Message();
        $message2->setText('World');

        // Mock the `by` method instead of `findAll` to match the controller logic
        $this->messageRepository
            ->method('by') // Change from `findAll` to `by`
            ->willReturn([$message1, $message2]);

        // Call the list endpoint
        $client->request('GET', '/messages');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'messages' => [
                ['text' => 'Hello'],
                ['text' => 'World'],
            ],
        ]);
    }

    /**
     * Test that the /messages endpoint returns an empty array when no messages exist.
     */
    public function test_list_returns_empty_array_when_no_messages(): void
    {
        $client = static::createClient();

        // Mock an empty response from the repository
        $this->messageRepository
            ->method('by') // Mock `by` method
            ->willReturn([]);

        // Call the list endpoint
        $client->request('GET', '/messages');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'messages' => [],
        ]);
    }

    /**
     * Test sending a message.
     */
    public function test_that_it_sends_a_message(): void
    {
        $client = static::createClient();

        // Send a POST request with a JSON body
        $client->request('POST', '/messages/send', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'text' => 'Hello World',
        ]));

        // Assert the response is successful (204 No Content)
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Check if the message was dispatched to the correct handler
        $this->transport('sync')
            ->queue()
            ->assertContains(SendMessage::class, 1); // Ensure SendMessage was dispatched
    }
}
