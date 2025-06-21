<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "ประวัติการแลกของรางวัล";
$member_id = $_SESSION['member_id'];
ob_start();

// ดึงประวัติการแลกของรางวัล
$stmt = $pdo->prepare(
    "SELECT 
        rh.id,
        rh.points_used,
        rh.shipping_info,
        rh.status,
        rh.redeemed_at,
        mi.name AS item_name,
        mi.image_path
    FROM merchandise_redeem_history rh
    JOIN merchandise_items mi ON rh.merchandise_item_id = mi.id
    WHERE rh.member_id = ?
    ORDER BY rh.redeemed_at DESC"
);
$stmt->execute([$member_id]);
$history = $stmt->fetchAll();

// ฟังก์ชันสำหรับสร้าง Badge สถานะ
function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-300">รอดำเนินการ</span>';
        case 'shipped':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-300">จัดส่งแล้ว</span>';
        case 'cancelled':
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-300">ยกเลิก</span>';
        default:
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-500/20 text-gray-300">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="space-y-8 max-w-4xl mx-auto">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="history" class="text-blue-400"></i>
            ประวัติการแลกของรางวัล
        </h1>
        <p class="text-gray-400 mt-1">รายการของรางวัลทั้งหมดที่คุณเคยแลก</p>
    </div>

    <div class="space-y-4">
        <?php if (empty($history)): ?>
            <div class="text-center py-12 bg-gray-800/50 rounded-xl">
                 <i data-lucide="inbox" class="w-16 h-16 mx-auto text-gray-500"></i>
                <h3 class="mt-4 text-xl font-semibold text-white">ยังไม่มีประวัติการแลก</h3>
                <p class="mt-1 text-gray-400">เมื่อคุณแลกของรางวัล รายการจะแสดงที่นี่</p>
            </div>
        <?php else: ?>
            <?php foreach ($history as $item): ?>
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg p-4 transition-transform hover:scale-[1.02] duration-300 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    
                    <img src="<?= htmlspecialchars($item['image_path']) ?>" 
                         class="w-full sm:w-32 h-32 object-cover rounded-lg flex-shrink-0" 
                         alt="<?= htmlspecialchars($item['item_name']) ?>">
                    
                    <div class="flex-grow">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-start">
                            <div>
                                <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($item['item_name']) ?></h2>
                                <p class="text-sm text-gray-400 mt-1">
                                    แลกเมื่อ: <?= date("d M Y, H:i", strtotime($item['redeemed_at'])) ?>
                                </p>
                            </div>
                            <div class="mt-2 sm:mt-0">
                                <?= getStatusBadge($item['status']) ?>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-700/60 space-y-3">
                             <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400 font-medium">Points ที่ใช้:</span>
                                <span class="font-semibold text-green-400 flex items-center gap-1.5">
                                    <i data-lucide="star" class="w-4 h-4"></i> 
                                    <?= number_format($item['points_used']) ?>
                                </span>
                            </div>
                            <div class="flex flex-col text-sm">
                                <span class="text-gray-400 font-medium mb-1">ข้อมูลจัดส่ง:</span>
                                <p class="text-gray-300 bg-gray-900/50 p-3 rounded-md whitespace-pre-wrap text-xs">
                                    <?= htmlspecialchars($item['shipping_info']) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>