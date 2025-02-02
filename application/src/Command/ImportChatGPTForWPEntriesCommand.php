<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ChatService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

#[AsCommand(name: 'app:import_chat_gptfor_wpentries', description: 'Hello PhpStorm')]
class ImportChatGPTForWPEntriesCommand extends Command
{
    private ChatService $chatService;
    private ContainerBagInterface $params;

    public function __construct(ChatService $chatService, ContainerBagInterface $params, string $name = null)
    {
        $this->chatService = $chatService;
        $this->params = $params;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('prompt', InputArgument::REQUIRED, 'Prompt');
        $this->addArgument('category', InputArgument::REQUIRED, 'Kategory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prompt = $input->getArgument('prompt');
        $category = $input->getArgument('category');

        $question = 'Beschreibe mit maximal 500 Worten folgendes Sprichwort "'.$prompt.'". Bitte keine Einleitung dazu schrieben, sondern nur eine ausführliche Erklärung.';
        dump($question);
        $chatType = 'gpt-4-turbo';
        $resultArray = $this->chatService->getAnswer($question, $chatType, '', '');

        if (!\array_key_exists('answer', $resultArray)) {
            dump('FEHLER: answer nicht vorhanden!');
            dump($resultArray);

            return Command::FAILURE;
        }

        $content = '<!-- wp:paragraph -->'.$resultArray['answer'].'<!-- /wp:paragraph -->';

        $content = str_replace("\n", "<br>", $content);
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);

        $data = [
            'title' => $prompt,
            'content' => $content,
            'status' => 'publish',
        ];

        $categoryArray = [
            'Natur und Umwelt' => 97,
            'Arbeit und Fleiß' => 98,
            'Weisheit und Klugheit' => 99,
            'Freundschaft und Beziehungen' => 100,
            'Liebe und Ehe' => 101,
            'Gesundheit und Wohlbefinden' => 102,
            'Geld und Reichtum' => 103,
            'Glück und Pech' => 104,
            'Mut und Entschlossenheit' => 105,
            'Zeit und Geduld' => 106,
            'Erfahrung und Lernen' => 107,
            'Gerechtigkeit und Moral' => 108,
            'Macht und Politik' => 109,
            'Heimat und Herkunft' => 110,
            'Tiere und Tiervergleiche' => 111,
        ];

        $tagArray = [
            'Natur und Umwelt' => 112,
            'Arbeit und Fleiß' => 113,
            'Weisheit und Klugheit' => 114,
            'Freundschaft und Beziehungen' => 115,
            'Liebe und Ehe' => 116,
            'Gesundheit und Wohlbefinden' => 117,
            'Geld und Reichtum' => 118,
            'Glück und Pech' => 119,
            'Mut und Entschlossenheit' => 120,
            'Zeit und Geduld' => 121,
            'Erfahrung und Lernen' => 122,
            'Gerechtigkeit und Moral' => 123,
            'Macht und Politik' => 124,
            'Heimat und Herkunft' => 125,
            'Tiere und Tiervergleiche' => 126,
        ];

        dump($categoryArray[$category]);
        dump($tagArray[$category]);

        $urlString = '?title='.rawurlencode($data['title']).'&content='.rawurlencode($data['content']).'&status=publish&categories[0]=25&categories[1]='.$categoryArray[$category].'&tags[0]=26&tags[1]=27&tags[2]='.$tagArray[$category];

        $curlExec = "curl --location --globoff --request POST 'https://sprichwoerter-online.de/wp-json/wp/v2/posts".$urlString."' --header 'Content-Type: application/json; charset=utf-8' --header 'Authorization: Basic cm9vdF9pNmVxNzBzMjpqd25UIDk3em0gdjJJUiBCTDY0IFZuUWggSDJ0bA=='";

        dump($curlExec);

        exec($curlExec, $output, $return_var);

        echo "Ausgabe:\n";
        print_r($output);
        echo "Return Code: $return_var";

        return Command::SUCCESS;
    }
}
