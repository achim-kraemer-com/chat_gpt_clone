<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use GuzzleHttp\Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tectalic\OpenAi\Authentication;
use Tectalic\OpenAi\Manager;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;

class ChatService
{
    private Security $security;
    private HttpClientInterface $httpClient;

    public function __construct(Security $security, HttpClientInterface $httpClient)
    {
        $this->security = $security;
        $this->httpClient = $httpClient;
    }

    public function getAnswer(string $prompt, ?string $previousResponse, ?string $sessionId, string $chatType): array
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
                        'Authorization' => "Bearer {$chatGPTApiKey}",
                    ],
                    'json' => $content,
                ]
            );
        } catch (TransportExceptionInterface $e) {
            dd($e->getMessage());
        }

        $responseArray = $response->toArray();

        if ('dall-e-3' === $chatType) {
            $resultArray['answer'] = $responseArray['data'][0]['url'];
        } else {
            $resultArray['answer'] = $responseArray['choices'][0]['message']['content'];
        }
        $resultArray['session_id'] = $responseArray['sessionId'] ?? null;
        $resultArray['id'] = $responseArray['id'] ?? null;

        return $resultArray;
    }
}
