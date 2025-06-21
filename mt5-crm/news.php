<?php
session_start();
require 'includes/connectdb.php';
require 'check_permission.php';

check_page_permission('news');

$pageTitle = '🗞️ ปฏิทินข่าวเศรษฐกิจ';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
        <i data-lucide="calendar-days" class="w-7 h-7 text-blue-400"></i>
        ปฏิทินข่าวเศรษฐกิจ
    </h1>
    <p class="text-gray-400 mt-1">ข้อมูลข่าวสารสำคัญจาก TradingView</p>
</div>

<div class="h-[75vh] w-full">
    <div class="tradingview-widget-container h-full w-full">
      <div class="tradingview-widget-container__widget h-full w-full"></div>
      <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-events.js" async>
      {
        "colorTheme": "dark",
        "isTransparent": true,
        "width": "100%",
        "height": "100%",
        "locale": "th_TH",
        "importanceFilter": "-1,0,1",
        "currencyFilter": "USD,EUR,JPY,GBP,CAD,AUD,NZD,CHF"
      }
      </script>
    </div>
    </div>

<?php
$content = ob_get_clean();
include 'layout.php';
?>