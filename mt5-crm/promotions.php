<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "โปรโมชั่น";
ob_start();

// ดึงโปรโมชั่นที่เปิดใช้งานอยู่เท่านั้น
$promotions = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
?>
<main class="p-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8 text-center">⭐ โปรโมชั่นพิเศษสำหรับคุณ ⭐</h1>

    <?php if (empty($promotions)): ?>
        <p class="text-center text-gray-500">ยังไม่มีโปรโมชั่นในขณะนี้</p>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($promotions as $promo): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden flex flex-col md:flex-row">
                <img src="<?= htmlspecialchars($promo['image_path']) ?>" class="md:w-1/3 h-64 md:h-auto object-cover" alt="Promotion Image">
                <div class="p-6 flex flex-col justify-center">
                    <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white"><?= htmlspecialchars($promo['title']) ?></h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        <?= nl2br(htmlspecialchars($promo['description'])) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php
$content = ob_get_clean();
include 'layout.php';
?>