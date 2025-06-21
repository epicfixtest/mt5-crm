<?php
require 'includes/connectdb.php';
require_once 'GoogleAuthenticator.php';
session_start();

if (!isset($_SESSION['pending_2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$ga = new PHPGangsta_GoogleAuthenticator();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $userId = $_SESSION['pending_2fa_user_id'];

    $stmt = $pdo->prepare("SELECT twofa_secret FROM members WHERE id = ?");
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();

    if ($ga->verifyCode($secret, $otp, 2)) {
    // ดึงข้อมูลสมาชิก
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // เซ็ต session ให้ครบ
    $_SESSION['member_id'] = $user['id'];
    $_SESSION['member_name'] = $user['name'];
    $_SESSION['member_role'] = $user['role'];
    $_SESSION['is_twofa_enabled'] = 1;
    $_SESSION['meta_api_account_id'] = $user['meta_api_account_id'];

    unset($_SESSION['pending_2fa_user_id']);

    header("Location: index.php");
    exit();
}
 else {
        $error = "รหัส OTP ไม่ถูกต้อง กรุณาลองอีกครั้ง";
        }
    }
?>
<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันตัวตนสองขั้นตอน - MT5 CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #111827; /* Dark Gray */
            background-image: radial-gradient(circle at 1px 1px, #374151 1px, transparent 0);
            background-size: 2rem 2rem;
        }
        .form-container {
            background-color: rgba(31, 41, 55, 0.8); /* Semi-transparent darker gray */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-input {
            background-color: #374151; /* Gray-700 */
            border: 1px solid #4b5563; /* Gray-600 */
            color: #e5e7eb; /* Gray-200 */
            text-align: center;
            font-size: 1.875rem; /* 2xl */
            letter-spacing: 0.5em;
            padding-left: 0.8em; /* Adjust padding to center with letter-spacing */
            height: 4rem; /* เพิ่มความสูงเพื่อให้ดูเด่นขึ้น */
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6; /* Blue-500 */
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
        }
        .btn-primary {
            background-color: #3b82f6; /* Blue-500 */
            color: white;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2563eb; /* Blue-600 */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="w-full max-w-sm p-8 space-y-6 rounded-xl shadow-lg form-container text-center">
        <div>
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-500/20">
                <i data-lucide="shield-check" class="h-6 w-6 text-blue-400"></i>
            </div>
             <h1 class="text-2xl font-bold text-white mt-4">ยืนยันตัวตนสองขั้นตอน</h1>
             <p class="text-gray-400 mt-2">กรอกรหัส 6 หลักจากแอปพลิเคชัน Authenticator ของคุณ</p>
        </div>

        <?php if ($error): ?>
            <div class="p-3 text-sm text-red-200 bg-red-900/50 rounded-lg border border-red-700" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" method="POST" action="verify_2fa.php">
            <div>
                <label for="otp" class="sr-only">รหัสยืนยัน</label>
                <input type="text" name="otp" id="otp" 
                       class="w-full rounded-lg form-input" 
                       maxlength="6" 
                       inputmode="numeric" 
                       pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       required 
                       autofocus>
            </div>

            <div>
                <button type="submit" class="w-full py-3 font-semibold rounded-lg btn-primary">
                    ยืนยัน
                </button>
            </div>
        </form>

        <p class="text-sm text-center text-gray-500">
            <a href="logout.php" class="hover:underline">กลับไปหน้าเข้าสู่ระบบ</a>
        </p>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>