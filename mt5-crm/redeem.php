<?php
// --- PHP เดิมจากไฟล์ของคุณ (ไม่แก้ไข) ---
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "ร้านค้า (ซื้อไฟล์)";
$member_id = $_SESSION['member_id'];
ob_start();

$result_msg = '';
$result_type = 'error';

// จัดการการ "ซื้อ"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_item_id'])) {
    $item_id = intval($_POST['purchase_item_id']);
    
    // ดึงข้อมูลล่าสุดของผู้ใช้และสินค้าใน transaction
    $pdo->beginTransaction();
    try {
        // Lock แถวของ member และ item เพื่อป้องกัน race condition
        $stmt_member = $pdo->prepare("SELECT points, coins FROM members WHERE id = ? FOR UPDATE");
        $stmt_member->execute([$member_id]);
        $user = $stmt_member->fetch();

        $stmt_item = $pdo->prepare("SELECT coin_cost, point_reward FROM redeem_items WHERE id = ? FOR UPDATE");
        $stmt_item->execute([$item_id]);
        $item = $stmt_item->fetch();

        if ($item && $user && $user['coins'] >= $item['coin_cost']) {
            // 1. หัก Coins
            $pdo->prepare("UPDATE members SET coins = coins - ? WHERE id = ?")->execute([$item['coin_cost'], $member_id]);
            // 2. เพิ่ม Points
            $pdo->prepare("UPDATE members SET points = points + ? WHERE id = ?")->execute([$item['point_reward'], $member_id]);
            // 3. บันทึกประวัติ (ยังใช้ตารางเดิมได้)
            $pdo->prepare("INSERT INTO redeem_history (member_id, redeem_item_id) VALUES (?, ?)")->execute([$member_id, $item_id]);
            
            $pdo->commit();
            
            $_SESSION['purchase_feedback'] = ['type' => 'success', 'message' => 'ซื้อไฟล์สำเร็จ! คุณสามารถดาวน์โหลดได้จากประวัติการซื้อ'];
            header("Location: redeem_history.php");
            exit();

        } else {
            $pdo->rollBack();
            $result_msg = "Coins ของคุณไม่เพียงพอ!";
            $result_type = 'error';
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $result_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $result_type = 'error';
    }
}

// ดึงแต้มและเหรียญปัจจุบัน (นอก transaction)
$stmt = $pdo->prepare("SELECT points, coins FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$balances = $stmt->fetch();
$current_points = $balances['points'] ?? 0;
$current_coins = $balances['coins'] ?? 0;


// ดึงรายการไฟล์ทั้งหมด
$items = $pdo->query("SELECT * FROM redeem_items ORDER BY created_at DESC")->fetchAll();
$redeemed_items = $pdo->query("SELECT redeem_item_id FROM redeem_history WHERE member_id = $member_id")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="space-y-8">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="shopping-cart" class="text-blue-400"></i>
            ร้านค้า
        </h1>
        <p class="text-gray-400 mt-1">ใช้ Coins เพื่อซื้อไฟล์และรับ Points สะสม</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl mx-auto">
        <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-5 shadow-lg flex items-center gap-4">
            <div class="p-3 bg-yellow-500/20 rounded-lg">
                <i data-lucide="coins" class="w-7 h-7 text-yellow-400"></i>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-400">Coins ของคุณ</h3>
                <p class="text-2xl font-semibold text-white mt-1"><?= number_format($current_coins, 2) ?></p>
                <a href="topup.php" class="text-xs text-blue-400 hover:underline mt-1">เติม Coins</a>
            </div>
        </div>
        <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-5 shadow-lg flex items-center gap-4">
            <div class="p-3 bg-green-500/20 rounded-lg">
                <i data-lucide="star" class="w-7 h-7 text-green-400"></i>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-400">Points ของคุณ</h3>
                <p class="text-2xl font-semibold text-white mt-1"><?= number_format($current_points) ?></p>
                 <a href="redeem_merchandise.php" class="text-xs text-blue-400 hover:underline mt-1">แลกของรางวัล</a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($result_msg)): ?>
        <div class="max-w-2xl mx-auto p-4 rounded-lg text-sm flex items-center gap-3 <?= $result_type === 'error' ? 'bg-red-900/50 text-red-300 border border-red-700' : 'bg-green-900/50 text-green-300 border border-green-700' ?>">
             <i data-lucide="<?= $result_type === 'error' ? 'x-circle' : 'check-circle' ?>"></i>
            <span><?= htmlspecialchars($result_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($items as $item): ?>
            <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg flex flex-col hover:border-blue-500/50 transition-colors">
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex-grow mb-6">
                        <i data-lucide="file-code-2" class="w-10 h-10 text-blue-400 mb-4"></i>
                        <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($item['title']) ?></h2>
                    </div>
                    
                    <div class="mb-6 space-y-2">
                        <p class="text-2xl font-bold text-yellow-400 flex items-center gap-2">
                           <i data-lucide="coins" class="w-5 h-5"></i>
                           <span><?= number_format($item['coin_cost'], 2) ?></span>
                           <span class="text-base font-normal text-gray-400">Coins</span>
                        </p>
                        <p class="text-sm text-green-400 flex items-center gap-2">
                           <i data-lucide="plus-circle" class="w-4 h-4"></i>
                           <span>รับ <?= number_format($item['point_reward']) ?> Points</span>
                        </p>
                    </div>
                    
                    <div class="mt-auto">
                    <?php if (in_array($item['id'], $redeemed_items)): ?>
                        <a href="download.php?id=<?= $item['id'] ?>" class="w-full flex items-center justify-center gap-2 text-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition-colors">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>ดาวน์โหลด</span>
                        </a>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="purchase_item_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 font-bold py-3 rounded-lg transition-colors
                                <?= ($current_coins < $item['coin_cost']) 
                                    ? 'bg-gray-600 text-gray-400 cursor-not-allowed' 
                                    : 'bg-blue-600 hover:bg-blue-700 text-white' 
                                ?>" 
                                <?= ($current_coins < $item['coin_cost']) ? 'disabled' : '' ?>>
                                <?php if($current_coins < $item['coin_cost']): ?>
                                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    <span>Coins ไม่พอ</span>
                                <?php else: ?>
                                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                    <span>ซื้อตอนนี้</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>