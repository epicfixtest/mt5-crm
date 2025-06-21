<?php
require '../includes/connectdb.php';
// ควรเพิ่มการตรวจสอบสิทธิ์ Admin ที่นี่

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['order']) && is_array($data['order'])) {
    foreach ($data['order'] as $index => $id) {
        $stmt = $pdo->prepare("UPDATE promotions SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index, $id]);
    }
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'error']);
}