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

    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function createFromGoogle(string $name, string $email, string $googleId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, google_id, auth_provider, role)
             VALUES (:name, :email, :password_hash, :google_id, :auth_provider, :role)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'google_id' => $googleId,
            'auth_provider' => 'google',
            'role' => 'customer',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function linkGoogleAccount(int $id, string $googleId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET google_id = :google_id, auth_provider = :auth_provider WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'google_id' => $googleId,
            'auth_provider' => 'google',
        ]);
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

