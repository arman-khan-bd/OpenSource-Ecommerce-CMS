<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment as TwigEnvironment;

/**
 * Production-ready custom Inertia.js Monolithic Bridge service.
 * Delivers full Single-Page Application (SPA) reactivity with zero client-side routing drift.
 */
class InertiaService
{
    private TwigEnvironment $twig;
    private array $sharedProps = [];
    private string $version = '1.0.0';
    private string $rootView = 'app.html.twig';

    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    public function share(string $key, mixed $value): self
    {
        $this->sharedProps[$key] = $value;
        return $this;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function render(Request $request, string $component, array $props = []): Response
    {
        // Merge shared props with action props
        $allProps = array_merge($this->sharedProps, $props);

        // Handle partial reloads
        if ($request->headers->get('X-Inertia-Partial-Component') === $component && $request->headers->has('X-Inertia-Partial-Data')) {
            $only = explode(',', $request->headers->get('X-Inertia-Partial-Data'));
            $filtered = [];
            foreach ($only as $key) {
                $trimmed = trim($key);
                if (array_key_exists($trimmed, $allProps)) {
                    $filtered[$trimmed] = $allProps[$trimmed];
                }
            }
            $allProps = $filtered;
        }

        // Resolve lazy props or closures
        foreach ($allProps as $k => $v) {
            if (is_callable($v) && !is_string($v) && !is_array($v)) {
                $allProps[$k] = $v();
            }
        }

        $page = [
            'component' => $component,
            'props' => $allProps,
            'url' => $request->getRequestUri(),
            'version' => $this->version,
        ];

        // Inertia AJAX request
        if ($request->headers->get('X-Inertia') === 'true') {
            $response = new JsonResponse($page, Response::HTTP_OK);
            $response->headers->set('X-Inertia', 'true');
            $response->headers->set('Vary', 'X-Inertia');
            return $response;
        }

        // Full page initial SSR/HTML load
        $html = $this->twig->render($this->rootView, [
            'page' => $page,
        ]);

        return new Response($html, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Helper for Inertia external redirect (409 Conflict).
     */
    public function location(string $url): Response
    {
        return new Response('', Response::HTTP_CONFLICT, [
            'X-Inertia-Location' => $url,
        ]);
    }
}
