<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "รายได้จากการแนะนำสมาชิก";
ob_start();

// ดึงข้อมูลรายได้จากการแนะนำ
$stmt = $pdo->query("
    SELECT e.*, 
           referrer.username AS referrer_name,
           referred.username AS referred_name
    FROM referral_earnings e
    JOIN members referrer ON e.referrer_id = referrer.id
    JOIN members referred ON e.referred_id = referred.id
    ORDER BY e.created_at DESC
");
$earnings = $stmt->fetchAll();
?>

<main class="p-6 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <div class="overflow-x-auto">
            <table id="earningsTable" class="min-w-full text-sm text-left text-gray-700 dark:text-gray-200">
                <thead class="text-xs uppercase bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">ผู้แนะนำ</th>
                        <th class="px-4 py-2">ผู้ถูกแนะนำ</th>
                        <th class="px-4 py-2">บริการ</th>
                        <th class="px-4 py-2">ค่าคอมมิชชั่น (บาท)</th>
                        <th class="px-4 py-2">วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <?php foreach ($earnings as $earning): ?>
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"><?= htmlspecialchars($earning['referrer_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($earning['referred_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($earning['service_name'] ?: '-') ?></td>
                            <td class="px-4 py-2"><?= number_format($earning['amount'], 2) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($earning['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#earningsTable').DataTable();
});
</script>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>
