<?php
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';

$pageTitle = 'Live Stream';
ob_start();

$stmt = $pdo->query("SELECT * FROM live_links ORDER BY is_live DESC, category ASC, sort_order ASC");
$links = $stmt->fetchAll(PDO::FETCH_ASSOC);

// แยกตามหมวด
$grouped = [];
foreach ($links as $link) {
    $grouped[$link['category']][] = $link;
}
?>

<div class="space-y-8 max-w-4xl mx-auto">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-white flex items-center justify-center gap-3">
            <i data-lucide="youtube" class="text-red-500"></i>
            Live Stream
        </h1>
        <p class="text-gray-400 mt-1">ติดตาม Live Stream และวิดีโอย้อนหลัง</p>
    </div>

    <div class="relative max-w-2xl mx-auto">
        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
        <input type="text" 
               id="searchInput" 
               placeholder="ค้นหาชื่อไลฟ์..."
               class="w-full bg-gray-800 border border-gray-700 rounded-lg py-3 pl-12 pr-4 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
    </div>

    <div id="liveContainer" class="space-y-10">
        <?php foreach ($grouped as $category => $items): ?>
            <div class="category-group">
                <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="folder-open" class="text-blue-400 w-7 h-7"></i>
                    <?= htmlspecialchars($category) ?>
                </h2>
                
                <div class="space-y-4">
                    <?php foreach ($items as $item): 
                        $isLive = $item['is_live'] == 1;
                        $startTime = $item['start_time'] ? date('d M Y, H:i', strtotime($item['start_time'])) : 'ไม่ระบุ';
                    ?>
                        <div class="live-item bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg flex flex-col md:flex-row md:items-center p-5 gap-5 transition-transform duration-300 hover:scale-[1.03] hover:border-blue-500/70">
                           
                           <div class="flex-grow">
                                <h3 class="text-xl font-semibold text-white live-title"><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="text-sm text-gray-400 flex items-center gap-1.5 mt-1">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                    เริ่ม: <?= $startTime ?>
                                </p>
                            </div>

                            <div class="flex items-center gap-4 flex-shrink-0 w-full md:w-auto">
                                <?php if ($isLive): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium bg-red-600/90 text-white">
                                        <i data-lucide="radio-tower" class="w-4 h-4 animate-pulse"></i>
                                        กำลังไลฟ์
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium bg-gray-600 text-gray-200">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        จบแล้ว
                                    </span>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="flex-grow md:flex-grow-0 flex items-center justify-center gap-2 text-center font-bold py-2.5 px-5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                    <span>เปิดลิงก์</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Script for searching remains the same and will work with this new layout
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');

    searchInput.addEventListener('input', function() {
        const keyword = this.value.toLowerCase().trim();

        document.querySelectorAll('.category-group').forEach(categoryGroup => {
            let categoryHasVisibleItems = false;
            
            categoryGroup.querySelectorAll('.live-item').forEach(liveItem => {
                const title = liveItem.querySelector('.live-title').textContent.toLowerCase();
                
                if (title.includes(keyword)) {
                    liveItem.style.display = 'flex';
                    categoryHasVisibleItems = true;
                } else {
                    liveItem.style.display = 'none';
                }
            });

            if (categoryHasVisibleItems) {
                categoryGroup.style.display = 'block';
            } else {
                categoryGroup.style.display = 'none';
            }
        });
    });
});
</script>


<?php
$content = ob_get_clean();
require 'layout.php';
?>