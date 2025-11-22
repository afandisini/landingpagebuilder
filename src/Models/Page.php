<?php
require_once __DIR__ . '/../Core/Database.php';

class Page
{
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM pages ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $page = $stmt->fetch();
        return $page ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $page = $stmt->fetch();
        return $page ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'INSERT INTO pages (
                user_id, title, slug, status, html_content,
                shopee_link, tokped_link, fb_link, ig_link,
                tiktok_link, x_link, corporate, publisher, whatsapp, telegram,
                template_id, order_type, cta_label, cta_url, product_config,
                published_path, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['slug'],
            $data['status'],
            $data['html_content'],
            $data['shopee_link'] ?? null,
            $data['tokped_link'] ?? null,
            $data['fb_link'] ?? null,
            $data['ig_link'] ?? null,
            $data['tiktok_link'] ?? null,
            $data['x_link'] ?? null,
            $data['corporate'] ?? null,
            $data['publisher'] ?? null,
            $data['whatsapp'] ?? null,
            $data['telegram'] ?? null,
            $data['template_id'] ?? null,
            $data['order_type'] ?? 'none',
            $data['cta_label'] ?? null,
            $data['cta_url'] ?? null,
            $data['product_config'] ?? null,
            $data['published_path'] ?? null,
            $data['published_at'] ?? null,
            $now,
            $now,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE pages SET
                title = ?, slug = ?, html_content = ?, status = ?,
                shopee_link = ?, tokped_link = ?, fb_link = ?, ig_link = ?,
                tiktok_link = ?, x_link = ?, corporate = ?, publisher = ?, whatsapp = ?, telegram = ?,
                template_id = ?, order_type = ?, cta_label = ?, cta_url = ?, product_config = ?,
                published_path = ?, published_at = ?, updated_at = ?
             WHERE id = ?'
        );
        $now = date('Y-m-d H:i:s');
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['html_content'],
            $data['status'] ?? 'draft',
            $data['shopee_link'] ?? null,
            $data['tokped_link'] ?? null,
            $data['fb_link'] ?? null,
            $data['ig_link'] ?? null,
            $data['tiktok_link'] ?? null,
            $data['x_link'] ?? null,
            $data['corporate'] ?? null,
            $data['publisher'] ?? null,
            $data['whatsapp'] ?? null,
            $data['telegram'] ?? null,
            $data['template_id'] ?? null,
            $data['order_type'] ?? 'none',
            $data['cta_label'] ?? null,
            $data['cta_url'] ?? null,
            $data['product_config'] ?? null,
            $data['published_path'] ?? null,
            $data['published_at'] ?? null,
            $now,
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM pages WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
