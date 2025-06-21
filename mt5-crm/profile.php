<?php
require 'includes/connectdb.php';
require 'includes/require_login.php';
require_once 'GoogleAuthenticator.php';

$pageTitle = "โปรไฟล์ของฉัน";
$force_2fa = isset($_GET['force_2fa']) && $_GET['force_2fa'] == 1;
ob_start();

$member_id = $_SESSION['member_id'] ?? 0;
$ga = new PHPGangsta_GoogleAuthenticator();
$success = '';
$error = '';

// โหลดข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT username, name, role, is_twofa_enabled, twofa_secret FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$user = $stmt->fetch();

// จัดการการปิด 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    $otp = $_POST['otp'] ?? '';

    if ($ga->verifyCode($user['twofa_secret'], $otp, 2)) {
        $stmt = $pdo->prepare("UPDATE members SET is_twofa_enabled = 0, twofa_secret = NULL WHERE id = ?");
        $stmt->execute([$member_id]);
        $success = "ปิดการใช้งาน 2FA เรียบร้อยแล้ว";
        $user['is_twofa_enabled'] = 0;
        $user['twofa_secret'] = null;
    } else {
        $error = "รหัส OTP ไม่ถูกต้อง กรุณาลองอีกครั้ง";
    }
}
?>

<div class="max-w-2xl mx-auto space-y-6">

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 space-y-3 text-gray-800 dark:text-gray-100">
        <h3 class="text-xl font-bold">🔗 ลิงก์แนะนำเพื่อนของคุณ</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">ส่งลิงก์นี้ให้เพื่อนเพื่อสมัครสมาชิก แล้วคุณจะได้รับคะแนนสะสมหรือค่าคอมมิชชั่น</p>
        <div class="flex items-center space-x-2">
            <?php
            // สร้าง URL แบบเต็ม (ต้องปรับ scheme และ host ให้ตรงกับเว็บของคุณ)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $referral_link = $protocol . $host . $path . '/register.php?ref=' . $member_id;
            ?>
            <input type="text" id="referralLink" value="<?= htmlspecialchars($referral_link) ?>" readonly class="w-full p-2 rounded border bg-gray-100 dark:bg-gray-700 dark:border-gray-600">
            <button onclick="copyLink()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 whitespace-nowrap">คัดลอก</button>
        </div>
        <p id="copyStatus" class="text-sm text-green-500 h-4"></p>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 space-y-4 text-gray-800 dark:text-gray-100">
        <h2 class="text-2xl font-bold text-center">โปรไฟล์ของคุณ</h2>

        <?php if ($success): ?>
            <p class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 p-2 rounded"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p class="bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 p-2 rounded"><?= $error ?></p>
        <?php endif; ?>

        <div class="space-y-2">
            <p><strong>ชื่อผู้ใช้:</strong> <?= htmlspecialchars($user['username']) ?></p>
            <p><strong>ชื่อ:</strong> <?= htmlspecialchars($user['name']) ?></p>
            <p><strong>บทบาท:</strong> <?= htmlspecialchars($user['role']) ?></p>
            <p>
                <strong>2FA:</strong>
                <?php if ($user['is_twofa_enabled']): ?>
                    <span class="inline-block px-2 py-1 text-sm bg-green-100 dark:bg-green-700 text-green-800 dark:text-green-100 rounded">เปิดใช้งานแล้ว</span>
                <?php else: ?>
                    <span class="inline-block px-2 py-1 text-sm bg-red-100 dark:bg-red-700 text-red-800 dark:text-red-100 rounded">ยังไม่ได้เปิด</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if (!$user['is_twofa_enabled']): ?>
            <div class="text-center mt-4">
                <a href="enable_2fa.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    เปิดใช้งาน 2FA
                </a>
            </div>
        <?php else: ?>
            <form method="post" class="mt-6 space-y-4">
                <p class="text-sm text-gray-700 dark:text-gray-300">กรอกรหัส OTP เพื่อยืนยันการปิด 2FA</p>
                <input type="text" name="otp" placeholder="รหัส OTP 6 หลัก" required
                    class="w-full border p-2 rounded text-center text-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white border-gray-300 dark:border-gray-600">
                <button type="submit" name="disable_2fa"
                    class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded">
                    ปิดการใช้งาน 2FA
                </button>
            </form>
        <?php endif; ?>
        
        <?php if (in_array($_SESSION['member_role'] ?? '', ['Admin', 'SuperAdmin'])): ?>
        <div class="mt-6">
            <a href="admin/index.php"
    class="w-full inline-block text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
        🔧 เข้าส่วนของแอดมิน
    </a>

        </div>
    <?php endif; ?>


        <div class="text-center mt-6">
            <a href="index.php" class="text-blue-600 dark:text-blue-400 underline">← กลับหน้าแรก</a>
        </div>
    </div>
</div>

<script>
function copyLink() {
    const linkInput = document.getElementById('referralLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(linkInput.value).then(() => {
        const status = document.getElementById('copyStatus');
        status.innerText = 'คัดลอกลิงก์สำเร็จ!';
        setTimeout(() => { status.innerText = ''; }, 2000);
    }, () => {
        const status = document.getElementById('copyStatus');
        status.innerText = 'ไม่สามารถคัดลอกได้';
    });
}

<?php if ($force_2fa && !$user['is_twofa_enabled']): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  setTimeout(() => {
    Swal.fire({
      icon: 'warning',
      title: 'คุณยังไม่ได้เปิดใช้งาน 2FA',
      html: 'เพื่อความปลอดภัยของบัญชี กรุณาเปิดใช้งาน 2FA ทันที',
      confirmButtonText: 'เปิดใช้งานตอนนี้',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then(() => {
      window.location.href = 'enable_2fa.php';
    });
  }, 500);
</script>
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require 'layout.php';
?>