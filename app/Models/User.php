<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE username = ? AND deleted_at IS NULL');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO users (email, username, password_hash, first_name, last_name, phone, avatar_path, bio)
            VALUES (:email, :username, :password_hash, :first_name, :last_name, :phone, :avatar_path, :bio)
        ');
        $stmt->execute([
            'email' => $data['email'],
            'username' => $data['username'],
            'password_hash' => $data['password_hash'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'avatar_path' => $data['avatar_path'] ?? null,
            'bio' => $data['bio'] ?? null
        ]);
        return (int) $db->lastInsertId();
    }

    public static function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_hash($password, $algo);
    }
}
