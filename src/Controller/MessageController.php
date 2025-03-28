<?php
declare(strict_types=1);

namespace App\Controller;

use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class MessageController extends AbstractController
{
    #[Route('/messages', methods: ['GET'])]
    public function list(Request $request, MessageRepository $messages): JsonResponse
    {
        $messageEntities = $messages->by($request);

        // Use array_map instead of manually looping through messages
        $messages = array_map(static fn($message) => [
            'uuid' => $message->getUuid(),
            'text' => $message->getText(),
            'status' => $message->getStatus(),
        ], $messageEntities);

        return new JsonResponse(['messages' => $messages], json: JSON_THROW_ON_ERROR); // JsonResponse class for better handling
    }

    #[Route('/messages/send', methods: ['POST'])] // Changed from GET to POST
    public function send(Request $request, MessageBusInterface $bus): JsonResponse
    {
        // Retrieve `text` from JSON body instead of query parameters
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $text = $data['text'] ?? null;

        if (!$text) {
            return new JsonResponse(['error' => 'Text is required'], Response::HTTP_BAD_REQUEST); // JsonResponse class for better handling
        }

        $bus->dispatch(new SendMessage($text));

        return new JsonResponse(['message' => 'Successfully sent'], Response::HTTP_NO_CONTENT); // JsonResponse class for better handling
    }
}
