<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ใช้ฟังก์ชัน require_role() ตามเดิม
function require_role($allowed_roles = []) {
    if (!isset($_SESSION['member_role']) || !in_array($_SESSION['member_role'], $allowed_roles)) {
        header('Location: ../login.php');
        exit();
    }
}
?>
