<?php
// update_manage.php (หลังบ้าน Admin จัดการเวอร์ชัน)
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require_once '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "จัดการเวอร์ชันระบบ";
ob_start();

// บันทึกเวอร์ชันใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $version_code = $_POST['version_code'] ?? '';
    $changelog = $_POST['changelog'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO app_versions (version_code, changelog) VALUES (?, ?)");
    $stmt->execute([$version_code, $changelog]);
    header("Location: update_manage.php?success=1");
    exit();
}

// ดึงเวอร์ชันล่าสุด
$stmt = $pdo->query("SELECT * FROM app_versions ORDER BY updated_at DESC LIMIT 1");
$current_version = $stmt->fetch();
?>

<main class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">🛠️ <?= $pageTitle ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">✅ บันทึกเวอร์ชันใหม่เรียบร้อย</div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div>
            <label class="block mb-1 font-semibold">เวอร์ชันใหม่</label>
            <input type="text" name="version_code" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white p-2" required>
        </div>
        <div>
            <label class="block mb-1 font-semibold">รายละเอียดการอัปเดต (changelog)</label>
            <textarea name="changelog" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white p-2 h-32"></textarea>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">💾 เผยแพร่เวอร์ชัน</button>
    </form>

    <?php if ($current_version): ?>
        <div class="mt-8 bg-gray-100 dark:bg-gray-700 p-4 rounded">
            <p><strong>เวอร์ชันล่าสุด:</strong> <?= htmlspecialchars($current_version['version_code']) ?></p>
            <p><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($current_version['changelog'])) ?></p>
            <p class="text-sm text-gray-500 mt-2">อัปเดตเมื่อ: <?= $current_version['updated_at'] ?></p>
        </div>
    <?php endif; ?>
</main>

<?php
$content = ob_get_clean();
include 'layout_admin.php';
?>
