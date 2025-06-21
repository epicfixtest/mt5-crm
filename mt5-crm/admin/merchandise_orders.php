<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require_once '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "รายการแลกของชำร่วย";
ob_start();

// อัปเดตสถานะ
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE merchandise_redeem_history SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    header("Location: merchandise_orders.php");
    exit();
}

$orders = $pdo->query("
    SELECT h.*, m.name as member_name, i.name as item_name
    FROM merchandise_redeem_history h
    JOIN members m ON h.member_id = m.id
    JOIN merchandise_items i ON h.merchandise_item_id = i.id
    ORDER BY h.redeemed_at DESC
")->fetchAll();
?>

<div class="max-w-7xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-700">
                    <tr class="text-left">
                        <th class="p-2">วันที่แลก</th>
                        <th class="p-2">สมาชิก</th>
                        <th class="p-2">ของที่แลก</th>
                        <th class="p-2">ที่อยู่จัดส่ง</th>
                        <th class="p-2">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr class="border-b border-gray-700">
                            <td class="p-2"><?= $order['redeemed_at'] ?></td>
                            <td class="p-2"><?= htmlspecialchars($order['member_name']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($order['item_name']) ?></td>
                            <td class="p-2"><?= nl2br(htmlspecialchars($order['shipping_info'])) ?></td>
                            <td class="p-2">
                                <form method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="bg-gray-900 p-1 rounded">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                                        <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>จัดส่งแล้ว</option>
                                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>สำเร็จ</option>
                                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                                    </select>
                                    <button type="submit" name="update_status" class="bg-green-600 text-white px-2 py-1 rounded text-xs">บันทึก</button>
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