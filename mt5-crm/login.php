<?php
session_start();
require 'includes/connectdb.php';

// หาก login อยู่แล้วให้ไปหน้า index
if (isset($_SESSION['member_id'])) {
    header("Location: mt5-crm/index.php");
    exit();
}

// --- Google Login ---
// !! แทนที่ค่าเหล่านี้ด้วย Client ID, Client Secret, และ Redirect URI ของคุณ
$google_client_id = '179663872858-04fajg6s8bl7kmb2948nhij5jq9bf1f9.apps.googleusercontent.com';
$google_redirect_uri = 'http://epictest.info/mt5-crm/google_callback.php'; // เปลี่ยนเป็น URL จริงของคุณ
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
    'access_type' => 'offline',
    'prompt' => 'select_account'
]);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM members WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]); // อนุญาตให้ login ด้วย email หรือ username
    $user = $stmt->fetch();

    if ($user) {
        // --- ส่วนที่เพิ่มเข้ามาเพื่อจัดการบัญชี Google ---
        if (!empty($user['google_id']) && is_null($user['password'])) {
            $error = 'บัญชีนี้สมัครด้วย Google กรุณาคลิก "Sign in with Google" เพื่อเข้าสู่ระบบ';
        }
        // --- จบส่วนที่เพิ่ม ---
        
        // ตรวจสอบรหัสผ่าน (เฉพาะบัญชีที่สมัครปกติ)
        elseif (password_verify($password, $user['password'])) {
            // กรณีเปิดใช้ 2FA
            if ($user['is_twofa_enabled'] == 1) {
                $_SESSION['pending_2fa_user_id'] = $user['id'];
                $_SESSION['is_twofa_enabled'] = 1;
                $_SESSION['member_role'] = $user['role'];
                $_SESSION['member_name'] = $user['name'];
                header("Location: verify_2fa.php");
                exit();
            }

            // กรณีไม่ใช้ 2FA → login ปกติ
            $_SESSION['member_id'] = $user['id'];
            $_SESSION['member_role'] = $user['role'];
            $_SESSION['member_name'] = $user['name'];
            $_SESSION['is_twofa_enabled'] = 0;
            $_SESSION['meta_api_account_id'] = $user['meta_api_account_id'];

            // log login
            $stmt = $pdo->prepare("INSERT INTO login_logs (member_id, ip_address) VALUES (?, ?)");
            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

            header("Location: mt5-crm/index.php");
            exit();
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>

<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - MT5 CRM</title>
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
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; /* Gray-400 */
        }
        .form-input {
            background-color: #374151; /* Gray-700 */
            border: 1px solid #4b5563; /* Gray-600 */
            color: #e5e7eb; /* Gray-200 */
            padding-left: 3rem; /* เพิ่ม Padding จาก 2.5rem เป็น 3rem */
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
        .btn-google {
            background-color: #ffffff;
            color: #1f2937;
            transition: background-color 0.3s ease;
        }
        .btn-google:hover {
            background-color: #f3f4f6; /* Gray-100 */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md p-8 space-y-6 rounded-xl shadow-lg form-container">
        <div class="text-center">
             <h1 class="text-3xl font-bold text-white">MT5 CRM</h1>
             <p class="text-gray-400">ยินดีต้อนรับ, กรุณาเข้าสู่ระบบ</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 text-sm text-red-200 bg-red-800 bg-opacity-50 rounded-lg border border-red-700" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="space-y-6" method="POST" action="login.php">
            <div class="input-group">
                <i data-lucide="user" class="input-icon" stroke-width="2" width="20" height="20"></i>
                <input type="text" name="username" id="username" placeholder="ชื่อผู้ใช้" class="w-full px-12 py-3 rounded-lg form-input" required>
            </div>

            <div class="input-group">
                 <i data-lucide="lock" class="input-icon" stroke-width="2" width="20" height="20"></i>
                <input type="password" name="password" id="password" placeholder="รหัสผ่าน" class="w-full px-12 py-3 rounded-lg form-input" required>
            </div>
            
            <div class="flex items-center justify-between">
                <label class="flex items-center text-sm text-gray-300">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-600 ring-offset-gray-800 focus:ring-2">
                    <span class="ml-2">จดจำฉันไว้ในระบบ</span>
                </label>
                </div>

            <div>
                <button type="submit" class="w-full py-3 font-semibold rounded-lg btn-primary">
                    เข้าสู่ระบบ
                </button>
            </div>

            <div class="flex items-center justify-center">
                <div class="w-full h-px bg-gray-600"></div>
                <div class="px-3 text-sm text-gray-400">หรือ</div>
                <div class="w-full h-px bg-gray-600"></div>
            </div>

            <div>
                <a href="<?= htmlspecialchars($google_login_url) ?>" class="flex items-center justify-center w-full py-3 font-semibold rounded-lg btn-google">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48">
                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"></path><path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"></path><path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"></path><path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571l6.19,5.238C43.021,36.251,44,34,44,30C44,22.659,43.862,21.35,43.611,20.083z"></path>
                    </svg>
                    เข้าสู่ระบบด้วย Google
                </a>
            </div>
        </form>

        <p class="text-sm text-center text-gray-400">
            ยังไม่มีบัญชี? <a href="register.php" class="font-semibold text-blue-400 hover:underline">สมัครสมาชิกที่นี่</a>
        </p>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>