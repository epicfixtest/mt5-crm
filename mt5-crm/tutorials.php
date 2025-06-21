<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = "🎥 วิดีโอแนะนำการใช้งาน";
ob_start();
?>

<div class="space-y-8 max-w-7xl mx-auto">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="book-open-check" class="text-blue-400"></i>
            <?= $pageTitle ?>
        </h1>
        <p class="text-gray-400 mt-1">ค้นหาและเรียนรู้จากวิดีโอแนะนำการใช้งานต่างๆ</p>
    </div>

    <div class="relative max-w-2xl mx-auto">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
        <input type="text" 
               id="searchInput" 
               placeholder="ค้นหาด้วยชื่อวิดีโอ..."
               class="w-full bg-gray-800 border border-gray-700 rounded-lg py-3 pl-12 pr-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
    </div>

    <div id="results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const resultsDiv = document.getElementById("results");

    function fetchResults(query = "") {
        // ใช้ logic เดิมที่คุณให้มา
        fetch(`search_tutorials.php?search=${encodeURIComponent(query)}`)
            .then(res => res.text())
            .then(html => {
                resultsDiv.innerHTML = html;
            });
    }

    searchInput.addEventListener("input", function () {
        fetchResults(this.value);
    });

    // โหลดตอนเริ่มต้น
    fetchResults();
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>