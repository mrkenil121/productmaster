<?php
// app/Repositories/UserRepository.php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function createUser(array $data)
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->mobile_no = $data['mobile_no'];
        $user->save();
        
        return $user;
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function createUserToken($user, string $tokenName)
    {
        return $user->createToken($tokenName)->plainTextToken;
    }

    public function revokeAllTokens($user)
    {
        // Revoke all tokens for the user
        $user->tokens()->delete();
        return true;
    }
}