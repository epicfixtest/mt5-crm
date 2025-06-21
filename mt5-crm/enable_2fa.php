<?php
require 'includes/connectdb.php';
require 'includes/require_login.php';
require_once 'GoogleAuthenticator.php';

$ga = new PHPGangsta_GoogleAuthenticator();
$secret = $ga->createSecret();

$username = $_SESSION['member_name'] ?? 'user';
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode("otpauth://totp/MT5-CRM:$username?secret=$secret");


// Save to DB
$stmt = $pdo->prepare("UPDATE members SET twofa_secret = ?, is_twofa_enabled = 1 WHERE id = ?");
$stmt->execute([$secret, $_SESSION['member_id']]);

if ($ga->verifyCode($secret, $otp, 2)) {
    $stmt = $pdo->prepare("UPDATE members SET is_twofa_enabled = 1, twofa_secret = ? WHERE id = ?");
    $stmt->execute([$secret, $member_id]);

    // ✅ อัปเดต session เพื่อไม่ให้ระบบเข้าใจว่ายังไม่เปิด
    $_SESSION['is_twofa_enabled'] = 1;

    $success = "เปิดใช้งาน 2FA เรียบร้อยแล้ว";
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เปิดใช้งาน 2FA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="p-6 max-w-lg mx-auto bg-white rounded-xl shadow-md space-y-4 mt-10">
        <h2 class="text-xl font-bold text-center">ตั้งค่า 2FA</h2>
        <p class="text-center">สแกน QR ด้านล่างด้วย Google Authenticator หรือ Authy</p>
        <img src="<?= $qrCodeUrl ?>" class="mx-auto" alt="QR Code">
        <p class="text-center text-sm text-gray-500">หรือใช้ Secret: <code><?= $secret ?></code></p>
        <div class="text-center mt-4">
            <a href="index.php" class="text-blue-600 underline">กลับหน้าแรก</a>
        </div>
    </div>
</body>
</html>
