<?php
// --- PHP เดิมจากไฟล์ของคุณ (ไม่แก้ไข) ---
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "เติมเงิน (Coins)";
$member_id = $_SESSION['member_id'];
ob_start();

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slip'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0 && isset($_FILES['slip']) && $_FILES['slip']['error'] === 0) {
        $upload_dir = 'uploads/slips/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = $member_id . '-' . time() . '-' . basename($_FILES['slip']['name']);
        $target_path = $upload_dir . $filename;

        // Check if file is an image
        $check = getimagesize($_FILES['slip']['tmp_name']);
        if($check !== false) {
            if (move_uploaded_file($_FILES['slip']['tmp_name'], $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO payment_transactions (member_id, amount, slip_path) VALUES (?, ?, ?)");
                $stmt->execute([$member_id, $amount, $target_path]);
                $success_msg = "ส่งสลิปสำเร็จ! กรุณารอทีมงานตรวจสอบสักครู่ ระบบจะอัปเดต Coins ให้ท่านภายใน 24 ชม.";
            } else {
                $error_msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            }
        } else {
            $error_msg = "ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ";
        }
    } else {
        $error_msg = "กรุณากรอกจำนวนเงินและเลือกไฟล์สลิปให้ถูกต้อง";
    }
}
?>

<div class="max-w-6xl mx-auto space-y-8">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="credit-card" class="text-blue-400"></i>
            เติมเงิน (Coins)
        </h1>
        <p class="text-gray-400 mt-1">ทำตามขั้นตอนด้านล่างเพื่อเติม Coins เข้าสู่ระบบ</p>
    </div>

    <?php if(!empty($success_msg)): ?>
        <div class="p-4 text-sm rounded-lg flex items-center gap-3 bg-green-900/50 text-green-300 border border-green-700">
            <i data-lucide="check-circle"></i><span><?= htmlspecialchars($success_msg) ?></span>
        </div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div class="p-4 text-sm rounded-lg flex items-center gap-3 bg-red-900/50 text-red-300 border border-red-700">
            <i data-lucide="x-circle"></i><span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-white flex items-center gap-2">
                <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</span>
                <span>ข้อมูลการโอนเงิน</span>
            </h2>
            <div class="space-y-4 text-gray-300">
                <div class="flex justify-between items-center">
                    <span>ธนาคาร:</span>
                    <span class="font-semibold text-white">กสิกรไทย</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>เลขที่บัญชี:</span>
                    <span class="font-semibold text-white">123-4-56789-0</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>ชื่อบัญชี:</span>
                    <span class="font-semibold text-white">บริษัท เอพิคเทส จำกัด</span>
                </div>
                <div class="mt-6 p-4 bg-white rounded-lg">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=promptpay-0812345678-100.00" alt="QR Code" class="mx-auto">
                </div>
            </div>
        </div>

        <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-white flex items-center gap-2">
                <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</span>
                <span>แจ้งการชำระเงิน</span>
            </h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-300">จำนวนเงินที่โอน (บาท)</label>
                    <input type="number" id="amount" step="0.01" name="amount" required class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="slip" class="block mb-2 text-sm font-medium text-gray-300">แนบสลิปการโอน (รูปภาพเท่านั้น)</label>
                    <input type="file" id="slip" name="slip" required accept="image/*" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-gray-200 hover:file:bg-gray-600 transition-colors cursor-pointer">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center gap-2 transition-colors duration-200">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    <span>ยืนยันการแจ้งโอน</span>
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>