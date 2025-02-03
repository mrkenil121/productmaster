<?php
// app/Repositories/Interfaces/UserRepositoryInterface.php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function createUser(array $data);
    public function findByEmail(string $email);
    public function createUserToken($user, string $tokenName);
    public function revokeAllTokens($user);
}