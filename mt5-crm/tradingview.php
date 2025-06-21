<?php
session_start();
require 'includes/connectdb.php';
require 'check_permission.php';

check_page_permission('tradingview'); //

$pageTitle = '📈 กราฟเทคนิค';
ob_start();
?>

<div class="flex flex-col h-full">

    <div class="mb-6 flex-shrink-0">
        <h1 class="text-2xl font-bold text-white flex items-center gap-3">
            <i data-lucide="candlestick-chart" class="w-7 h-7 text-green-400"></i>
            กราฟเทคนิค
        </h1>
        <p class="text-gray-400 mt-1">เครื่องมือวิเคราะห์กราฟจาก TradingView</p>
    </div>

    <div class="flex-grow w-full">
        <div class="tradingview-widget-container h-full w-full">
          <div id="tradingview_chart_container" class="w-full"></div>
          <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
          {
            "autosize": true,
            "symbol": "XAUUSD",
            "interval": "D",
            "timezone": "Asia/Bangkok",
            "theme": "dark",
            "style": "1",
            "locale": "th_TH",
            "enable_publishing": false,
            "with_date_ranges": true,
            "allow_symbol_change": true,
            "container_id": "tradingview_chart_container"
          }
          </script>
        </div>
        </div>

</div>

<?php
$content = ob_get_clean();
// *** สำคัญมาก: เราต้องปรับแก้ layout.php เล็กน้อยเพื่อให้ flexbox ทำงานถูกต้อง ***
// ให้คุณไปที่ไฟล์ layout.php และหาแท็ก <main> แล้วเพิ่ม class `flex flex-col` เข้าไปดังนี้
// <main class="flex-1 p-6 overflow-y-auto bg-gray-900 flex flex-col">
include 'layout.php';
?>