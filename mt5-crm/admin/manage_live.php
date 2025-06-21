<?php
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = 'จัดการลิงก์ Live';
ob_start();

// เพิ่มลิงก์ใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['url'])) {
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    $category = trim($_POST['category']) ?: 'ทั่วไป';
    $start_time = $_POST['start_time'] ?: null;
    $is_live = isset($_POST['is_live']) ? intval($_POST['is_live']) : 1;

    $stmt = $pdo->prepare("INSERT INTO live_links (title, url, category, start_time, is_live, sort_order)
                           VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->execute([$title, $url, $category, $start_time, $is_live]);

    header("Location: manage_live.php");
    exit();
}


// ลบลิงก์
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM live_links WHERE id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: manage_live.php");
    exit();
}

// ดึงลิงก์ทั้งหมด
$stmt = $pdo->query("SELECT * FROM live_links ORDER BY category ASC, sort_order ASC");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มตาม category
$grouped = [];
foreach ($links as $link) {
    $grouped[$link['category']][] = $link;
}
?>

<div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">🎥 จัดการลิงก์ Live</h1>

    <!-- Form เพิ่มลิงก์ -->
    <form method="post" class="grid md:grid-cols-5 gap-4 mb-8">
    <input type="text" name="title" placeholder="ชื่อไลฟ์" required class="p-2 rounded border bg-white dark:bg-gray-700 dark:border-gray-600">
    
    <input type="url" name="url" placeholder="URL ไลฟ์" required class="p-2 rounded border bg-white dark:bg-gray-700 dark:border-gray-600">

    <input type="text" name="category" placeholder="หมวดหมู่ (กีฬา/ข่าว)" class="p-2 rounded border bg-white dark:bg-gray-700 dark:border-gray-600">

    <input type="datetime-local" name="start_time" class="p-2 rounded border bg-white dark:bg-gray-700 dark:border-gray-600">

    <select name="is_live" class="p-2 rounded border bg-white dark:bg-gray-700 dark:border-gray-600">
        <option value="1" selected>🔴 กำลังไลฟ์</option>
        <option value="0">🕓 จบแล้ว</option>
    </select>

    <div class="md:col-span-5 text-right">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            ➕ เพิ่มลิงก์
        </button>
    </div>
</form>


    <!-- กลุ่มลิงก์แบบ drag -->
    <?php foreach ($grouped as $cat => $items): ?>
        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mt-6 mb-2">📂 <?= htmlspecialchars($cat) ?></h2>
        <ul class="space-y-2 mb-6" data-category="<?= htmlspecialchars($cat) ?>">
            <?php foreach ($items as $item): ?>
                <li class="bg-gray-100 dark:bg-gray-700 p-4 rounded flex justify-between items-center cursor-move" data-id="<?= $item['id'] ?>">
                    <div>
                        <div class="font-semibold"><?= htmlspecialchars($item['title']) ?></div>
                        <div class="text-sm text-blue-400 break-all"><?= htmlspecialchars($item['url']) ?></div>
                    </div>
                    <a href="?delete=<?= $item['id'] ?>" class="text-red-500 hover:underline text-sm" onclick="return confirm('ลบลิงก์นี้?')">ลบ</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.querySelectorAll("ul[data-category]").forEach(ul => {
    new Sortable(ul, {
        animation: 150,
        onEnd: function () {
            const ids = Array.from(ul.children).map(li => li.dataset.id);
            fetch('update_live_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids })
            });
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require 'layout_admin.php';
