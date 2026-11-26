<?php

namespace App;

use PDO;

class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1'
        );

        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email FROM users WHERE id = :id LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
        );

        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}