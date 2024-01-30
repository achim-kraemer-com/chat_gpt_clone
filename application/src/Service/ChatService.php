<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
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

    public function __construct(Security $security, HttpClientInterface $httpClient, ContainerBagInterface $params)
    {
        $this->security = $security;
        $this->httpClient = $httpClient;
        $this->params = $params;
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

        try {
            $previousResponseArray = \json_decode($previousResponse ?? '[{}]', true);
        } catch (\Exception $exception) {
            dd($exception);
        }

        if (\is_array($previousResponseArray)) {
            foreach ($previousResponseArray as $previousResponseItem) {
                if (\array_key_exists('role', $previousResponseItem)) {
                    $messages[] = ['role' => $previousResponseItem['role'], 'content' => $previousResponseItem['content']];
                }
            }
        }

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
            return $resultArray;
        }

        try {
            $responseArray = $response->toArray();
        } catch (\Exception $e) {
            return new \Exception($e->getMessage(), $e->getCode());
        }

        if ('dall-e-3' === $chatType && \array_key_exists('data', $responseArray)) {
            $imageUrl = $responseArray['data'][0]['url'];
            $urlPiece = \parse_url($imageUrl);
            $filename = \pathinfo($urlPiece['path'], PATHINFO_BASENAME);

            $imagePath = $this->params->get('image_path');
            $imageContent = \file_get_contents($imageUrl);
            \file_put_contents($imagePath.$filename, $imageContent);
            $resultArray['answer'] = $imagePath.$filename;
        } else {
            $resultArray['answer'] = $responseArray['choices'][0]['message']['content'];
        }
        $resultArray['session_id'] = $responseArray['sessionId'] ?? null;
        $resultArray['id'] = $responseArray['id'] ?? null;

        return $resultArray;
    }
}
