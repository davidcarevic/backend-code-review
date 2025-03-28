<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\MessageDTO;
use App\Message\SendMessage;
use App\Repository\MessageRepository;
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

        // Convert each Message entity to MessageDTO
        $messageDTOs = array_map(fn($message) => MessageDTO::fromEntity($message), $messageEntities);

        return new JsonResponse(['messages' => $messageDTOs], json: JSON_THROW_ON_ERROR);
    }

    #[Route('/messages/send', methods: ['POST'])] // Use POST instead of GET
    public function send(Request $request, MessageBusInterface $bus): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $text = $data['text'] ?? null;

        if (!$text) {
            return new JsonResponse(['error' => 'Text is required'], Response::HTTP_BAD_REQUEST);
        }

        $bus->dispatch(new SendMessage($text));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT); // No content needed for 204 response
    }
}
