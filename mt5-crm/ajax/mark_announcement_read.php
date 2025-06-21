<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';

header('Content-Type: application/json');

$user_id = $_SESSION['member_id'] ?? 0;
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id > 0 && $announcement_id > 0) {
    $stmt = $pdo->prepare("INSERT INTO announcement_reads (announcement_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP");
    $stmt->execute([$announcement_id, $user_id]);
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error']);
}
