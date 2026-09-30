<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

class AuthService
{
    private UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function login(Request $request, string $identifier, string $password): ?User
    {
        $session = $request->getSession();
        $user = str_contains($identifier, '@') 
            ? $this->userRepo->findByEmail($identifier)
            : $this->userRepo->findByUsername($identifier);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return null;
        }

        if ($user->getStatus() !== 'active') {
            return null;
        }

        // Store user ID in session
        $session->set('auth_user_id', $user->getId());
        $session->set('auth_user_role', $user->getRole());
        $session->migrate(true); // Regenerate session ID for security

        return $user;
    }

    public function logout(Request $request): void
    {
        $session = $request->getSession();
        $session->remove('auth_user_id');
        $session->remove('auth_user_role');
        $session->invalidate();
    }

    public function getCurrentUser(Request $request): ?User
    {
        $session = $request->getSession();
        $userId = $session->get('auth_user_id');
        if (!$userId) {
            return null;
        }

        return $this->userRepo->findById((int)$userId);
    }

    public function isAuthenticated(Request $request): bool
    {
        return $this->getCurrentUser($request) !== null;
    }

    public function hasRole(Request $request, string $role): bool
    {
        $user = $this->getCurrentUser($request);
        if (!$user) {
            return false;
        }

        if ($user->getRole() === 'admin') {
            return true; // Admin has all permissions
        }

        return $user->getRole() === $role;
    }
}
