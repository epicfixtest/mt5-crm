<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "จัดการสินค้า / Bot / บริการ";
ob_start();

// Handle Delete Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // (Optional) Add code here to delete the actual file from the server
    $pdo->prepare("DELETE FROM redeem_items WHERE id = ?")->execute([$id]);
    header("Location: manage_products.php");
    exit();
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $title = trim($_POST['title']);
    // ★★★ รับค่า coin_cost และ point_reward ★★★
    $coin_cost = floatval($_POST['coin_cost']);
    $point_reward = intval($_POST['point_reward']);

    if (!empty($title) && $coin_cost >= 0 && $point_reward >= 0 && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = '../files/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '_' . basename($_FILES['file']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            // ★★★ บันทึก coin_cost และ point_reward ลง DB ★★★
            $stmt = $pdo->prepare("INSERT INTO redeem_items (title, file_path, coin_cost, point_reward) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, 'files/' . $filename, $coin_cost, $point_reward]);
        }
    }
    header("Location: manage_products.php");
    exit();
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = intval($_POST['product_id']);
    $title = trim($_POST['title']);
    $points_required = floatval($_POST['points_required']);

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $upload_dir = '../files/';
        $filename = uniqid() . '_' . basename($_FILES['file']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("UPDATE redeem_items SET title = ?, points_required = ?, file_path = ? WHERE id = ?");
            $stmt->execute([$title, $points_required, 'files/' . $filename, $id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE redeem_items SET title = ?, points_required = ? WHERE id = ?");
        $stmt->execute([$title, $points_required, $id]);
    }

    header("Location: manage_products.php");
    exit();
}

// ดึงรายการสินค้า
$stmt = $pdo->query("SELECT * FROM redeem_items ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>

<main class="p-6 max-w-7xl mx-auto space-y-10">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">➕ เพิ่มไฟล์/บริการใหม่</h2>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="title" placeholder="ชื่อสินค้า" required class="p-2 rounded bg-gray-700 border border-gray-600 text-white">
            <input type="number" step="0.01" name="coin_cost" placeholder="ราคา (Coins)" required class="p-2 rounded bg-gray-700 border border-gray-600 text-white">
            <input type="number" name="point_reward" placeholder="แต้มที่ได้รับ" required class="p-2 rounded bg-gray-700 border border-gray-600 text-white">
            <input type="file" name="file" required class="p-2 bg-gray-700 border border-gray-600 text-white">
            <div class="md:col-span-4">
                <button type="submit" name="add_product" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded mt-2">
                    บันทึก
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">🛍️ รายการไฟล์/บริการ</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">
                <thead class="text-xs bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">ชื่อสินค้า</th>
                        <th class="px-4 py-2">ราคา (Coins)</th>
                        <th class="px-4 py-2">แต้มที่ได้รับ</th>
                        <th class="px-4 py-2">ไฟล์</th>
                        <th class="px-4 py-2 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <?php foreach ($products as $product): ?>
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"><?= htmlspecialchars($product['title']) ?></td>
                            <td class="px-4 py-2"><?= number_format($product['coin_cost'], 2) ?></td>
                            <td class="px-4 py-2"><?= number_format($product['point_reward']) ?></td>
                            <td class="px-4 py-2">
                                <a href="../<?= htmlspecialchars($product['file_path']) ?>" target="_blank" class="text-blue-400 underline">ดาวน์โหลด</a>
                            </td>
                            <td class="px-4 py-2 text-right flex gap-2 justify-end">
                                <a href="manage_products.php?delete=<?= $product['id'] ?>" onclick="return confirm('ลบสินค้านี้หรือไม่?')" class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded text-xs">
                                    🗑️ ลบ
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow w-full max-w-md">
        <h2 class="text-lg font-semibold mb-4">✏️ แก้ไขสินค้า</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="product_id" id="editProductId">

            <div>
                <label class="block text-sm mb-1">ชื่อสินค้า</label>
                <input type="text" name="title" id="editTitle" required class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white">
            </div>

            <div>
                <label class="block text-sm mb-1">แต้มที่ต้องใช้</label>
                <input type="number" step="0.01" name="points_required" id="editPoints" required class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white">
            </div>

            <div>
                <label class="block text-sm mb-1">อัปโหลดไฟล์ใหม่ (ถ้ามี)</label>
                <input type="file" name="file" class="w-full text-gray-300 dark:text-gray-400">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                    ยกเลิก
                </button>
                <button type="submit" name="edit_product" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, title, points) {
    document.getElementById('editProductId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editPoints').value = points;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>
