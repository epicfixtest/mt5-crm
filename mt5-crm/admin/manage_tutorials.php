<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "🎬 จัดการวิดีโอแนะนำการใช้งาน";
ob_start();

function extractYoutubeID($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $vars);
    return $vars['v'] ?? preg_replace('/^.*\\/embed\\/([^?]+).*$/', '$1', $url);
}

// เพิ่มวิดีโอใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_video'])) {
    $title = trim($_POST['title']);
    $youtube_url = trim($_POST['youtube_url']);
    $video_id = extractYoutubeID($youtube_url);
    if ($title && $video_id) {
        $stmt = $pdo->prepare("INSERT INTO tutorials (title, youtube_url, sort_order) SELECT ?, ?, COALESCE(MAX(sort_order), 0) + 1 FROM (SELECT * FROM tutorials) AS t");
        $stmt->execute([$title, $video_id]);
    }
}

// ลบวิดีโอ
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM tutorials WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    exit(header('Location: manage_tutorials.php'));
}

// แก้ไขวิดีโอ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $title = trim($_POST['edit_title']);
    $youtube_url = trim($_POST['edit_youtube_url']);
    $video_id = extractYoutubeID($youtube_url);
    if ($title && $video_id) {
        $stmt = $pdo->prepare("UPDATE tutorials SET title = ?, youtube_url = ? WHERE id = ?");
        $stmt->execute([$title, $video_id, $id]);
    }
    exit(header('Location: manage_tutorials.php'));
}

// อัปเดตลำดับใหม่ (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    foreach ($_POST['order'] as $index => $id) {
        $stmt = $pdo->prepare("UPDATE tutorials SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index, $id]);
    }
    exit;
}

$stmt = $pdo->query("SELECT * FROM tutorials ORDER BY sort_order ASC, created_at DESC");
$tutorials = $stmt->fetchAll();
?>

<main class="p-6 max-w-5xl mx-auto">
  <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

  <form method="POST" class="bg-white dark:bg-gray-800 p-4 rounded shadow mb-8 space-y-4">
    <h2 class="text-lg font-semibold">➕ เพิ่มวิดีโอใหม่</h2>
    <input type="text" name="title" placeholder="หัวข้อวิดีโอ" required class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700">
    <input type="url" name="youtube_url" placeholder="ลิงก์ YouTube (https://www.youtube.com/watch?v=xxxx)" required class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700">
    <button type="submit" name="add_video" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">เพิ่มวิดีโอ</button>
  </form>

  <h2 class="text-lg font-semibold mb-4">📂 วิดีโอทั้งหมด (ลากเพื่อเรียงลำดับ)</h2>
  <ul id="videoList" class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($tutorials as $t): ?>
      <li data-id="<?= $t['id'] ?>" class="bg-white dark:bg-gray-800 p-4 rounded shadow cursor-move">
        <form method="POST" class="space-y-2">
          <input type="hidden" name="edit_id" value="<?= $t['id'] ?>">
          <input type="text" name="edit_title" value="<?= htmlspecialchars($t['title']) ?>" class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700">
          <input type="text" name="edit_youtube_url" value="https://www.youtube.com/watch?v=<?= $t['youtube_url'] ?>" class="w-full p-2 rounded bg-gray-100 dark:bg-gray-700">
          <div class="aspect-video">
            <iframe class="w-full h-52" src="https://www.youtube.com/embed/<?= $t['youtube_url'] ?>" frameborder="0" allowfullscreen></iframe>
          </div>
          <div class="flex gap-2 pt-2">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">💾 บันทึก</button>
            <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('ลบวิดีโอนี้หรือไม่?')" class="text-red-600 text-sm">🗑️ ลบ</a>
          </div>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
$(function() {
  $('#videoList').sortable({
    update: function(event, ui) {
      const order = $(this).children().map(function() {
        return $(this).data('id');
      }).get();
      $.post('manage_tutorials.php', { order: order });
    }
  });
});
</script>

<?php
$content = ob_get_clean();
include 'layout_admin.php';
?>
