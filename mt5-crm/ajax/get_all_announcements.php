<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';

header('Content-Type: application/json');

$user_id = $_SESSION['member_id'];

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.message, a.created_at,
           CASE WHEN r.id IS NULL THEN 0 ELSE 1 END AS is_read
    FROM announcements a
    LEFT JOIN announcement_reads r ON a.id = r.announcement_id AND r.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
