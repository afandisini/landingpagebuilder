<?php
require_once __DIR__ . '/../Core/Database.php';

class PageVisit
{
    public static function logVisit(int $pageId, ?string $ip = null, ?string $userAgent = null, ?string $referrer = null, ?string $sessionId = null, ?string $userHash = null): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO page_visits (page_id, ip_address, user_agent, referrer, session_id, user_hash, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $pageId,
            $ip,
            $userAgent,
            $referrer,
            $sessionId,
            $userHash,
            date('Y-m-d H:i:s'),
        ]);
    }

    public static function getSummary(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT 
                COUNT(*) AS total_views,
                COUNT(DISTINCT page_id) AS total_pages_tracked,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_views
            FROM page_visits'
        );
        $summary = $stmt->fetch();
        return [
            'total_views' => (int)($summary['total_views'] ?? 0),
            'total_pages_tracked' => (int)($summary['total_pages_tracked'] ?? 0),
            'today_views' => (int)($summary['today_views'] ?? 0),
        ];
    }

    public static function getTotalsByPageIds(array $pageIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $pageIds), static fn($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT page_id, COUNT(*) AS total_views FROM page_visits WHERE page_id IN ($placeholders) GROUP BY page_id"
        );
        $stmt->execute($ids);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['page_id']] = (int)$row['total_views'];
        }
        return $result;
    }
}
