<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use GuzzleHttp\Client;
use Symfony\Bundle\SecurityBundle\Security;
use Tectalic\OpenAi\Authentication;
use Tectalic\OpenAi\Manager;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;

class ChatService
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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
