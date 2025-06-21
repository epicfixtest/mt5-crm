<?php
// --- ส่วน PHP เดิมของคุณทั้งหมด จะถูกคงไว้เหมือนเดิมทุกประการ ---

// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/includes/connectdb.php'; //

// 2. บันทึกสถานะออนไลน์ของผู้ใช้ที่ล็อกอินอยู่
if (isset($_SESSION['member_id'])) {
    $stmt = $pdo->prepare("INSERT INTO user_online_logs (member_id, last_active)
                           VALUES (?, NOW())
                           ON DUPLICATE KEY UPDATE last_active = NOW()"); //
    $stmt->execute([$_SESSION['member_id']]);
}

// 3. ดึงข้อมูล VPS ของผู้ใช้
$vps_info = null;
if (isset($_SESSION['member_id'])) {
    $stmt = $pdo->prepare("SELECT vps_ip, vps_user, vps_password FROM members WHERE id = ?"); //
    $stmt->execute([$_SESSION['member_id']]);
    $vps_info = $stmt->fetch();
}

// 4. ตรวจสอบสิทธิ์ Admin (ถ้าจำเป็น)
$isAdmin = (isset($_SESSION['member_role']) && in_array($_SESSION['member_role'], ['Admin', 'SuperAdmin'])); //

?>
<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'MT5 CRM') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #e5e7eb; }
        ::-webkit-scrollbar-thumb { background: #9ca3af; border-radius: 6px; }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            border-radius: 8px;
        }
        .sidebar-link-default {
            color: #94a3b8;
        }
        .sidebar-link-default:hover {
            background-color: #1e293b;
            color: #ffffff;
        }
        .sidebar-link-active {
            background-color: #2563eb22;
            border-left-color: #3b82f6;
            color: #ffffff;
            font-weight: 500;
        }
        .fade-in {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.5s ease forwards;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>
<body class="text-gray-900 bg-white">
<div class="flex min-h-screen">
    <aside class="w-64 bg-slate-900 text-white shadow hidden md:block border-r border-slate-800">
        <div class="flex flex-col h-full">
            <div class="h-16 flex items-center justify-center border-b border-slate-800">
                <h1 class="text-2xl font-bold">MT5 CRM</h1>
            </div>
            <nav class="flex-1 p-3 text-sm space-y-2 overflow-y-auto">
                <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
                <div class="space-y-1">
                        <h3 class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mt-4 mb-2">เมนูหลัก</h3>
                        <a href="index.php" class="sidebar-link <?= $currentPage == 'index.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i><span>Dashboard</span></a>
                        <a href="history.php" class="sidebar-link <?= $currentPage == 'history.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="history" class="w-5 h-5 mr-3"></i><span>ประวัติการเทรด</span></a>
                        <a href="trade.php" class="sidebar-link <?= $currentPage == 'trade.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="swords" class="w-5 h-5 mr-3"></i><span>สร้างคำสั่งซื้อขาย</span></a>
                        <a href="news.php" class="sidebar-link <?= $currentPage == 'news.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="newspaper" class="w-5 h-5 mr-3"></i><span>ข่าวสาร</span></a>
                        <a href="tradingview.php" class="sidebar-link <?= $currentPage == 'tradingview.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="candlestick-chart" class="w-5 h-5 mr-3"></i><span>กราฟหุ้น</span></a>
                        <?php if ($vps_info && !empty($vps_info['vps_ip'])): ?>
                            <?php $vps_url = sprintf("http://103.22.181.99:8080/?server=%s&user=%s&password=%s&connect=1", urlencode($vps_info['vps_ip']), urlencode($vps_info['vps_user']), urlencode($vps_info['vps_password'])); ?>
                            <a href="<?= $vps_url ?>" target="_blank" class="sidebar-link sidebar-link-default"><i data-lucide="server" class="w-5 h-5 mr-3"></i><span>เข้าสู่ระบบ VPS</span></a>
                        <?php endif; ?>
                        <a href="activities.php" class="sidebar-link <?= $currentPage == 'activities.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="award" class="w-5 h-5 mr-3"></i><span>กิจกรรม</span></a>
                    </div>

                    <div class="space-y-1">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4 mb-2">ร้านค้า & ของรางวัล</h3>
                        <a href="topup.php" class="sidebar-link <?= $currentPage == 'topup.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="credit-card" class="w-5 h-5 mr-3"></i><span>เติม Coins</span></a>
                        <a href="redeem.php" class="sidebar-link <?= $currentPage == 'redeem.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="shopping-cart" class="w-5 h-5 mr-3"></i><span>ร้านค้าไฟล์</span></a>
                        <a href="redeem_merchandise.php" class="sidebar-link <?= $currentPage == 'redeem_merchandise.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="gift" class="w-5 h-5 mr-3"></i><span>แลกของชำร่วย</span></a>
                        <a href="redeem_history.php" class="sidebar-link <?= $currentPage == 'redeem_history.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="archive" class="w-5 h-5 mr-3"></i><span>ประวัติการซื้อ/แลก</span></a>
                    </div>

                    <div class="space-y-1">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4 mb-2">ช่วยเหลือ & ข้อมูล</h3>
                        <a href="tutorials.php" class="sidebar-link <?= $currentPage == 'tutorials.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="book-open" class="w-5 h-5 mr-3"></i><span>คู่มือการใช้งาน</span></a>
                        <a href="live.php" class="sidebar-link <?= $currentPage == 'live.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="video" class="w-5 h-5 mr-3"></i><span>Live</span></a>
                        <a href="contact.php" class="sidebar-link <?= $currentPage == 'contact.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="phone" class="w-5 h-5 mr-3"></i><span>ช่องทางติดต่อ</span></a>
                        <a href="check_update.php" class="sidebar-link <?= $currentPage == 'check_update.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="refresh-cw" class="w-5 h-5 mr-3"></i><span>อัปเดตระบบ</span></a>
                        <a href="promotions.php" class="sidebar-link <?= $currentPage == 'promotions.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="star" class="w-5 h-5 mr-3"></i><span>โปรโมชั่น</span></a>
                        <a href="bot_presets.php" class="sidebar-link <?= $currentPage == 'bot_presets.php' ? 'sidebar-link-active' : 'sidebar-link-default' ?>"><i data-lucide="download-cloud" class="w-5 h-5 mr-3"></i><span>ดาวน์โหลด Preset</span></a>
                    </div>
                </nav>
            </div>
        </aside>

            <div class="flex-1 flex flex-col">
        <header class="bg-slate-100 px-6 py-3 flex items-center justify-between border-b border-slate-200 h-16 relative z-10">
            <h1 class="font-semibold text-lg text-slate-900 fade-in"><?= htmlspecialchars($pageTitle ?? 'MT5 CRM') ?></h1>
            <div class="flex items-center gap-6 fade-in">
                <span id="onlineCount" class="text-sm text-green-600 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4"></i><span>ออนไลน์: ...</span>
                </span>
                <div class="relative z-20">
                    <button id="notifBtn" type="button" class="relative text-slate-600 hover:text-slate-900 focus:outline-none">
                        <i data-lucide="bell" class="w-6 h-6"></i>
                        <span id="notifCount" class="absolute -top-1 -right-1.5 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded-full hidden">0</span>
                    </button>
                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 max-h-96 overflow-y-auto bg-white shadow-lg rounded-lg z-50 border border-slate-300">
                        <div class="p-3 font-semibold border-b border-slate-200">📣 ประกาศใหม่</div>
                        <ul id="notifList" class="divide-y divide-slate-200">
                            <li class="p-3 text-center text-sm text-slate-400">กำลังโหลด...</li>
                        </ul>
                    </div>
                </div>
                <div class="relative z-20">
                    <button id="userDropdownBtn" type="button" class="bg-slate-200 text-slate-900 px-4 py-2 rounded-lg hover:bg-slate-300 flex items-center gap-2 focus:outline-none">
                        <span><?= htmlspecialchars($_SESSION['member_name'] ?? 'User') ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </button>
                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg z-50">
                        <a href="profile.php" class="block px-4 py-2 hover:bg-slate-100">โปรไฟล์ของฉัน</a>
                        <a href="logout.php" class="block px-4 py-2 text-red-500 hover:bg-red-100 border-t border-slate-200">ออกจากระบบ</a>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 p-6 overflow-y-auto bg-white text-gray-900 fade-in">
            <?= $content ?? '<p>ไม่มีเนื้อหา</p>' ?>
        </main>
    </div>
</div>

<script>
    lucide.createIcons();

    function updateOnlineCount() {
        fetch('get_online_count.php')
            .then(res => res.text())
            .then(count => {
                const span = document.getElementById('onlineCount')?.querySelector('span');
                if (span) span.innerText = `ออนไลน์: ${count} คน`;
            });
    }
    updateOnlineCount();
    setInterval(updateOnlineCount, 30000);

    document.addEventListener('DOMContentLoaded', function () {
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');

        userDropdownBtn?.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdownMenu?.classList.toggle('hidden');
            notifDropdown?.classList.add('hidden');
        });

        notifBtn?.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown?.classList.toggle('hidden');
            userDropdownMenu?.classList.add('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#userDropdownBtn')) {
                userDropdownMenu?.classList.add('hidden');
            }
            if (!e.target.closest('#notifBtn')) {
                notifDropdown?.classList.add('hidden');
            }
        });
    });
</script>
</body>
</html>