<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = 'จัดการประกาศ';
ob_start();

// เพิ่มประกาศใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['message'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $created_by = $_SESSION['member_id'];

    $stmt = $pdo->prepare("INSERT INTO announcements (title, message, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$title, $message, $created_by]);
}

// ดึงประกาศทั้งหมด
$stmt = $pdo->query("SELECT a.*, m.name AS creator FROM announcements a LEFT JOIN members m ON a.created_by = m.id ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">📣 จัดการประกาศ</h2>

    <form method="post" class="space-y-4 mb-6">
        <div>
            <label class="block mb-1">หัวข้อประกาศ</label>
            <input type="text" name="title" required class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
        </div>
        <div>
            <label class="block mb-1">รายละเอียด</label>
            <textarea name="message" rows="4" required class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            ➕ เพิ่มประกาศ
        </button>
    </form>

    <h3 class="text-xl font-semibold mb-2">📜 ประกาศทั้งหมด</h3>
    <ul class="space-y-3">
        <?php foreach ($announcements as $row): ?>
            <li class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                <h4 class="font-bold text-lg"><?= htmlspecialchars($row['title']) ?></h4>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">โดย <?= htmlspecialchars($row['creator']) ?> | <?= $row['created_at'] ?></p>
                <div class="text-gray-800 dark:text-gray-100"> <?= nl2br(htmlspecialchars($row['message'])) ?> </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php
$content = ob_get_clean();
require 'layout_admin.php';
