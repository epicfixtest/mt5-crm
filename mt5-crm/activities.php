<?php
// --- PHP ทั้งหมดจะถูกคงไว้เหมือนเดิมทุกประการ ---
session_start();
require 'includes/connectdb.php';
require 'includes/require_login.php';
$pageTitle = "กิจกรรม";
$member_id = $_SESSION['member_id'];
ob_start();

$feedback = '';
$feedback_type = 'error'; // default to error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submission_image'])) {
    $activity_id = intval($_POST['activity_id']);

    $check_stmt = $pdo->prepare("SELECT 1 FROM activity_submissions WHERE activity_id = ? AND member_id = ?");
    $check_stmt->execute([$activity_id, $member_id]);

    if ($check_stmt->fetchColumn()) {
        $feedback = "คุณได้ส่งผลงานสำหรับกิจกรรมนี้ไปแล้ว";
        $feedback_type = 'error';
    } elseif ($activity_id > 0 && $_FILES['submission_image']['error'] === 0) {
        $upload_dir = 'uploads/activity_submissions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $filename = $member_id . '-' . $activity_id . '-' . time() . '.jpg';
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['submission_image']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO activity_submissions (activity_id, member_id, image_path) VALUES (?, ?, ?)");
            $stmt->execute([$activity_id, $member_id, $target_path]);
            $feedback = "ส่งผลงานสำเร็จ! รอทีมงานตรวจสอบและประกาศผล";
            $feedback_type = 'success';
        }
    }
}

$submitted_stmt = $pdo->prepare("SELECT DISTINCT activity_id FROM activity_submissions WHERE member_id = ?");
$submitted_stmt->execute([$member_id]);
$submitted_activity_ids = $submitted_stmt->fetchAll(PDO::FETCH_COLUMN);


$open_activities = $pdo->query("SELECT * FROM activities WHERE status = 'open' ORDER BY created_at DESC")->fetchAll();
$past_activities = $pdo->query("SELECT a.*, s.image_path as winner_image, m.username as winner_name FROM activities a JOIN activity_submissions s ON a.winner_submission_id = s.id JOIN members m ON s.member_id = m.id WHERE a.status = 'archived' AND a.announcement_date <= CURDATE() ORDER BY a.announcement_date DESC")->fetchAll();
?>

<div class="space-y-8">
    <div>
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">🔥 กิจกรรมและประกาศผล 🏆</h1>
            <p class="text-gray-400 mt-1">ร่วมสนุกกับกิจกรรมและตรวจสอบรายชื่อผู้ชนะได้ที่นี่</p>
        </div>
        
        <?php if(!empty($feedback)): ?>
            <div class="max-w-3xl mx-auto p-4 mb-6 rounded-lg text-sm flex items-center gap-3 <?= $feedback_type === 'success' ? 'bg-green-900/50 text-green-300 border border-green-700' : 'bg-red-900/50 text-red-300 border border-red-700' ?>">
                <i data-lucide="<?= $feedback_type === 'success' ? 'check-circle' : 'x-circle' ?>"></i>
                <span><?= htmlspecialchars($feedback) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        
        <div class="lg:col-span-3 space-y-8">
            <h2 class="text-2xl font-semibold text-white border-b-2 border-orange-500 pb-2">กิจกรรมที่เปิดให้เข้าร่วม</h2>
            <?php if(empty($open_activities)): ?>
                <p class="text-center text-gray-500 py-10">ยังไม่มีกิจกรรมที่เปิดให้เข้าร่วมในขณะนี้</p>
            <?php else: ?>
                <?php foreach($open_activities as $act): ?>
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg overflow-hidden">
                    <?php if(!empty($act['image_path'])): ?>
                        <img src="<?= htmlspecialchars($act['image_path']) ?>" class="w-full h-auto md:h-80 object-cover">
                    <?php endif; ?>
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($act['title']) ?></h3>
                        <p class="text-gray-400 my-3 text-base leading-relaxed"><?= nl2br(htmlspecialchars($act['description'])) ?></p>
                        
                        <div class="mt-6 border-t border-gray-700/50 pt-6">
                        <?php if (in_array($act['id'], $submitted_activity_ids)): ?>
                            <div class="bg-green-900/50 border border-green-700 text-green-300 font-medium p-4 rounded-lg text-center flex items-center justify-center gap-2">
                                <i data-lucide="check-check"></i>
                                <span>คุณส่งผลงานสำหรับกิจกรรมนี้เรียบร้อยแล้ว</span>
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                                <label class="block mb-2 font-semibold text-gray-300">อัปโหลดภาพผลงานของคุณ:</label>
                                <div class="flex flex-col sm:flex-row gap-4 items-center">
                                    <input type="file" name="submission_image" required accept="image/*" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 transition-colors cursor-pointer">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg flex-shrink-0 w-full sm:w-auto">ส่งผลงาน</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <h2 class="text-2xl font-semibold text-white border-b-2 border-yellow-400 pb-2">ประกาศผลผู้ชนะ</h2>
            <?php if(empty($past_activities)): ?>
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-6 text-center text-gray-500">
                    ยังไม่มีการประกาศผล
                </div>
            <?php else: ?>
                <?php foreach($past_activities as $act): ?>
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl shadow-lg p-5">
                    <h3 class="font-bold text-yellow-400"><?= htmlspecialchars($act['title']) ?></h3>
                    <p class="text-xs text-gray-500 mb-3">ประกาศผลเมื่อ: <?= date('d M Y', strtotime($act['announcement_date'])) ?></p>
                    <img src="<?= htmlspecialchars($act['winner_image']) ?>" class="w-full h-48 rounded-lg shadow-md object-cover mb-3">
                    <p class="text-center text-sm text-gray-300">ผู้ชนะ: <span class="font-bold text-lg text-white"><?= htmlspecialchars($act['winner_name']) ?></span></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include 'layout.php'; 
?>