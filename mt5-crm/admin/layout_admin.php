<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/connectdb.php';
$currentPage = basename($_SERVER['PHP_SELF']);

// ดึงจำนวนผู้ใช้งานออนไลน์ใน 5 นาทีล่าสุด
$online_stmt = $pdo->query("SELECT COUNT(*) FROM user_online_logs WHERE last_active >= NOW() - INTERVAL 5 MINUTE");
$online_users = $online_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th" class="transition duration-300">

<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'MT5 CRM - Admin' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>

    <script>
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
        }
    </script>
</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white dark:bg-gray-800 shadow hidden md:block">
            <div class="p-4 text-lg font-semibold border-b dark:border-gray-700">
                🔐 Admin Panel
            </div>
            <nav class="mt-4 px-2 text-sm space-y-4">

    <div>
        <h3 class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ภาพรวม</h3>
        <a href="index.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'index.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>Dashboard</span>
        </a>
        <a href="reports.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'reports.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="bar-chart-3" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>รายงานระบบ</span>
        </a>
        <a href="admin_online_stats.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'admin_online_stats.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="users" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>สรุปผู้ใช้งาน</span>
        </a>
        <a href="logs.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'logs.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="file-text" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>ประวัติการใช้งาน</span>
        </a>
    </div>

    <div>
        <h3 class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">จัดการร้านค้า</h3>
        <a href="verify_payments.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'verify_payments.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="check-circle" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>ตรวจสอบการชำระเงิน</span>
        </a>
        <a href="manage_products.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_products.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="shopping-cart" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการสินค้า (ไฟล์)</span>
        </a>
         <a href="manage_merchandise.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_merchandise.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="gift" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการของชำร่วย</span>
        </a>
        <a href="merchandise_orders.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'merchandise_orders.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="package-check" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>รายการแลกของชำร่วย</span>
        </a>
    </div>

    <div>
        <h3 class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ผู้ใช้และการแนะนำ</h3>
        <a href="manage_member.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_member.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="user-cog" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการสมาชิก</span>
        </a>
        <a href="referral_earnings.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'referral_earnings.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="dollar-sign" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>รายได้จากการแนะนำ</span>
        </a>
        <a href="commission_setting.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'commission_setting.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="sliders-horizontal" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>ตั้งค่าคอมมิชชั่น</span>
        </a>
    </div>

    <div>
        <h3 class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">จัดการเนื้อหา</h3>
        <a href="announcements.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'announcements.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="megaphone" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการประกาศ</span>
        </a>
        <a href="manage_tutorials.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_tutorials.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="book-open" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการคู่มือ</span>
        </a>
        <a href="manage_live.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_live.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="video" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการ Live</span>
        </a>
        <a href="manage_about.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_about.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="info" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>แก้ไขเกี่ยวกับเรา</span>
        </a>
        <a href="manage_contact.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_contact.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="phone" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>แก้ไขช่องทางติดต่อ</span>
        </a>
        <a href="manage_promotions.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_promotions.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="star" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการโปรโมชั่น</span>
        </a>
        <a href="manage_presets.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_presets.php' ? '...' : '...' ?>">
            <i data-lucide="settings-2" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการ Preset</span>
        </a>
        <a href="manage_activities.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'manage_activities.php' ? '...' : '...' ?>">
            <i data-lucide="award" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>จัดการกิจกรรม</span>
        </a>
    </div>

    <div>
        <h3 class="px-2 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ระบบ</h3>
        <a href="update_manage.php" class="flex items-center px-2 py-2.5 rounded-lg <?= $currentPage == 'update_manage.php' ? 'bg-gray-200 dark:bg-gray-700 font-semibold' : 'hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <i data-lucide="arrow-up-circle" class="w-5 h-5 mr-3 text-gray-500"></i>
            <span>อัปเดตเวอร์ชัน</span>
        </a>
    </div>
</nav>

        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <header class="bg-white dark:bg-gray-800 shadow px-4 py-3 flex items-center justify-between">
                <h1 class="font-semibold text-lg"><?= $pageTitle ?? '' ?></h1>
                <div class="flex items-center gap-4">
                    <span id="onlineCount" class="text-sm text-green-600 dark:text-green-400">
                        👥 ออนไลน์: <?= $online_users ?> คน
                    </span>
                    <button onclick="toggleDarkMode()" class="text-sm px-3 py-1 rounded bg-gray-200 dark:bg-gray-700">
                        🌗 Toggle Theme
                    </button>
                </div>

            </header>

            <main class="p-4">
                <?= $content ?? '<p>ไม่มีเนื้อหา</p>' ?>
            </main>
        </div>
    </div>
    <script>
        setInterval(() => {
            fetch('get_online_count.php')
                .then(res => res.text())
                .then(count => {
                    const span = document.getElementById('onlineCount');
                    if (span) span.innerText = `👥 ออนไลน์: ${count} คน`;
                });
        }, 30000);
    </script>
    
    <script>
    lucide.createIcons();
</script>

</body>

</html>