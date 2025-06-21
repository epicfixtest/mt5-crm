<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$member_id = $_SESSION['member_id'];
$preset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($preset_id <= 0) {
    die('Invalid preset ID.');
}

// 1. ดึงข้อมูล Preset เพื่อหาว่ามันเป็นของ Bot ตัวไหน (redeem_item_id)
$stmt_preset = $pdo->prepare("SELECT * FROM bot_presets WHERE id = ?");
$stmt_preset->execute([$preset_id]);
$preset = $stmt_preset->fetch();

if (!$preset) {
    die('Preset not found.');
}

$required_bot_id = $preset['redeem_item_id'];

// 2. ตรวจสอบว่าลูกค้าเคยซื้อ Bot ตัวนั้นหรือไม่
$stmt_check = $pdo->prepare("SELECT 1 FROM redeem_history WHERE member_id = ? AND redeem_item_id = ? LIMIT 1");
$stmt_check->execute([$member_id, $required_bot_id]);

if ($stmt_check->fetchColumn()) {
    // 3. ถ้ามีสิทธิ์ ให้ส่งไฟล์
    $file_path = $preset['file_path'];
    if (file_exists($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        die('File not found on server.');
    }
} else {
    // 4. ถ้าไม่มีสิทธิ์
    die('Access Denied. You do not own the required bot for this preset.');
}