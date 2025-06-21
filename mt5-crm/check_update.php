<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "ตรวจสอบเวอร์ชันระบบ";
ob_start();

$member_id = $_SESSION['member_id'] ?? 0;

// ดึงเวอร์ชันล่าสุดจากระบบ
$stmt = $pdo->query("SELECT * FROM app_versions ORDER BY updated_at DESC LIMIT 1");
$latest = $stmt->fetch();
$current_version = $latest['version_code'] ?? '0.0.0';

// ตรวจสอบว่าผู้ใช้นี้เคยอัปเดตแล้วหรือยัง
$updated = false;
if ($latest) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM version_updates WHERE member_id = ? AND version_code = ?");
    $stmt->execute([$member_id, $latest['version_code']]);
    $updated = $stmt->fetchColumn() > 0;
}
?>

<main class="p-6 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">🔄 <?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow text-center">
        <p class="mb-2">เวอร์ชันล่าสุด: <strong><?= $current_version ?></strong></p>

        <?php if ($latest): ?>
            <p class="mb-4">รายละเอียดเวอร์ชัน: <strong><?= nl2br(htmlspecialchars($latest['changelog'])) ?></strong></p>

            <?php if (!$updated): ?>
                <button onclick="simulateUpdate()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    อัปเดตเป็นเวอร์ชันล่าสุด (<?= htmlspecialchars($latest['version_code']) ?>)
                </button>
            <?php else: ?>
                <p class="text-green-600">คุณได้อัปเดตเป็นเวอร์ชันล่าสุดแล้ว ✅</p>
            <?php endif; ?>

        <?php else: ?>
            <p class="text-red-600 mt-4">ยังไม่มีเวอร์ชันล่าสุดในระบบ</p>
        <?php endif; ?>
    </div>
</main>

<!-- Popup อัปเดต -->
<div id="updatePopup" class="hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white dark:bg-gray-900 p-6 rounded-xl text-center w-80">
    <p class="text-lg font-semibold mb-2">กำลังอัปเดต...</p>
    <div class="relative w-full bg-gray-300 h-4 rounded overflow-hidden mb-2">
      <div id="progressBar" class="bg-blue-500 h-full w-0 transition-all"></div>
    </div>
    <p id="progressText">0%</p>
  </div>
</div>

<script>
function simulateUpdate(duration = 10000) {
    const popup = document.getElementById('updatePopup');
    const bar = document.getElementById('progressBar');
    const text = document.getElementById('progressText');

    popup.classList.remove('hidden');
    let progress = 0;
    const interval = 100;
    const increment = 100 / (duration / interval);

    const timer = setInterval(() => {
        progress += increment;
        if (progress >= 100) {
            progress = 100;
            clearInterval(timer);
            text.innerText = '✅ อัปเดตเสร็จสมบูรณ์!';

            // เรียก AJAX เพื่อบันทึกการอัปเดต
            fetch('save_version_update.php?version=<?= $latest['version_code'] ?? '' ?>');

            setTimeout(() => popup.classList.add('hidden'), 1500);
        } else {
            text.innerText = Math.floor(progress) + '%';
        }
        bar.style.width = progress + '%';
    }, interval);
}
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>