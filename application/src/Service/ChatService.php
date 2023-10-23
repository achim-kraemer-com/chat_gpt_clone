<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatService
{
    private ParameterBagInterface $parameterBag;
    private HttpClientInterface $httpClient;

    public function __construct(ParameterBagInterface $parameterBag, HttpClientInterface $httpClient)
    {
        $this->parameterBag = $parameterBag;
        $this->httpClient = $httpClient;
    }

    public function getAnswer(string $prompt): string
    {
        $chatGPTApiUrl = $this->parameterBag->get('chat_gpt_api_url');
        $chatGPTApiKey = $this->parameterBag->get('chat_gpt_api_key');

        $response = $this->httpClient->request(
            Request::METHOD_POST,
            $chatGPTApiUrl,
            [
                'headers' => [
                    'Authorization' => "Bearer {$chatGPTApiKey}",
                ],
                'json' => [
                    'prompt' => $prompt,
                    'max_tokens' => 100,
                    'temperature' => 0.7,
                    'model' => 'gpt-3.5-turbo',
                ],
            ]
        );

        $responseData = $response->toArray();

        return $responseData['choices'][0]['text'];
    }
}
