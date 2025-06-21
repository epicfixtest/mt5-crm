<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$member_id = $_SESSION['member_id'] ?? 0;
$version_code = $_GET['version'] ?? '';

if ($member_id && $version_code) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO version_updates (member_id, version_code, updated_at) VALUES (?, ?, NOW())");
    $stmt->execute([$member_id, $version_code]);
}
