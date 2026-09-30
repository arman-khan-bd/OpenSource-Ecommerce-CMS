<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OptionRepository;
use App\Service\AuthService;
use App\Service\CacheFallbackService;
use App\Service\InertiaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseAdminController extends AbstractController
{
    protected AuthService $auth;
    protected InertiaService $inertia;
    protected OptionRepository $optionRepo;
    protected CacheFallbackService $cacheService;

    public function __construct(
        AuthService $auth,
        InertiaService $inertia,
        OptionRepository $optionRepo,
        CacheFallbackService $cacheService
    ) {
        $this->auth = $auth;
        $this->inertia = $inertia;
        $this->optionRepo = $optionRepo;
        $this->cacheService = $cacheService;
    }

    /**
     * Pre-render guard and shared state builder.
     */
    protected function renderAdmin(Request $request, string $component, array $props = []): Response
    {
        $user = $this->auth->getCurrentUser($request);
        if (!$user) {
            return new RedirectResponse('/admin/login');
        }

        // Share global admin state
        $this->inertia->share('auth', [
            'user' => $user->toArray(),
        ]);

        $this->inertia->share('site', [
            'title' => $this->optionRepo->get('site_title', 'OpenSource eCommerce CMS'),
            'tagline' => $this->optionRepo->get('site_tagline', 'Alternative to WordPress'),
            'description' => $this->optionRepo->get('site_description', 'The modern, lightweight, monolithic & headless alternative to WordPress.'),
            'active_theme' => $this->optionRepo->get('active_theme', 'default'),
            'url' => $this->optionRepo->get('site_url', $request->getSchemeAndHttpHost()),
        ]);

        $this->inertia->share('cacheStatus', $this->cacheService->getDriverInfo());

        // Flash messages
        $session = $request->getSession();
        $this->inertia->share('flash', [
            'success' => $session->getFlashBag()->get('success', []),
            'error' => $session->getFlashBag()->get('error', []),
            'info' => $session->getFlashBag()->get('info', []),
        ]);

        return $this->inertia->render($request, $component, $props);
    }
}
