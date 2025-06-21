<?php
// --- PHP เดิมจากไฟล์ของคุณ (ไม่แก้ไข) ---
session_start();
require_once 'includes/connectdb.php';
require_once 'includes/require_login.php';
require_once 'check_permission.php';

check_page_permission('trade');

$pageTitle = 'สร้างคำสั่งซื้อขาย';
ob_start();
?>

<div class="max-w-md mx-auto">
    <div class="bg-gray-800 border border-gray-700/50 rounded-2xl shadow-lg text-white">
        <div class="p-6 border-b border-gray-700/50">
            <h1 class="text-2xl font-bold">Market Order</h1>
            <p class="text-gray-400 mt-1">ส่งคำสั่งซื้อขายอย่างรวดเร็ว</p>
        </div>

        <form id="trade-form" class="p-6 space-y-6">
            <input type="hidden" id="trade-type" name="type">

            <div>
                <label for="symbol" class="block mb-2 text-sm font-medium text-gray-400">Symbol</label>
                <select id="symbol" name="symbol" class="w-full bg-gray-900 border-2 border-gray-700 text-white rounded-lg px-4 py-3 focus:ring-blue-500 focus:border-blue-500 text-center font-semibold text-lg uppercase appearance-none" style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239ca3af%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.5em 1.5em;">
                    <option>XAUUSD</option>
                    <option>EURUSD</option>
                    <option>GBPUSD</option>
                    <option>USDJPY</option>
                    <option>USDCAD</option>
                    <option>AUDUSD</option>
                    </select>
            </div>

            <div>
                <label for="volume" class="block mb-2 text-sm font-medium text-gray-400">Volume (Lot)</label>
                <div class="flex items-center">
                    <button type="button" id="vol-minus" class="p-3 bg-gray-700 hover:bg-gray-600 rounded-l-lg focus:outline-none transition-colors">-</button>
                    <input type="number" id="volume" name="volume" step="0.01" min="0.01" value="0.01" class="w-full bg-gray-900 border-y-2 border-gray-700 text-white px-3 py-3 focus:ring-0 focus:border-gray-700 text-center font-bold text-lg" required>
                    <button type="button" id="vol-plus" class="p-3 bg-gray-700 hover:bg-gray-600 rounded-r-lg focus:outline-none transition-colors">+</button>
                </div>
            </div>

            <div>
                <label class="inline-flex items-center cursor-pointer">
                    <input id="sl-tp-toggle" type="checkbox" value="" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-300">ตั้งค่า Stop Loss / Take Profit</span>
                </label>
            </div>
            
            <div id="sl-tp-inputs" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="stoploss" class="block mb-2 text-xs font-medium text-gray-400">Stop Loss</label>
                    <input type="number" id="stoploss" name="stoploss" step="any" placeholder="0.0" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="takeprofit" class="block mb-2 text-xs font-medium text-gray-400">Take Profit</label>
                    <input type="number" id="takeprofit" name="takeprofit" step="any" placeholder="0.0" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 pt-4">
                <button type="button" id="sell-btn" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-lg transition-all duration-200 transform hover:scale-105">
                    SELL
                </button>
                <button type="button" id="buy-btn" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg transition-all duration-200 transform hover:scale-105">
                    BUY
                </button>
            </div>
        </form>
    </div>
    <div id="trade-result" class="mt-4" style="display: none;">
        </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tradeResult = document.getElementById('trade-result');
    const buyBtn = document.getElementById('buy-btn');
    const sellBtn = document.getElementById('sell-btn');

    // --- ส่วนควบคุม UI ---
    const volMinus = document.getElementById('vol-minus');
    const volPlus = document.getElementById('vol-plus');
    const volumeInput = document.getElementById('volume');
    const slTpToggle = document.getElementById('sl-tp-toggle');
    const slTpInputs = document.getElementById('sl-tp-inputs');

    volMinus.addEventListener('click', () => {
        let currentVol = parseFloat(volumeInput.value);
        if (currentVol > 0.01) {
            volumeInput.value = (currentVol - 0.01).toFixed(2);
        }
    });
    volPlus.addEventListener('click', () => {
        let currentVol = parseFloat(volumeInput.value);
        volumeInput.value = (currentVol + 0.01).toFixed(2);
    });
    slTpToggle.addEventListener('change', () => {
        slTpInputs.classList.toggle('hidden');
    });

    function submitTrade(tradeType) {
        document.getElementById('trade-type').value = tradeType;

        tradeResult.style.display = 'block';
        tradeResult.className = 'p-4 rounded-lg bg-yellow-500/10 text-yellow-300';
        tradeResult.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="loader" class="animate-spin w-5 h-5"></i><span>กำลังส่งคำสั่ง ${tradeType.includes('BUY') ? 'BUY' : 'SELL'}...</span></div>`;
        lucide.createIcons();

        const formData = {
            symbol: document.getElementById('symbol').value, // JS อ่านค่าจาก select ได้เหมือนเดิม
            type: document.getElementById('trade-type').value,
            volume: parseFloat(document.getElementById('volume').value),
            stoploss: parseFloat(document.getElementById('stoploss').value) || 0,
            takeprofit: parseFloat(document.getElementById('takeprofit').value) || 0,
        };

        fetch('api_handler.php?action=trade', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tradeResult.className = 'p-4 rounded-lg bg-green-500/10 text-green-300';
                tradeResult.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="check-circle"></i><span>${data.message}</span></div>`;
            } else {
                tradeResult.className = 'p-4 rounded-lg bg-red-500/10 text-red-300';
                tradeResult.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="x-circle"></i><span>${data.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ'}</span></div>`;
            }
            lucide.createIcons();
        })
        .catch(error => {
            tradeResult.className = 'p-4 rounded-lg bg-red-500/10 text-red-300';
            tradeResult.innerHTML = `<div class="flex items-center gap-2"><i data-lucide="alert-triangle"></i><span>เกิดข้อผิดพลาดในการเชื่อมต่อ</span></div>`;
            console.error('Error:', error);
            lucide.createIcons();
        });
    }

    buyBtn.addEventListener('click', () => submitTrade('DEAL_TYPE_BUY'));
    sellBtn.addEventListener('click', () => submitTrade('DEAL_TYPE_SELL'));
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>