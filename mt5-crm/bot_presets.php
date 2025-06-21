<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "ดาวน์โหลด Bot Preset";
$member_id = $_SESSION['member_id'];
ob_start();

// 1. ค้นหาสินค้า (Bot) ทั้งหมดที่ลูกค้าเคยซื้อ
$purchased_bots_stmt = $pdo->prepare("SELECT DISTINCT redeem_item_id FROM redeem_history WHERE member_id = ?");
$purchased_bots_stmt->execute([$member_id]);
$purchased_bot_ids = $purchased_bots_stmt->fetchAll(PDO::FETCH_COLUMN);

$presets_by_bot = [];
if (!empty($purchased_bot_ids)) {
    // 2. ดึง Preset เฉพาะของ Bot ที่ลูกค้ามี
    $placeholders = implode(',', array_fill(0, count($purchased_bot_ids), '?'));
    $presets_stmt = $pdo->prepare("
        SELECT p.*, r.title as bot_name
        FROM bot_presets p
        JOIN redeem_items r ON p.redeem_item_id = r.id
        WHERE p.redeem_item_id IN ($placeholders)
        ORDER BY r.title, p.name ASC
    ");
    $presets_stmt->execute($purchased_bot_ids);
    $all_presets = $presets_stmt->fetchAll();
    
    // 3. จัดกลุ่ม Preset ตามชื่อ Bot
    foreach($all_presets as $preset) {
        $presets_by_bot[$preset['bot_name']][] = $preset;
    }
}
?>
<main class="p-6 max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8 text-center">🤖 Bot Presets</h1>
    <p class="text-center text-gray-400 mb-8">คุณสามารถดาวน์โหลด Preset สำหรับ Bot ที่คุณเป็นเจ้าของได้ที่นี่</p>
    
    <?php if (empty($presets_by_bot)): ?>
        <div class="text-center bg-gray-800 p-8 rounded-lg">
            <p class="text-gray-500">คุณยังไม่มีสิทธิ์ดาวน์โหลด Preset</p>
            <p class="text-gray-500 text-sm mt-2">กรุณาซื้อ Bot จาก <a href="redeem.php" class="text-blue-400 underline">ร้านค้าไฟล์</a> เพื่อรับสิทธิ์</p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($presets_by_bot as $bot_name => $presets): ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Presets สำหรับ: <?= htmlspecialchars($bot_name) ?></h2>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach($presets as $preset): ?>
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($preset['name']) ?></h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($preset['description']) ?></p>
                                </div>
                                <a href="download_preset.php?id=<?= $preset['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors">
                                    ดาวน์โหลด
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php
$content = ob_get_clean();
include 'layout.php';
?>