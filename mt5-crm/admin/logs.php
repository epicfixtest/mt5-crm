<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "ประวัติการใช้งานระบบ";
ob_start();

// ดึง Login Logs
$stmt = $pdo->query("
    SELECT l.*, m.username
    FROM login_logs l
    JOIN members m ON l.member_id = m.id
    ORDER BY l.login_time DESC
    LIMIT 100
");
$loginLogs = $stmt->fetchAll();

// ดึง Download Logs
$stmt = $pdo->query("
    SELECT d.*, m.username
    FROM download_logs d
    JOIN members m ON d.member_id = m.id
    ORDER BY d.download_time DESC
    LIMIT 100
");
$downloadLogs = $stmt->fetchAll();
?>

<main class="p-6 max-w-7xl mx-auto space-y-10">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <!-- Login Logs -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">🔐 ประวัติการเข้าใช้งาน</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">
                <thead class="text-xs bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">ผู้ใช้</th>
                        <th class="px-4 py-2">IP Address</th>
                        <th class="px-4 py-2">เวลาเข้าใช้งาน</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <?php foreach ($loginLogs as $log): ?>
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"><?= htmlspecialchars($log['username']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($log['ip_address']) ?></td>
                            <td class="px-4 py-2"><?= date('d/m/Y H:i', strtotime($log['login_time'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Download Logs -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow mt-10">
        <h2 class="text-lg font-semibold mb-4">📥 ประวัติการดาวน์โหลดไฟล์</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-200">
                <thead class="text-xs bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">ผู้ใช้</th>
                        <th class="px-4 py-2">ชื่อไฟล์</th>
                        <th class="px-4 py-2">เวลาโหลด</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900">
                    <?php foreach ($downloadLogs as $log): ?>
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-2"><?= htmlspecialchars($log['username']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($log['file_name']) ?></td>
                            <td class="px-4 py-2"><?= date('d/m/Y H:i', strtotime($log['download_time'])) ?></td>
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
