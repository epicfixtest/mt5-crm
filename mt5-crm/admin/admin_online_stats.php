<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require_once '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "👥 สถิติผู้ใช้งานออนไลน์";
ob_start();

// ออนไลน์ใน 5 นาที
$stmt = $pdo->query("SELECT COUNT(*) FROM user_online_logs WHERE last_active >= NOW() - INTERVAL 5 MINUTE");
$online_now = $stmt->fetchColumn();

// ออนไลน์วันนี้
$stmt = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM user_online_logs WHERE DATE(last_active) = CURDATE()");
$today_count = $stmt->fetchColumn();

// ออนไลน์เมื่อวาน
$stmt = $pdo->query("SELECT COUNT(DISTINCT member_id) FROM user_online_logs WHERE DATE(last_active) = CURDATE() - INTERVAL 1 DAY");
$yesterday_count = $stmt->fetchColumn();

$diff = $today_count - $yesterday_count;
$trend = $diff > 0 ? "เพิ่มขึ้น 🔼" : ($diff < 0 ? "ลดลง 🔽" : "เท่าเดิม");

// รายวันของเดือนนี้
$stmt = $pdo->query("
    SELECT DATE(last_active) as date, COUNT(DISTINCT member_id) as count
    FROM user_online_logs
    WHERE MONTH(last_active) = MONTH(CURDATE()) AND YEAR(last_active) = YEAR(CURDATE())
    GROUP BY DATE(last_active)
    ORDER BY date DESC
");
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="p-6 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
            <p class="text-gray-500">👁️‍🗨️ ออนไลน์ใน 5 นาที</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400"><?= $online_now ?> คน</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
            <p class="text-gray-500">📅 ออนไลน์วันนี้</p>
            <p class="text-2xl font-bold"><?= $today_count ?> คน</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
            <p class="text-gray-500">📊 เทียบเมื่อวาน</p>
            <p class="text-xl font-semibold"><?= $trend ?> (<?= $diff >= 0 ? '+' : '' ?><?= $diff ?> คน)</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-4 rounded shadow">
        <h2 class="text-lg font-semibold mb-4">📆 สถิติรายวันในเดือนนี้</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">วันที่</th>
                        <th class="px-4 py-2">จำนวนผู้เข้าใช้งาน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($daily_stats as $row): ?>
                        <tr>
                            <td class="px-4 py-2"><?= date('d M Y', strtotime($row['date'])) ?></td>
                            <td class="px-4 py-2"><?= $row['count'] ?> คน</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$content = ob_get_clean();
include 'layout_admin.php';
?>
