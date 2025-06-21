<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "ตั้งค่าเปอร์เซ็นต์คอมมิชชั่น";
ob_start();

// ถ้ามีการส่ง POST มา
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_percent = intval($_POST['commission_first_percent']);
    $repeat_percent = intval($_POST['commission_repeat_percent']);

    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'commission_first_percent'");
    $stmt->execute([$first_percent]);

    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'commission_repeat_percent'");
    $stmt->execute([$repeat_percent]);

    $success = true;
}

// ดึงค่าคอมมิชชั่นปัจจุบัน
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('commission_first_percent', 'commission_repeat_percent')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$first_percent = $settings['commission_first_percent'] ?? 0;
$repeat_percent = $settings['commission_repeat_percent'] ?? 0;
?>

<main class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            🎉 อัปเดตเปอร์เซ็นต์คอมมิชชั่นเรียบร้อยแล้ว
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow space-y-6">
        <form method="POST" class="space-y-4">
            <div>
                <label for="commission_first_percent" class="block text-sm font-medium mb-1">เปอร์เซ็นต์คอมมิชชั่น (ชำระครั้งแรก)</label>
                <input type="number" name="commission_first_percent" id="commission_first_percent" min="0" max="100" value="<?= htmlspecialchars($first_percent) ?>" required
                    class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white p-2">
            </div>

            <div>
                <label for="commission_repeat_percent" class="block text-sm font-medium mb-1">เปอร์เซ็นต์คอมมิชชั่น (ชำระครั้งถัดไป)</label>
                <input type="number" name="commission_repeat_percent" id="commission_repeat_percent" min="0" max="100" value="<?= htmlspecialchars($repeat_percent) ?>" required
                    class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white p-2">
            </div>

            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    💾 บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
</main>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>
