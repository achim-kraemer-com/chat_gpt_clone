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

    public function getAnswer(string $prompt): string
    {
        $chatGPTApiKey = $this->parameterBag->get('chat_gpt_api_key');


        $openaiClient = Manager::build(
            new Client(),
            new Authentication($chatGPTApiKey)
        );

        $response = $openaiClient->chatCompletions()->create(
            new CreateRequest([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ])
        )->toModel();

        return $response->choices[0]->message->content;
    }
}
