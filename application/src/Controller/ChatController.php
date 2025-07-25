<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChatType;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    #[Route('/chat', name: 'app_index', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $form = $this->createForm(ChatType::class);
        $user = $this->getUser();
        $unit = $user->getUnit();
        $users = $unit->getUsers();
        $userArray = [];
        foreach ($users as $userValue) {
            $userArray[$userValue->getId()]= $userValue->getEmail();
        }
        $chatGptApiToken = $unit->getChatGptApiToken();
        $chatCount = $user->getChatCount();
        $customerInstruction = $user->getCustomerInstruction();

        return $this->render('chat/index.html.twig', [
            'form' => $form->createView(),
            'chatGptApiToken' => $chatGptApiToken,
            'chatCount' => $chatCount,
            'customerInstruction' => $customerInstruction,
            'users' => $userArray,
        ]);
    }

    #[Route('/get-chat-answer', name: 'app_get_chat_answer', methods: ['POST'])]
    public function getAnswer(Request $request, ChatService $chatService): JsonResponse
    {
        try {
            $prompt = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $answer = $chatService->getAnswer($prompt['prompt'], $prompt['chatType'], $prompt['previousResponse'], $prompt['sessionId']);
            if ($answer instanceof \Exception) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Ein Fehler ist aufgetreten'
                ], 500);
            }
            $answer['question'] = $prompt['prompt'];
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Ein Fehler ist aufgetreten'
            ], 500);
        }

        return new JsonResponse($answer);
    }

    #[Route('delete-user', name: 'app_delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $returnValue = true;
        try {
            $settings = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $userId = (int)$settings['userId'];
            $user = $userRepository->findOneBy(['id' => $userId]);
            $entityManager->remove($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            $returnValue = false;
        }

        return new JsonResponse($returnValue);
    }

    #[Route('/save-settings', name: 'app_save_settings', methods: ['POST'])]
    public function saveSettings(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $unit = $user->getUnit();
        $chatGptApiToken = $unit->getChatGptApiToken();
        $settings = \json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $chatCount = (int) $settings['chatCount'];
        $user->setChatCount($chatCount);
        $entityManager->persist($unit);
        $entityManager->flush();
        if ($chatGptApiToken !== $settings['chatGptApiToken']) {
            $unit->setChatGptApiToken($settings['chatGptApiToken']);
            $entityManager->persist($user);
            $entityManager->flush();
        }
        if ($settings['newPasswordOne'] !== '') {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $settings['newPasswordOne']
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();
        }
        if ($settings['newUserEmail']) {
            $newUser = new User();
            $newUser->setEmail($settings['newUserEmail']);
            $newUser->setUnit($user->getUnit());
            $newUser->setIsVerified(true);
            $password = $this->generateStrongPassword(12);
            $newUser->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $password
                )
            );
            $roles = ['ROLE_USER'];
            if ($settings['isAdmin']) {
                $roles[] = 'ROLE_ADMIN';
            }
            $newUser->setRoles($roles);
            $entityManager->persist($newUser);
            $entityManager->flush();

            $this->sendPasswordEmail($settings['newUserEmail'], $password);
        }
        if ($settings['customerInstruction']) {
            $user->setCustomerInstruction($settings['customerInstruction']);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return new JsonResponse(true);
    }

    private function generateStrongPassword(int $length = 12): string{
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = random_int(0, strlen($chars) - 1);
            $password .= $chars[$randomIndex];
        }

        return $password;
    }

    private function sendPasswordEmail(string $emailAddress, string $password): void {
        $email = (new TemplatedEmail())
            ->from('chat@notifications.mso-digital.de')
            ->to($emailAddress)
            ->subject('Deine Zugangsdaten für das prompt privacy portal sind da!')
            ->htmlTemplate('emails/password.html.twig')
            ->context([
                'user' => [
                    'password' => $password,
                ],
            ]);

        $this->mailer->send($email);
    }
}
