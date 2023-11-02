<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ChatType;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/chat', name: 'app_index', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $form = $this->createForm(ChatType::class);

        return $this->render('chat/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/get-chat-answer', name: 'app_get_chat_answer', methods: ['POST'])]
    public function getAnswer(Request $request, ChatService $chatService): JsonResponse
    {
        $prompt = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $answer['question'] = $prompt['prompt'];
        $answer['answer'] = $chatService->getAnswer($prompt['prompt']);

        return new JsonResponse($answer);
    }
}
