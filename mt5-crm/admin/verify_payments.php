<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require_once '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "ตรวจสอบการชำระเงิน";
ob_start();

// อัปเดตสถานะ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $pdo->beginTransaction();
    try {
        $transaction_id = $_POST['transaction_id'];
        $member_id = $_POST['member_id'];
        $amount = $_POST['amount'];
        $status = $_POST['status'];

        // อัปเดตสถานะ transaction เฉพาะเมื่อยังเป็น pending
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = ? WHERE id = ? AND status = 'pending'");
        $stmt->execute([$status, $transaction_id]);

        // ถ้าอนุมัติ (completed) ให้เพิ่ม coins ให้ user
        if ($status === 'completed' && $stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE members SET coins = coins + ? WHERE id = ?");
            $stmt->execute([$amount, $member_id]);
        }
        
        $pdo->commit();
    } catch(Exception $e) {
        $pdo->rollBack();
        // สามารถเพิ่มการแจ้งเตือน Error ได้
    }
    header("Location: verify_payments.php");
    exit();
}

$transactions = $pdo->query("
    SELECT t.*, m.username 
    FROM payment_transactions t
    JOIN members m ON t.member_id = m.id
    WHERE t.status = 'pending'
    ORDER BY t.created_at ASC
")->fetchAll();
?>
<div class="max-w-7xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">รายการที่รอตรวจสอบ</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-700">
                    <tr class="text-left">
                        <th class="p-2">เวลา</th>
                        <th class="p-2">ผู้ใช้</th>
                        <th class="p-2">จำนวนเงิน (บาท)</th>
                        <th class="p-2">สลิป</th>
                        <th class="p-2">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-400">ไม่มีรายการที่รอตรวจสอบ</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr class="border-b border-gray-700">
                        <td class="p-2 align-top"><?= $t['created_at'] ?></td>
                        <td class="p-2 align-top"><?= htmlspecialchars($t['username']) ?></td>
                        <td class="p-2 align-top"><?= number_format($t['amount'], 2) ?></td>
                        <td class="p-2 align-top">
                            <a href="../<?= htmlspecialchars($t['slip_path']) ?>" target="_blank" class="text-blue-400 underline">
                                <img src="../<?= htmlspecialchars($t['slip_path']) ?>" class="w-24 h-auto" alt="Slip">
                            </a>
                        </td>
                        <td class="p-2 align-top">
                            <form method="POST" class="flex flex-col gap-2">
                                <input type="hidden" name="transaction_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="member_id" value="<?= $t['member_id'] ?>">
                                <input type="hidden" name="amount" value="<?= $t['amount'] ?>">
                                <button type="submit" name="status" value="completed" class="bg-green-600 px-3 py-1 rounded w-full">อนุมัติ</button>
                                <button type="submit" name="status" value="rejected" class="bg-red-600 px-3 py-1 rounded w-full">ปฏิเสธ</button>
                                <input type="hidden" name="update_payment" value="1">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require 'layout_admin.php';
?>