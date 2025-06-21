<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เช็กว่ามี session หรือยัง ถ้าไม่มีให้ redirect
if (!isset($_SESSION['member_id'])) {
    header('Location: ../login.php');
    exit();
}
?>
