<?php
require 'includes/connectdb.php';

$search = $_GET['search'] ?? '';

if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM tutorials WHERE title LIKE ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM tutorials ORDER BY sort_order ASC, created_at DESC");
}
$tutorials = $stmt->fetchAll();

foreach ($tutorials as $t): ?>
  <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
    <h2 class="text-lg font-semibold mb-2"><?= htmlspecialchars($t['title']) ?></h2>
    <div class="aspect-video">
      <iframe class="w-full h-52" src="https://www.youtube.com/embed/<?= htmlspecialchars($t['youtube_url']) ?>" frameborder="0" allowfullscreen></iframe>
    </div>
  </div>
<?php endforeach; ?>

<?php if (empty($tutorials)): ?>
  <p class="text-gray-500 col-span-full">ไม่พบวิดีโอที่เกี่ยวข้องกับ "<?= htmlspecialchars($search) ?>"</p>
<?php endif; ?>
