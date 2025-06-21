<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "จัดการของชำร่วย";
ob_start();

// จัดการการเพิ่ม/แก้ไข/ลบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // เพิ่มของชำร่วยใหม่
    if (isset($_POST['add_item'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $points = intval($_POST['points_required']);
        $stock = intval($_POST['stock']);
        $image_path = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/merchandise/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/merchandise/' . $filename;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO merchandise_items (name, description, points_required, stock, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $points, $stock, $image_path]);
    }
    header("Location: manage_merchandise.php");
    exit();
}

// ลบของชำร่วย
if (isset($_GET['delete'])) {
    // (ควรเพิ่มโค้ดลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์ด้วย)
    $pdo->prepare("DELETE FROM merchandise_items WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: manage_merchandise.php");
    exit();
}

// ดึงรายการของชำร่วยทั้งหมด
$items = $pdo->query("SELECT * FROM merchandise_items ORDER BY created_at DESC")->fetchAll();
?>

<div class="max-w-7xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">🎁 เพิ่มของชำร่วยใหม่</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block">ชื่อของชำร่วย</label>
                <input type="text" name="name" required class="w-full p-2 rounded bg-gray-700 border border-gray-600">
            </div>
            <div>
                <label class="block">รายละเอียด</label>
                <textarea name="description" rows="3" class="w-full p-2 rounded bg-gray-700 border border-gray-600"></textarea>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <input type="number" name="points_required" placeholder="แต้มที่ใช้" required class="p-2 rounded bg-gray-700 border border-gray-600">
                <input type="number" name="stock" placeholder="จำนวนในสต็อก" required class="p-2 rounded bg-gray-700 border border-gray-600">
                <input type="file" name="image" accept="image/*" class="p-2 bg-gray-700 border border-gray-600 text-white">
            </div>
            <button type="submit" name="add_item" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                บันทึก
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">🛍️ รายการทั้งหมด</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left bg-gray-700">
                        <th class="p-2">รูป</th>
                        <th class="p-2">ชื่อ</th>
                        <th class="p-2">แต้ม</th>
                        <th class="p-2">สต็อก</th>
                        <th class="p-2">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-b border-gray-700">
                            <td class="p-2">
                                <img src="../<?= htmlspecialchars($item['image_path']) ?>" class="w-16 h-16 object-cover rounded" alt="Image">
                            </td>
                            <td class="p-2"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="p-2"><?= number_format($item['points_required']) ?></td>
                            <td class="p-2"><?= number_format($item['stock']) ?></td>
                            <td class="p-2">
                                <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('ยืนยันการลบ?')" class="text-red-500 hover:underline">ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout_admin.php';
?>