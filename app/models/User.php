<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public function create(string $name, string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );

        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'customer',
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'id' => $userId]);
        return (bool) $stmt->fetch();
    }

    public function updateProfile(int $id, string $name, string $email, string $phone, string $address): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 phone = :phone,
                 address = :address
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
        ]);
    }
}

