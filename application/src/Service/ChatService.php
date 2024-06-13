<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatHistory;
use App\Entity\User;
use App\Repository\ChatHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatService
{
    private Security $security;
    private HttpClientInterface $httpClient;
    private ContainerBagInterface $params;
    private EntityManagerInterface $entityManager;
    private ChatHistoryRepository $chatHistoryRepository;

    public function __construct(
        Security $security,
        HttpClientInterface $httpClient,
        ContainerBagInterface $params,
        ChatHistoryRepository $chatHistoryRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->security = $security;
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->chatHistoryRepository = $chatHistoryRepository;
    }

    public function getAnswer(string $prompt, string $chatType, ?string $previousResponse, ?string $sessionId): array|\Exception
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }
        $unit = $user->getUnit();
        $chatGPTApiKey = $unit->getChatGptApiToken();
        if ($chatGPTApiKey === '') {
            return [];
        }

        $resultArray = [];
        $messages = [];

        $customerInstruction = $user->getCustomerInstruction();
        if ('' !== $customerInstruction && null !== $customerInstruction) {
            $messages[] = ['role' => 'system', 'content' => $customerInstruction];
        }

        if ('dall-e-3' !== $chatType) {
            $chatCount = $user->getChatCount() ?? 0;
            $chatHistories = $this->chatHistoryRepository->getByUserTypeAndLimit($user, $chatType, $chatCount);
            $chatHistoriesReverse = \array_reverse($chatHistories);
            foreach ($chatHistoriesReverse as $chatHistory) {
                $messages[] = ['role' => 'user', 'content' => $chatHistory->getRequest()];
                $messages[] = ['role' => 'assistant', 'content' => $chatHistory->getResponse()];
            }
        }

        $chatHistory = new ChatHistory();
        $chatHistory->setUser($user);
        $chatHistory->setRequest($prompt);
        $chatHistory->setModel($chatType);
        $chatHistory->setCreatedAt(new \DateTimeImmutable());

        $messages[] = ['role' => 'user', 'content' => $prompt];
        $content['model'] = $chatType;
        $content['messages'] = $messages;

        $chatGPTApiUrl = 'https://api.openai.com/v1/chat/completions';
        if ('dall-e-3' === $chatType) {
            $chatGPTApiUrl = 'https://api.openai.com/v1/images/generations';
            unset($content['messages']);
            $content['prompt'] = $prompt;
            $content['n'] = 1;
            $content['size'] = '1024x1024';
        }

        try {
            $response = $this->httpClient->request(
                Request::METHOD_POST,
                $chatGPTApiUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => "Bearer {$chatGPTApiKey}",
                    ],
                    'json' => $content,
                ]
            );
        } catch (TransportExceptionInterface $e) {
            $chatHistory->setResponse($e->getMessage());
            $this->entityManager->persist($chatHistory);
            $this->entityManager->flush();

            return $resultArray;
        }

        try {
            $responseArray = $response->toArray();
        } catch (\Exception $e) {
            $chatHistory->setResponse($e->getMessage());
            $this->entityManager->persist($chatHistory);
            $this->entityManager->flush();

            return [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }

        if ('dall-e-3' === $chatType && \array_key_exists('data', $responseArray)) {
            $imageUrl = $responseArray['data'][0]['url'];
            $urlPiece = \parse_url($imageUrl);
            $filename = \pathinfo($urlPiece['path'], PATHINFO_BASENAME);

            $imagePath = $this->params->get('image_path');
            $imageContent = \file_get_contents($imageUrl);
            \file_put_contents($imagePath.$filename, $imageContent);
            $resultArray['answer'] = $imagePath.$filename;
            $chatHistory->setResponse($imagePath.$filename);
        } else {
            $resultArray['answer'] = $responseArray['choices'][0]['message']['content'];
            $chatHistory->setResponse($resultArray['answer']);
        }
        $resultArray['session_id'] = $responseArray['sessionId'] ?? null;
        $resultArray['id'] = $responseArray['id'] ?? null;

        $this->entityManager->persist($chatHistory);
        $this->entityManager->flush();

        return $resultArray;
    }
}
