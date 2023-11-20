<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Tectalic\OpenAi\Authentication;
use Tectalic\OpenAi\Manager;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;

class ChatService
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function getAnswer(string $prompt, ?string $previousResponse, ?string $sessionId, string $chatType): array
    {
        $chatGPTApiKey = $this->parameterBag->get('chat_gpt_api_key');

        $resultArray = [];

        $openaiClient = Manager::build(
            new Client(),
            new Authentication($chatGPTApiKey)
        );

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

        $response = $openaiClient->chatCompletions()->create(
            new CreateRequest([
                'model' => $chatType,
                'messages' => $messages,
            ])
        )->toModel();

        $resultArray['answer'] = $response->choices[0]->message->content;
        $resultArray['session_id'] = $response->sessionId ?? null;
        $resultArray['id'] = $response->id ?? null;

        return $resultArray;
    }
}
