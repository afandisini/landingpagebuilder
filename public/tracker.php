<?php
require_once __DIR__ . '/../src/Core/Logger.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Models/PageVisit.php';

Logger::register();

session_start();
$pageId = (int)($_GET['page_id'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$sessionId = session_id();
$userHash = substr(sha1(($ip ?? '') . '|' . ($userAgent ?? '')), 0, 40);

if ($pageId > 0) {
    PageVisit::logVisit($pageId, $ip, $userAgent, $referrer, $sessionId, $userHash);
}

header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
