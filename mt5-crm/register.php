<?php
session_start();
require 'includes/connectdb.php';

// --- จัดการลิงก์แนะนำเพื่อน ---
if (isset($_GET['ref']) && is_numeric($_GET['ref'])) {
    // เก็บ ID ผู้แนะนำไว้ใน Session เพื่อใช้ต่อ
    $_SESSION['referrer_id'] = intval($_GET['ref']);
}

// --- Google Login ---
// !! อย่าลืม! แทนที่ค่าเหล่านี้ด้วย Client ID และ Redirect URI ของคุณ
$google_client_id = '179663872858-04fajg6s8bl7kmb2948nhij5jq9bf1f9.apps.googleusercontent.com';
$google_redirect_uri = 'https://epictest.info/mt5-crm/google_callback.php'; // เปลี่ยนเป็น URL จริงของคุณ
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email',
    'access_type' => 'offline',
    'prompt' => 'select_account'
]);

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // --- Validation ---
    if (empty($phone) || empty($name) || empty($username) || empty($password) || empty($email)) {
        $errors[] = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }
    if (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    // --- Check if username or email already exists ---
    $stmt = $pdo->prepare("SELECT 1 FROM members WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
    }

    // --- Process registration if no errors ---
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // เพิ่ม referrer_id ในคำสั่ง INSERT
        $referrer_id = $_SESSION['referrer_id'] ?? null;
        
        $stmt = $pdo->prepare(
            "INSERT INTO members (username, password, name, email, phone, role, referrer_id) VALUES (?, ?, ?, ?, ?, 'User', ?)"
        );
        if ($stmt->execute([$username, $hashed_password, $name, $email, $phone, $referrer_id])) {
            $new_member_id = $pdo->lastInsertId();

            // กำหนดสิทธิ์เริ่มต้น
            $default_permissions = ['index', 'history', 'trade', 'news', 'tradingview'];
            $perm_stmt = $pdo->prepare("INSERT INTO member_permissions (member_id, page_key) VALUES (?, ?)");
            foreach ($default_permissions as $page_key) {
                $perm_stmt->execute([$new_member_id, $page_key]);
            }
            
            // ล้าง session ของผู้แนะนำหลังจากใช้งานแล้ว
            if (isset($_SESSION['referrer_id'])) {
                unset($_SESSION['referrer_id']);
            }

            $success_message = 'สมัครสมาชิกสำเร็จ! คุณสามารถเข้าสู่ระบบได้ทันที';
        } else {
            $errors[] = 'เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองอีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - MT5 CRM</title>
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
            padding-left: 3rem; /* Adjusted Padding */
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
<body class="flex items-center justify-center min-h-screen py-12">

    <div class="w-full max-w-lg p-8 space-y-6 rounded-xl shadow-lg form-container">
        <div class="text-center">
             <h1 class="text-3xl font-bold text-white">สร้างบัญชีใหม่</h1>
             <p class="text-gray-400">เริ่มต้นใช้งาน MT5 CRM ได้ง่ายๆ</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="p-4 text-sm text-green-200 bg-green-800 bg-opacity-50 rounded-lg border border-green-700" role="alert">
                <p><?= htmlspecialchars($success_message) ?></p>
                <a href="login.php" class="font-bold underline hover:text-green-300">คลิกที่นี่เพื่อเข้าสู่ระบบ</a>
            </div>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="p-4 text-sm text-red-200 bg-red-800 bg-opacity-50 rounded-lg border border-red-700" role="alert">
                    <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="space-y-4" method="POST" action="register.php">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="input-group">
                        <i data-lucide="user-circle" class="input-icon" stroke-width="2" width="20" height="20"></i>
                        <input type="text" name="name" placeholder="ชื่อ-นามสกุล" class="w-full px-12 py-3 rounded-lg form-input" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                     <div class="input-group">
                        <i data-lucide="phone" class="input-icon" stroke-width="2" width="20" height="20"></i>
                        <input type="tel" name="phone" placeholder="เบอร์โทรศัพท์" class="w-full px-12 py-3 rounded-lg form-input" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="input-group">
                    <i data-lucide="user" class="input-icon" stroke-width="2" width="20" height="20"></i>
                    <input type="text" name="username" placeholder="ชื่อผู้ใช้ (สำหรับ Login)" class="w-full px-12 py-3 rounded-lg form-input" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="input-group">
                    <i data-lucide="mail" class="input-icon" stroke-width="2" width="20" height="20"></i>
                    <input type="email" name="email" placeholder="อีเมล" class="w-full px-12 py-3 rounded-lg form-input" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="input-group">
                         <i data-lucide="lock" class="input-icon" stroke-width="2" width="20" height="20"></i>
                        <input type="password" name="password" placeholder="รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" class="w-full px-12 py-3 rounded-lg form-input" required>
                    </div>
                    <div class="input-group">
                         <i data-lucide="lock-keyhole" class="input-icon" stroke-width="2" width="20" height="20"></i>
                        <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" class="w-full px-12 py-3 rounded-lg form-input" required>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full mt-2 py-3 font-semibold rounded-lg btn-primary">
                        ลงทะเบียน
                    </button>
                </div>

                <div class="flex items-center justify-center pt-2">
                    <div class="w-full h-px bg-gray-600"></div>
                    <div class="px-3 text-sm text-gray-400">หรือ</div>
                    <div class="w-full h-px bg-gray-600"></div>
                </div>

                <div>
                     <a href="<?= htmlspecialchars($google_login_url) ?>" class="flex items-center justify-center w-full py-3 font-semibold rounded-lg btn-google">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path><path fill="none" d="M0 0h48v48H0z"></path></svg>
                        สมัครด้วย Google
                    </a>
                </div>
            </form>
        <?php endif; ?>

        <p class="text-sm text-center text-gray-400 pt-4">
            มีบัญชีอยู่แล้ว? <a href="login.php" class="font-semibold text-blue-400 hover:underline">เข้าสู่ระบบที่นี่</a>
        </p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>