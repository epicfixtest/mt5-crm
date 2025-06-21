<?php
require '../includes/connectdb.php';
$stmt = $pdo->query("SELECT COUNT(*) FROM user_online_logs WHERE last_active >= NOW() - INTERVAL 5 MINUTE");
echo $stmt->fetchColumn();
