<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "เพิ่มบริการแลกไฟล์";
ob_start();

// Handle การเพิ่มไฟล์ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $title = trim($_POST['title']);
    $points_required = floatval($_POST['points_required']);

    if (!empty($title) && $points_required > 0 && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = 'files/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = basename($_FILES['file']['name']);
        $target_path = $upload_dir . uniqid() . '_' . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO redeem_items (title, points_required, file_path) VALUES (?, ?, ?)");
            $stmt->execute([$title, $points_required, $target_path]);
            $success = true;
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
        }
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วนและเลือกไฟล์ที่ถูกต้อง";
    }
}
?>

<main class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            🎉 เพิ่มบริการแลกไฟล์เรียบร้อยแล้ว
        </div>
    <?php elseif (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium mb-1" for="title">ชื่อบริการ</label>
                <input type="text" id="title" name="title" class="w-full p-2 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="points_required">แต้มที่ต้องใช้</label>
                <input type="number" id="points_required" name="points_required" min="1" class="w-full p-2 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" for="file">ไฟล์บริการ</label>
                <input type="file" id="file" name="file" class="w-full text-sm text-gray-500 dark:text-gray-300" required>
            </div>

            <div>
                <button type="submit" name="add_item" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
                    ➕ เพิ่มบริการ
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
