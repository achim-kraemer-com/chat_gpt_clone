<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page')]
    public function landingPage(): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $this->redirectToRoute('app_index');
        }

        return $this->redirectToRoute('app_login', [], 301);
    }
}