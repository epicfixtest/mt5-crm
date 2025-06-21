<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "จัดการโปรโมชั่น";
ob_start();

// จัดการการเพิ่ม/แก้ไข/ลบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // เพิ่มโปรโมชั่นใหม่
    if (isset($_POST['add_promotion'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_path = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/promotions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/promotions/' . $filename;
            }
        }
        $stmt = $pdo->prepare("INSERT INTO promotions (title, description, is_active, image_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $is_active, $image_path]);
    }
    header("Location: manage_promotions.php");
    exit();
}

// ลบโปรโมชั่น
if (isset($_GET['delete'])) {
    // (ควรเพิ่มโค้ดลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์ด้วย)
    $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: manage_promotions.php");
    exit();
}

// ดึงรายการโปรโมชั่นทั้งหมด
$promotions = $pdo->query("SELECT * FROM promotions ORDER BY sort_order ASC")->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">⭐ เพิ่มโปรโมชั่นใหม่</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block">หัวข้อโปรโมชั่น</label>
                <input type="text" name="title" required class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <label class="block">รายละเอียด</label>
                <textarea name="description" rows="4" class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></textarea>
            </div>
            <div>
                 <label class="block">รูปภาพโปรโมชั่น</label>
                <input type="file" name="image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 hover:file:bg-blue-100">
            </div>
             <div>
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded">
                    <span class="ml-2">แสดงโปรโมชั่นนี้</span>
                </label>
            </div>
            <button type="submit" name="add_promotion" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                บันทึกโปรโมชั่น
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">✨ รายการโปรโมชั่นทั้งหมด (ลากเพื่อเรียงลำดับ)</h2>
        <ul id="promotionList" class="space-y-4">
            <?php foreach ($promotions as $promo): ?>
                <li data-id="<?= $promo['id'] ?>" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg flex items-start gap-4 cursor-move">
                    <img src="../<?= htmlspecialchars($promo['image_path']) ?>" class="w-32 h-32 object-cover rounded" alt="Promotion Image">
                    <div class="flex-1">
                        <h3 class="font-bold text-lg"><?= htmlspecialchars($promo['title']) ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= nl2br(htmlspecialchars($promo['description'])) ?></p>
                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full <?= $promo['is_active'] ? 'text-green-600 bg-green-200' : 'text-red-600 bg-red-200' ?>">
                            <?= $promo['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน' ?>
                        </span>
                    </div>
                    <div>
                        <a href="?delete=<?= $promo['id'] ?>" onclick="return confirm('ยืนยันการลบโปรโมชั่นนี้?')" class="text-red-500 hover:underline text-sm">ลบ</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const list = document.getElementById('promotionList');
    new Sortable(list, {
        animation: 150,
        onEnd: function () {
            const order = Array.from(list.children).map(item => item.dataset.id);
            fetch('update_promotion_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order: order })
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require 'layout_admin.php';
?>