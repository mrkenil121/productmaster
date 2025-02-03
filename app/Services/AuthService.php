<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function signup(array $data)
    {
        $user = $this->userRepository->createUser($data);
        $token = $this->userRepository->createUserToken($user, 'authToken');

        return [
            'token' => $token,
            'name' => $user->name
        ];
    }

    public function login(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            throw new \Exception('Invalid credentials');
        }

        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user->is_active) {
            throw new \Exception('Account is inactive');
        }

        $token = $this->userRepository->createUserToken($user, 'authToken');

        return [
            'token' => $token,
            'name' => $user->name
        ];
    }

    public function logout()
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('No authenticated user found');
        }

        return $this->userRepository->revokeAllTokens($user);
    }
}