<?php
require_once __DIR__ . '/../Core/Database.php';

class Template
{
    // Retrieve all templates so admin can choose from the catalog.
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM templates ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    // Fetch a specific template by its identifier.
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM templates WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $template = $stmt->fetch();
        return $template ?: null;
    }

    // Look up a template using the unique template key.
    public static function findByKey(string $key): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM templates WHERE template_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $template = $stmt->fetch();
        return $template ?: null;
    }
}
