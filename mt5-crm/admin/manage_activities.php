<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);
$pageTitle = "จัดการกิจกรรม";
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $announcement_date = $_POST['announcement_date'];
    $image_path = null;

    // ★★★ เพิ่ม Logic การอัปโหลดรูปภาพ ★★★
    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] === 0) {
        $upload_dir = '../uploads/activities/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $filename = uniqid() . '-' . basename($_FILES['activity_image']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['activity_image']['tmp_name'], $target_path)) {
            $image_path = 'uploads/activities/' . $filename;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO activities (title, description, announcement_date, image_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $announcement_date, $image_path]);
    
    header("Location: manage_activities.php");
    exit();
}
$activities = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC")->fetchAll();
?>
<div class="max-w-4xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">⭐ สร้างกิจกรรมใหม่</h2>
        <form method="POST" class="space-y-4" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="หัวข้อกิจกรรม" required class="w-full p-2 rounded bg-gray-700">
            <textarea name="description" placeholder="รายละเอียดกิจกรรม" rows="3" class="w-full p-2 rounded bg-gray-700"></textarea>
            
            <div>
                <label class="block mb-1">รูปภาพประกอบกิจกรรม</label>
                <input type="file" name="activity_image" accept="image/*" class="w-full text-sm">
            </div>

            <div>
                <label class="block">วันประกาศผล</label>
                <input type="date" name="announcement_date" required class="p-2 rounded bg-gray-700">
            </div>
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded">สร้างกิจกรรม</button>
        </form>
    </div>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <h2 class="text-lg font-semibold mb-4">รายการกิจกรรมทั้งหมด</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left bg-gray-700">
                        <th class="p-2">รูปภาพ</th>
                        <th class="p-2">หัวข้อ</th>
                        <th class="p-2">สถานะ</th>
                        <th class="p-2">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $act): ?>
                    <tr class="border-b border-gray-700">
                        <td class="p-2">
                            <?php if(!empty($act['image_path'])): ?>
                                <img src="../<?= htmlspecialchars($act['image_path']) ?>" class="w-20 h-20 object-cover rounded">
                            <?php endif; ?>
                        </td>
                        <td class="p-2 align-top"><?= htmlspecialchars($act['title']) ?></td>
                        <td class="p-2 align-top"><?= $act['status'] ?></td>
                        <td class="p-2 align-top">
                            <a href="view_submissions.php?id=<?= $act['id'] ?>" class="text-blue-400 hover:underline">ดูผลงานที่ส่ง</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout_admin.php'; ?>