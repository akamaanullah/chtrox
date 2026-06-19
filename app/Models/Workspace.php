<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Workspace extends Model
{
    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM workspaces WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM workspaces WHERE slug = ? AND deleted_at IS NULL');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public static function isSlugExists(string $slug): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM workspaces WHERE slug = ?');
        $stmt->execute([$slug]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(array $data): int
    {
        $db = self::db();
        $stmt = $db->prepare('
            INSERT INTO workspaces (slug, name, industry, organization_type, email, phone, logo_path, plan, status)
            VALUES (:slug, :name, :industry, :organization_type, :email, :phone, :logo_path, :plan, :status)
        ');
        $stmt->execute([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'industry' => $data['industry'] ?? 'technology',
            'organization_type' => $data['organization_type'] ?? 'corporation',
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'plan' => $data['plan'] ?? 'free',
            'status' => $data['status'] ?? 'active'
        ]);
        return (int) $db->lastInsertId();
    }

    public static function createAddress(int $workspaceId, array $data): bool
    {
        $stmt = self::db()->prepare('
            INSERT INTO workspace_addresses (workspace_id, address_line1, city, state, country, postal_code)
            VALUES (:workspace_id, :address_line1, :city, :state, :country, :postal_code)
        ');
        return $stmt->execute([
            'workspace_id' => $workspaceId,
            'address_line1' => $data['address_line1'],
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'country' => $data['country'],
            'postal_code' => $data['postal_code']
        ]);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $slug = self::slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (self::isSlugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        return empty($text) ? 'workspace' : $text;
    }
}
