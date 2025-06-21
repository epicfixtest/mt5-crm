<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "แลกของรางวัล";
$member_id = $_SESSION['member_id'];
ob_start();

// ดึงแต้มปัจจุบัน
$stmt = $pdo->prepare("SELECT points FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$current_points = $stmt->fetchColumn() ?? 0;

// ดึงรายการของชำร่วย
$items = $pdo->query("SELECT * FROM merchandise_items WHERE stock > 0 ORDER BY created_at DESC")->fetchAll();
$redeem_feedback = null;

// จัดการการแลก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_item_id'])) {
    $item_id = intval($_POST['redeem_item_id']);
    $shipping_info = trim($_POST['shipping_info']);

    $stmt = $pdo->prepare("SELECT * FROM merchandise_items WHERE id = ? AND stock > 0");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if ($item && $current_points >= $item['points_required'] && !empty($shipping_info)) {
        $pdo->beginTransaction();
        try {
            // 1. หักแต้มผู้ใช้
            $pdo->prepare("UPDATE members SET points = points - ? WHERE id = ?")->execute([$item['points_required'], $member_id]);
            // 2. ลดสต็อก
            $pdo->prepare("UPDATE merchandise_items SET stock = stock - 1 WHERE id = ?")->execute([$item_id]);
            // 3. บันทึกประวัติ
            $pdo->prepare("INSERT INTO merchandise_redeem_history (member_id, merchandise_item_id, points_used, shipping_info, status) VALUES (?, ?, ?, ?, 'pending')")
                ->execute([$member_id, $item_id, $item['points_required'], $shipping_info]);
            
            $pdo->commit();
            
            $_SESSION['redeem_feedback'] = ['type' => 'success', 'message' => 'แลกของรางวัลสำเร็จ! ทีมงานจะดำเนินการจัดส่งในเร็วที่สุด'];
            header("Location: redeem_merchandise.php");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $redeem_feedback = ['type' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
        }
    } else {
        $redeem_feedback = ['type' => 'error', 'message' => 'แต้มของคุณไม่เพียงพอ, สินค้าหมด, หรือไม่ได้กรอกที่อยู่จัดส่ง'];
    }
}

// ตรวจสอบ feedback จาก session
if (isset($_SESSION['redeem_feedback'])) {
    $redeem_feedback = $_SESSION['redeem_feedback'];
    unset($_SESSION['redeem_feedback']);
}
?>

<div class="space-y-8">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="gift" class="text-blue-400"></i>
            แลกของรางวัล
        </h1>
        <p class="text-gray-400 mt-1">ใช้ Points เพื่อแลกรับของรางวัลสุดพิเศษ</p>
    </div>

    <div class="max-w-sm mx-auto bg-gray-800 border border-gray-700/50 rounded-2xl p-6 shadow-lg flex items-center gap-5">
        <div class="p-4 bg-green-500/20 rounded-xl">
            <i data-lucide="star" class="w-9 h-9 text-green-400"></i>
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-400">Points ของคุณ</h3>
            <p class="text-4xl font-semibold text-white mt-1"><?= number_format($current_points) ?></p>
        </div>
    </div>
    
    <?php if ($redeem_feedback): ?>
        <div class="max-w-3xl mx-auto p-4 rounded-lg text-sm flex items-center gap-3 <?= $redeem_feedback['type'] === 'error' ? 'bg-red-900/50 text-red-300 border border-red-700' : 'bg-green-900/50 text-green-300 border border-green-700' ?>">
             <i data-lucide="<?= $redeem_feedback['type'] === 'error' ? 'x-circle' : 'check-circle' ?>"></i>
            <span><?= htmlspecialchars($redeem_feedback['message']) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($items as $item): 
            $can_redeem = $current_points >= $item['points_required'];
        ?>
            <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg flex flex-col hover:border-blue-500/50 transition-colors duration-300">
                <img src="<?= htmlspecialchars($item['image_path']) ?>" class="w-full h-48 object-cover rounded-t-xl" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="p-5 flex flex-col flex-grow">
                    <div class="flex-grow mb-4">
                        <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($item['name']) ?></h2>
                        <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($item['description']) ?></p>
                    </div>
                    
                    <div class="mb-5 space-y-2">
                         <p class="text-2xl font-bold text-green-400 flex items-center gap-2">
                           <i data-lucide="star" class="w-6 h-6"></i>
                           <span><?= number_format($item['points_required']) ?></span>
                           <span class="text-base font-normal text-gray-400">Points</span>
                        </p>
                        <p class="text-xs text-gray-500">
                           คงเหลือในสต็อก: <?= number_format($item['stock']) ?> ชิ้น
                        </p>
                    </div>
                    
                    <div class="mt-auto">
                        <button 
                            onclick="openRedeemModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['points_required'] ?>)"
                            class="w-full flex items-center justify-center gap-2 text-center font-bold py-3 rounded-lg transition-colors duration-200
                            <?= $can_redeem 
                                ? 'bg-blue-600 hover:bg-blue-700 text-white' 
                                : 'bg-gray-600 text-gray-400 cursor-not-allowed' 
                            ?>"
                            <?= !$can_redeem ? 'disabled' : '' ?>>
                            <?php if($can_redeem): ?>
                                <i data-lucide="gift" class="w-5 h-5"></i>
                                <span>แลกของรางวัล</span>
                            <?php else: ?>
                                <i data-lucide="x-circle" class="w-5 h-5"></i>
                                <span>แต้มไม่เพียงพอ</span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="redeemModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
    <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-2xl w-full max-w-lg m-4 transform transition-transform duration-300 scale-95">
        <div class="p-6 border-b border-gray-700 flex justify-between items-center">
             <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <i data-lucide="package-check" class="text-blue-400"></i>
                ยืนยันการแลกของรางวัล
            </h2>
            <button onclick="closeRedeemModal()" class="text-gray-400 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
       
        <form method="POST">
            <div class="p-6 space-y-5">
                <input type="hidden" name="redeem_item_id" id="modalItemId">

                <div class="bg-gray-900/50 p-4 rounded-lg">
                    <p class="text-sm text-gray-400">คุณกำลังจะแลก</p>
                    <p id="modalItemName" class="text-lg font-semibold text-white"></p>
                    <p class="text-lg font-bold text-green-400 mt-1 flex items-center gap-2">
                        <i data-lucide="star"></i>
                        <span id="modalItemPoints"></span>
                        <span>Points</span>
                    </p>
                </div>
                
                <div>
                    <label for="shipping_info" class="block mb-2 text-sm font-medium text-gray-300">
                        กรอกชื่อ-ที่อยู่ และเบอร์โทรศัพท์สำหรับจัดส่ง
                    </label>
                    <textarea id="shipping_info" name="shipping_info" rows="4" required class="w-full p-3 rounded-lg bg-gray-700 border border-gray-600 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-800 border-t border-gray-700 flex justify-end gap-4 rounded-b-xl">
                <button type="button" onclick="closeRedeemModal()" class="px-6 py-2 rounded-lg bg-gray-600 hover:bg-gray-500 text-white font-semibold transition-colors">ยกเลิก</button>
                <button type="submit" name="redeem" class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-colors">ยืนยันการแลก</button>
            </div>
        </form>
    </div>
</div>

<script>
const redeemModal = document.getElementById('redeemModal');
const modalContent = redeemModal.querySelector('.transform');

function openRedeemModal(id, name, points) {
    document.getElementById('modalItemId').value = id;
    document.getElementById('modalItemName').innerText = name;
    document.getElementById('modalItemPoints').innerText = Number(points).toLocaleString();
    
    redeemModal.classList.remove('hidden');
    redeemModal.classList.add('flex');
    setTimeout(() => {
        redeemModal.style.opacity = '1';
        modalContent.classList.remove('scale-95');
    }, 10);
}

function closeRedeemModal() {
    modalContent.classList.add('scale-95');
    redeemModal.style.opacity = '0';
    setTimeout(() => {
        redeemModal.classList.add('hidden');
        redeemModal.classList.remove('flex');
    }, 300);
}

// Close modal on escape key press
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !redeemModal.classList.contains('hidden')) {
        closeRedeemModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>