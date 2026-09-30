<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private AuthService $auth;
    private InertiaService $inertia;
    private OptionRepository $optionRepo;

    public function __construct(AuthService $auth, InertiaService $inertia, OptionRepository $optionRepo)
    {
        $this->auth = $auth;
        $this->inertia = $inertia;
        $this->optionRepo = $optionRepo;
    }

    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        // If already logged in, go to dashboard
        if ($this->auth->isAuthenticated($request)) {
            return new RedirectResponse('/admin/dashboard');
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?: $request->request->all();
            $identifier = $data['identifier'] ?? '';
            $password = $data['password'] ?? '';

            $user = $this->auth->login($request, $identifier, $password);

            if ($user) {
                return new RedirectResponse('/admin/dashboard');
            }

            return $this->inertia->render($request, 'Auth/Login', [
                'siteTitle' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
                'siteDescription' => $this->optionRepo->get('site_description', 'Alternative to WordPress'),
                'error' => 'Invalid email/username or password. Please try again.',
            ]);
        }

        return $this->inertia->render($request, 'Auth/Login', [
            'siteTitle' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
            'siteDescription' => $this->optionRepo->get('site_description', 'Alternative to WordPress'),
            'error' => null,
        ]);
    }

    #[Route('/admin/logout', name: 'admin_logout', methods: ['POST', 'GET'])]
    public function logout(Request $request): Response
    {
        $this->auth->logout($request);
        return new RedirectResponse('/admin/login');
    }
}
