<?php

namespace App\Controller;
use App\Entity\ChatHistory;
use App\Repository\ChatHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminChatHistoryController extends AbstractController
{
    #[Route('/admin/chat-history', name:'admin_chat_history')]
    public function new(Request $request, EntityManagerInterface $entityManager, ChatHistoryRepository $adminPageRepository ): Response
    {
        $user = $this->getUser();
        $unitId = $user->getUnit()->getId();
        $histories = $entityManager->getRepository(ChatHistory::class)->getHistoryFromUnit($unitId);


            return $this->render('chatHistory/index.html.twig',[
                'histories' => $histories,

        ]);
        
    }
} 
