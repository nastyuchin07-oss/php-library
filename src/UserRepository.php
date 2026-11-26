<?php

namespace App;

class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password, bg_color, text_color
             FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, email, bg_color, text_color, remember_token_hash
             FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $name, string $email, string $passwordHash, string $bg, string $text): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (name, email, password, bg_color, text_color)
             VALUES (:name, :email, :password, :bg, :text)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'bg' => $bg,
            'text' => $text,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateSettings(int $id, string $bg, string $text): void
    {
        Database::connection()
            ->prepare('UPDATE users SET bg_color = :bg, text_color = :text WHERE id = :id')
            ->execute(['bg' => $bg, 'text' => $text, 'id' => $id]);
    }

    public function saveRememberToken(int $id, ?string $hash): void
    {
        Database::connection()
            ->prepare('UPDATE users SET remember_token_hash = :hash WHERE id = :id')
            ->execute(['hash' => $hash, 'id' => $id]);
    }
}