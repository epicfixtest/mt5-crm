<?php
function check_page_permission($page_key) {
    require_once __DIR__ . '/includes/connectdb.php'; // ป้องกันหาย

    if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


    if (!isset($_SESSION['member_id'])) {
        header("Location: login.php");
        exit();
    }

    global $pdo;

    $stmt = $pdo->prepare("SELECT role FROM members WHERE id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    $user = $stmt->fetch();

    // SuperAdmin ได้ทุกหน้า
    if ($user && $user['role'] === 'SuperAdmin') {
        return;
    }

    $stmt = $pdo->prepare("SELECT 1 FROM member_permissions WHERE member_id = ? AND page_key = ?");
    $stmt->execute([$_SESSION['member_id'], $page_key]);
    if (!$stmt->fetchColumn()) {
        echo "<div class='text-red-500 text-center p-10'>🚫 คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";

        exit();
    }
}
