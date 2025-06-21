<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$item_id = $_GET['id'] ?? null;
if (!$item_id || !is_numeric($item_id)) {
    die('รหัสไฟล์ไม่ถูกต้อง');
}

// ตรวจสอบว่าผู้ใช้เคยแลกไฟล์นี้แล้วหรือไม่
$stmt = $pdo->prepare("SELECT i.file_path FROM redeem_history h 
    JOIN redeem_items i ON h.redeem_item_id = i.id 
    WHERE h.member_id = ? AND h.redeem_item_id = ?");
$stmt->execute([$_SESSION['member_id'], $item_id]);
$file_path = $stmt->fetchColumn();

if (!$file_path) {
    die('คุณยังไม่ได้แลกรายการนี้');
}

// ✅ บันทึก log
$stmt = $pdo->prepare("INSERT INTO download_logs (member_id, file_name) VALUES (?, ?)");
$stmt->execute([$_SESSION['member_id'], $file_path]);

// ✅ ส่งผู้ใช้ไปยังไฟล์
header("Location: $file_path");
exit();
