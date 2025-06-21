<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['ids']) && is_array($data['ids'])) {
    foreach ($data['ids'] as $index => $id) {
        $stmt = $pdo->prepare("UPDATE live_links SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index, $id]);
    }
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error']);
}
