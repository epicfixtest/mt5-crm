<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "รายงานสรุประบบ";
ob_start();

// ดึงข้อมูลรายวัน
$today = date('Y-m-d');
$month = date('m');
$year = date('Y');

// สมาชิกใหม่วันนี้
$stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$newMembersToday = $stmt->fetchColumn();

// การซื้อไฟล์วันนี้
$stmt = $pdo->prepare("SELECT COUNT(*) FROM redeem_history WHERE DATE(redeemed_at) = ?");
$stmt->execute([$today]);
$purchaseToday = $stmt->fetchColumn();

// การซื้อไฟล์เดือนนี้
$stmt = $pdo->prepare("SELECT COUNT(*) FROM redeem_history WHERE MONTH(redeemed_at) = ? AND YEAR(redeemed_at) = ?");
$stmt->execute([$month, $year]);
$purchaseMonth = $stmt->fetchColumn();

// ★★★ แก้ไขตรงนี้: เปลี่ยนจากการรวม Points เป็นการรวม Coins ที่ใช้ไป ★★★
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(r.coin_cost), 0)
    FROM redeem_history h
    JOIN redeem_items r ON h.redeem_item_id = r.id
    WHERE MONTH(h.redeemed_at) = ? AND YEAR(h.redeemed_at) = ?
");
$stmt->execute([$month, $year]);
$totalCoinsUsed = $stmt->fetchColumn();

// Top 5 ไฟล์ที่ถูกซื้อมากที่สุด
$stmt = $pdo->query("
    SELECT r.title, COUNT(h.id) as total
    FROM redeem_history h
    JOIN redeem_items r ON h.redeem_item_id = r.id
    GROUP BY h.redeem_item_id
    ORDER BY total DESC
    LIMIT 5
");
$topPurchasedItems = $stmt->fetchAll();
?>

<main class="p-6 max-w-7xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex flex-col">
            <h2 class="text-sm text-gray-500 dark:text-gray-400 mb-2">สมาชิกใหม่วันนี้</h2>
            <p class="text-3xl font-bold text-green-400"><?= number_format($newMembersToday) ?></p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex flex-col">
            <h2 class="text-sm text-gray-500 dark:text-gray-400 mb-2">การซื้อไฟล์วันนี้</h2>
            <p class="text-3xl font-bold text-blue-400"><?= number_format($purchaseToday) ?></p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex flex-col">
            <h2 class="text-sm text-gray-500 dark:text-gray-400 mb-2">การซื้อไฟล์เดือนนี้</h2>
            <p class="text-3xl font-bold text-yellow-400"><?= number_format($purchaseMonth) ?></p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow flex flex-col">
            <h2 class="text-sm text-gray-500 dark:text-gray-400 mb-2">Coins ที่ใช้ซื้อไฟล์เดือนนี้</h2>
            <p class="text-3xl font-bold text-pink-400"><?= number_format($totalCoinsUsed, 2) ?> Coins</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mt-8">
        <h2 class="text-lg font-semibold mb-4">📈 ไฟล์บริการที่ถูกซื้อมากที่สุด</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">
                <thead class="text-xs bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">บริการ</th>
                        <th class="px-4 py-2">จำนวนครั้งที่ซื้อ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <?php foreach ($topPurchasedItems as $item): ?>
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"><?= htmlspecialchars($item['title']) ?></td>
                            <td class="px-4 py-2"><?= number_format($item['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>