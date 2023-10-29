<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ChatType;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    #[Route('/', name: 'app_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ChatService $chatService): Response
    {
        $answer = null;
        $form = $this->createForm(ChatType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $prompt = $form->get('prompt')->getData();

            $answer = $chatService->getAnswer($prompt);
        }

        return $this->render('chat/index.html.twig', [
            'form' => $form->createView(),
            'answer' => $answer,
        ]);
    }
}
