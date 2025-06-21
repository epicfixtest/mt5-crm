<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "จัดการ Preset ของ Bot";
ob_start();

// ดึงรายการสินค้า (Bot) ทั้งหมดมาเพื่อใช้ใน dropdown
$bots = $pdo->query("SELECT id, title FROM redeem_items ORDER BY title ASC")->fetchAll();

// จัดการการเพิ่ม Preset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_preset'])) {
    $bot_id = intval($_POST['bot_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if ($bot_id > 0 && !empty($name) && isset($_FILES['preset_file']) && $_FILES['preset_file']['error'] === 0) {
        $upload_dir = '../uploads/presets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $filename = uniqid() . '-' . basename($_FILES['preset_file']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['preset_file']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO bot_presets (redeem_item_id, name, description, file_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bot_id, $name, $description, $target_path]);
        }
    }
    header("Location: manage_presets.php");
    exit();
}

// จัดการการลบ
if (isset($_GET['delete'])) {
    $preset_id = intval($_GET['delete']);
    // (Optional) ควรเพิ่มโค้ดลบไฟล์ออกจากเซิร์ฟเวอร์ด้วย
    $pdo->prepare("DELETE FROM bot_presets WHERE id = ?")->execute([$preset_id]);
    header("Location: manage_presets.php");
    exit();
}

// ดึงรายการ Preset ทั้งหมด
$presets = $pdo->query("
    SELECT p.*, r.title as bot_name 
    FROM bot_presets p
    JOIN redeem_items r ON p.redeem_item_id = r.id
    ORDER BY r.title, p.name
")->fetchAll();
?>

<div class="max-w-4xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">🤖 อัปโหลด Preset ใหม่</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block">สำหรับ Bot (สินค้า)</label>
                <select name="bot_id" required class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600">
                    <option value="">-- เลือก Bot --</option>
                    <?php foreach($bots as $bot): ?>
                        <option value="<?= $bot['id'] ?>"><?= htmlspecialchars($bot['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block">ชื่อ Preset (เช่น "EURUSD Aggressive")</label>
                <input type="text" name="name" required class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <label class="block">รายละเอียด (ไม่บังคับ)</label>
                <textarea name="description" rows="2" class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></textarea>
            </div>
            <div>
                <label class="block">ไฟล์ Preset</label>
                <input type="file" name="preset_file" required class="w-full">
            </div>
            <button type="submit" name="add_preset" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                อัปโหลด Preset
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">⚙️ Preset ทั้งหมดในระบบ</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left bg-gray-200 dark:bg-gray-700">
                        <th class="p-2">ชื่อ Preset</th>
                        <th class="p-2">สำหรับ Bot</th>
                        <th class="p-2">วันที่อัปโหลด</th>
                        <th class="p-2">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($presets as $preset): ?>
                    <tr class="border-b dark:border-gray-700">
                        <td class="p-2"><?= htmlspecialchars($preset['name']) ?></td>
                        <td class="p-2 text-gray-400"><?= htmlspecialchars($preset['bot_name']) ?></td>
                        <td class="p-2"><?= date('d/m/Y', strtotime($preset['created_at'])) ?></td>
                        <td class="p-2">
                            <a href="?delete=<?= $preset['id'] ?>" onclick="return confirm('ยืนยันการลบ?')" class="text-red-500 hover:underline">ลบ</a>
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