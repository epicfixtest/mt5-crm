<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = 'แก้ไขเกี่ยวกับเรา';
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $stmt = $pdo->prepare("UPDATE about_page SET content = ? WHERE id = 1");
    $stmt->execute([trim($_POST['content'])]);
    $success = true;
}

$stmt = $pdo->query("SELECT content FROM about_page WHERE id = 1");
$contentData = $stmt->fetchColumn();
?>

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">✏️ แก้ไขหน้าเกี่ยวกับเรา</h1>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 dark:bg-green-700 text-green-800 dark:text-white px-4 py-2 rounded mb-4">
            ✅ บันทึกเรียบร้อยแล้ว
        </div>
    <?php endif; ?>

    <form method="post">
        <textarea name="content" rows="10" class="w-full p-4 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white"><?= htmlspecialchars($contentData) ?></textarea>
        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            💾 บันทึก
        </button>
    </form>
</div>

<?php
$content = ob_get_clean();
require 'layout_admin.php';
