<?php
// --- PHP เดิมจากไฟล์ของคุณ (ไม่แก้ไข) ---
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';
require 'check_permission.php';

check_page_permission('history');

$pageTitle = 'ประวัติการเทรด';
ob_start();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-bold text-white">ประวัติการเทรด</h1>
        <div class="flex items-center gap-4 w-full md:w-auto">
            <form id="historyForm" class="flex items-center gap-2">
                <input type="text" id="startDate" placeholder="วันที่เริ่มต้น" class="bg-gray-700 border-gray-600 rounded-lg px-3 py-2 text-sm w-36 focus:ring-blue-500 focus:border-blue-500">
                <span class="text-gray-500">ถึง</span>
                <input type="text" id="endDate" placeholder="วันที่สิ้นสุด" class="bg-gray-700 border-gray-600 rounded-lg px-3 py-2 text-sm w-36 focus:ring-blue-500 focus:border-blue-500">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>ค้นหา</span>
                </button>
            </form>
            <button id="exportCsvBtn" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center gap-2">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                <span>Export</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-400 uppercase bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3">Ticket</th>
                    <th class="px-6 py-3">Symbol</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Volume</th>
                    <th class="px-6 py-3">Open Time</th>
                    <th class="px-6 py-3">Close Time</th>
                    <th class="px-6 py-3">Profit</th>
                </tr>
            </thead>
            <tbody id="history-table-body" class="divide-y divide-gray-700">
                <tr>
                    <td colspan="7" class="text-center p-8 text-gray-500">
                        <div class="flex justify-center items-center gap-2">
                           <i data-lucide="loader" class="animate-spin w-5 h-5"></i>
                           <span>กำลังโหลดข้อมูล...</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#startDate", { dateFormat: "Y-m-d" });
    flatpickr("#endDate", { dateFormat: "Y-m-d" });

    const historyForm = document.getElementById('historyForm');
    const exportCsvBtn = document.getElementById('exportCsvBtn');

    function fetchHistory(from = '', to = '') {
        const tableBody = document.getElementById('history-table-body');
        const fromDate = from ? `&from=${from}` : '';
        const toDate = to ? `&to=${to}` : '';
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center p-8 text-gray-500"><div class="flex justify-center items-center gap-2"><i data-lucide="loader" class="animate-spin w-5 h-5"></i><span>กำลังโหลดข้อมูล...</span></div></td></tr>`;
        lucide.createIcons();
        
        const accountId = <?= json_encode($_SESSION['meta_api_account_id'] ?? '') ?>;
        fetch(`api_handler.php?action=get_history&account_id=${accountId}${fromDate}${toDate}`)
            .then(response => response.json())
            .then(data => {
                tableBody.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(deal => {
                        const type = deal.type.replace('DEAL_TYPE_', '');
                        const profitColor = deal.profit >= 0 ? 'text-green-400' : 'text-red-400';
                        const typeColor = type === 'BUY' ? 'text-sky-400' : 'text-rose-400';

                        const row = `
                            <tr class="hover:bg-gray-700/50">
                                <td class="px-6 py-4">${deal.ticket}</td>
                                <td class="px-6 py-4 font-medium text-white">${deal.symbol}</td>
                                <td class="px-6 py-4 font-semibold ${typeColor}">${type}</td>
                                <td class="px-6 py-4">${deal.volume}</td>
                                <td class="px-6 py-4 text-gray-400">${new Date(deal.openTime).toLocaleString()}</td>
                                <td class="px-6 py-4 text-gray-400">${new Date(deal.closeTime).toLocaleString()}</td>
                                <td class="px-6 py-4 font-bold ${profitColor}">${parseFloat(deal.profit).toFixed(2)}</td>
                            </tr>
                        `;
                        tableBody.innerHTML += row;
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center p-8 text-gray-500">ไม่พบข้อมูลประวัติการเทรดในช่วงเวลาที่กำหนด</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error fetching history:', error);
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center p-8 text-red-400">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            });
    }

    historyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        fetchHistory(startDate, endDate);
    });

    exportCsvBtn.addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        let url = 'export_csv.php';
        if (startDate && endDate) {
            url += `?from=${startDate}&to=${endDate}`;
        }
        window.location.href = url;
    });

    fetchHistory();
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
