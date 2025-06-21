<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = "แดชบอร์ดระบบ";
ob_start();

// ดึงข้อมูลจริง
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalRedeemItems = $pdo->query("SELECT COUNT(*) FROM redeem_items")->fetchColumn();
$totalRedeemThisMonth = $pdo->query("
    SELECT COUNT(*) 
    FROM redeem_history 
    WHERE MONTH(redeemed_at) = MONTH(NOW()) 
      AND YEAR(redeemed_at) = YEAR(NOW())
")->fetchColumn();

// ดึงข้อมูล 7 วันย้อนหลัง
$dates = [];
$redeemCounts = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM redeem_history WHERE DATE(redeemed_at) = ?");
    $stmt->execute([$date]);
    $count = $stmt->fetchColumn();

    $dates[] = date('d M', strtotime($date)); 
    $redeemCounts[] = $count;
}
?>

<main class="p-6 max-w-7xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold mb-6"><?= $pageTitle ?></h1>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow transition-all duration-500 flex flex-col">
            <h2 class="text-gray-500 dark:text-gray-400 mb-2 text-sm">สมาชิกทั้งหมด</h2>
            <p id="totalMembers" class="text-3xl font-bold">0</p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow transition-all duration-500 flex flex-col">
            <h2 class="text-gray-500 dark:text-gray-400 mb-2 text-sm">ไฟล์บริการทั้งหมด</h2>
            <p id="totalRedeemItems" class="text-3xl font-bold">0</p>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow transition-all duration-500 flex flex-col">
            <h2 class="text-gray-500 dark:text-gray-400 mb-2 text-sm">การแลกไฟล์เดือนนี้</h2>
            <p id="totalRedeemThisMonth" class="text-3xl font-bold">0</p>
        </div>
    </div>

    <!-- กราฟการแลกไฟล์ 7 วันย้อนหลัง -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">ยอดการแลกไฟล์ 7 วันย้อนหลัง</h2>
        <canvas id="redeemChart" class="w-full h-64"></canvas>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Counter Animation -->
<script>
// ฟังก์ชันนับเลขแบบลื่น ๆ
function animateCounter(id, target) {
    const el = document.getElementById(id);
    let current = 0;
    const increment = target / 60; // ปรับสปีดที่นี่ (60 เฟรมประมาณ 1 วิ)

    function updateCounter() {
        current += increment;
        if (current >= target) {
            el.innerText = target.toLocaleString();
        } else {
            el.innerText = Math.floor(current).toLocaleString();
            requestAnimationFrame(updateCounter);
        }
    }

    updateCounter();
}

// เรียก animate ตัวเลข
document.addEventListener('DOMContentLoaded', function() {
    animateCounter('totalMembers', <?= $totalMembers ?>);
    animateCounter('totalRedeemItems', <?= $totalRedeemItems ?>);
    animateCounter('totalRedeemThisMonth', <?= $totalRedeemThisMonth ?>);
});

// Chart
const ctx = document.getElementById('redeemChart').getContext('2d');
const redeemChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [{
            label: 'จำนวนการแลกไฟล์',
            data: <?= json_encode($redeemCounts) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
            pointBorderColor: 'white',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: 'white'
                }
            }
        },
        scales: {
            x: {
                ticks: { color: 'white' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            },
            y: {
                ticks: { color: 'white' },
                grid: { color: 'rgba(255,255,255,0.1)' }
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>
