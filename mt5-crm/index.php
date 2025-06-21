<?php
session_start();
require 'includes/connectdb.php';
require 'check_permission.php';

check_page_permission('index');

$is_twofa_enabled = $_SESSION['is_twofa_enabled'] ?? 0;
$member_id = $_SESSION['member_id'];

try {
    $stmt = $pdo->prepare("SELECT meta_api_account_id, meta_api_token FROM members WHERE id = :member_id");
    $stmt->execute(['member_id' => $member_id]);
    $api_credentials = $stmt->fetch();
} catch (PDOException $e) {
    die("ไม่สามารถดึงข้อมูล API ได้: " . $e->getMessage());
}

$accountId = $api_credentials['meta_api_account_id'] ?? '';
$apiToken = $api_credentials['meta_api_token'] ?? '';

$pageTitle = '📊 Dashboard';
ob_start();
?>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<div class="space-y-6">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php
    $cards = [
        ['label' => 'Balance', 'id' => 'balance', 'icon' => 'scale', 'color' => 'blue'],
        ['label' => 'Equity', 'id' => 'equity', 'icon' => 'trending-up', 'color' => 'green'],
        ['label' => 'Profit', 'id' => 'profit', 'icon' => 'piggy-bank', 'color' => 'purple'],
        ['label' => 'Margin Level', 'id' => 'margin_level', 'icon' => 'gauge', 'color' => 'yellow']
    ];
    foreach ($cards as $card) {
        echo "<div class='bg-gray-800 border border-gray-700/50 rounded-xl p-5 shadow-lg'>
                <div class='flex items-center gap-4'>
                    <div class='p-3 bg-{$card['color']}-500/20 rounded-lg'>
                        <i data-lucide='{$card['icon']}' class='w-6 h-6 text-{$card['color']}-400'></i>
                    </div>
                    <div>
                        <h3 class='text-sm font-medium text-gray-400'>{$card['label']}</h3>
                        <p id='{$card['id']}' class='text-2xl font-semibold text-white mt-1'>-</p>
                    </div>
                </div>
            </div>";
    }
    ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-gray-800 border border-gray-700/50 rounded-xl p-5 shadow-lg">
      <h2 class="text-xl font-semibold mb-4 text-white">ภาพรวมกำไร-ขาดทุน</h2>
      <div class="h-80"><canvas id="monthlyChart"></canvas></div>
    </div>
    <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-5 shadow-lg flex flex-col">
      <h2 class="text-xl font-semibold mb-4 text-white">ออเดอร์ที่เปิดอยู่</h2>
      <div id="open-orders" class="space-y-3 overflow-y-auto flex-grow pr-2">
        <div class="text-center text-gray-500 py-10">กำลังโหลดข้อมูล...</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let monthlyChartInstance;

  function formatProfit(profit) {
    const value = parseFloat(profit);
    const colorClass = value >= 0 ? 'text-green-400' : 'text-red-400';
    return `<span class="${colorClass}">${value.toFixed(2)}</span>`;
  }

  function updateDashboard() {
    fetch('api_handler.php?action=get_dashboard_data&account_id=<?= urlencode($accountId) ?>')
      .then(response => response.json())
      .then(data => {
        document.getElementById('balance').textContent = data.account_info?.balance ? '$' + parseFloat(data.account_info.balance).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '-';
        document.getElementById('equity').textContent = data.account_info?.equity ? '$' + parseFloat(data.account_info.equity).toLocaleString('en-US', { minimumFractionDigits: 2 }) : '-';
        document.getElementById('profit').innerHTML = data.account_info?.profit ? formatProfit(data.account_info.profit) : '-';
        document.getElementById('margin_level').textContent = data.account_info?.margin_level ? parseFloat(data.account_info.margin_level).toFixed(2) + '%' : '-';


        const openOrdersContainer = document.getElementById('open-orders');
        openOrdersContainer.innerHTML = '';
        if (data.open_orders && data.open_orders.length > 0) {
          data.open_orders.forEach(order => {
            openOrdersContainer.innerHTML += `
              <div class="bg-gray-700/50 p-3 rounded-lg animate-fade-in">
                <div class="flex justify-between items-center">
                  <span class="font-bold text-white">${order.symbol}</span>
                  <span class="text-sm font-medium ${order.type.toLowerCase().includes('buy') ? 'text-sky-400' : 'text-rose-400'}">${order.type}</span>
                </div>
                <div class="flex justify-between items-baseline mt-1">
                  <span class="text-xs text-gray-400">Vol: ${order.volume} @ ${order.openPrice}</span>
                  <span class="text-lg font-semibold">${formatProfit(order.profit)}</span>
                </div>
              </div>`;
          });
        } else {
          openOrdersContainer.innerHTML = '<div class="text-center text-gray-500 py-10">ไม่มีออเดอร์ที่เปิดอยู่</div>';
        }

        if (data.chart_data) {
          const ctx = document.getElementById('monthlyChart').getContext('2d');
          if (monthlyChartInstance) monthlyChartInstance.destroy();
          monthlyChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
              labels: data.chart_data.labels,
              datasets: [
                {
                  label: 'Buy Profit',
                  data: data.chart_data.buy_profits,
                  borderColor: 'rgba(59, 130, 246, 0.8)',
                  backgroundColor: 'rgba(59, 130, 246, 0.1)',
                  fill: true,
                  tension: 0.4
                },
                {
                  label: 'Sell Profit',
                  data: data.chart_data.sell_profits,
                  borderColor: 'rgba(244, 63, 94, 0.8)',
                  backgroundColor: 'rgba(244, 63, 94, 0.1)',
                  fill: true,
                  tension: 0.4
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } }
              },
              plugins: {
                legend: { labels: { color: '#d1d5db' } }
              }
            }
          });
        }
      })
      .catch(error => {
        console.error('Failed to fetch dashboard data:', error);
        document.getElementById('open-orders').innerHTML = '<div class="text-center text-red-400 py-10">ไม่สามารถโหลดข้อมูลได้</div>';
      });
  }

  updateDashboard();
  setInterval(updateDashboard, 30000);
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
