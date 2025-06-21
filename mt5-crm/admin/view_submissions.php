<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($activity_id === 0) die("Invalid Activity ID");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_score'])) {
        $submission_id = $_POST['submission_id'];
        $score = intval($_POST['score']);
        $pdo->prepare("UPDATE activity_submissions SET score = ? WHERE id = ?")->execute([$score, $submission_id]);
    }
    if (isset($_POST['set_winner'])) {
        $submission_id = $_POST['submission_id'];
        $pdo->prepare("UPDATE activities SET winner_submission_id = ?, status = 'archived' WHERE id = ?")->execute([$submission_id, $activity_id]);
    }
    header("Location: view_submissions.php?id=$activity_id");
    exit();
}

$activity = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
$activity->execute([$activity_id]);
$activity = $activity->fetch();

$submissions = $pdo->prepare("SELECT s.*, m.username FROM activity_submissions s JOIN members m ON s.member_id = m.id WHERE s.activity_id = ? ORDER BY s.score DESC, s.submitted_at ASC");
$submissions->execute([$activity_id]);
$submissions = $submissions->fetchAll();

$pageTitle = "ผลงานกิจกรรม: " . htmlspecialchars($activity['title']);
ob_start();
?>
<div class="max-w-6xl mx-auto space-y-8">
    <h1 class="text-2xl font-bold"><?= $pageTitle ?></h1>
    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left bg-gray-700"><th class="p-2">ผู้ส่ง</th><th class="p-2">รูปภาพ</th><th class="p-2">คะแนน</th><th class="p-2">จัดการ</th></tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $sub): ?>
                <tr class="border-b border-gray-700">
                    <td class="p-2 align-middle"><?= htmlspecialchars($sub['username']) ?></td>
                    <td class="p-2 align-middle"><a href="../<?= $sub['image_path'] ?>" target="_blank"><img src="../<?= $sub['image_path'] ?>" class="w-32 h-auto"></a></td>
                    <td class="p-2 align-middle">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                            <input type="number" name="score" value="<?= $sub['score'] ?>" class="p-1 rounded bg-gray-900 w-20">
                            <button type="submit" name="update_score" class="bg-green-600 text-white px-2 py-1 rounded text-xs">ให้คะแนน</button>
                        </form>
                    </td>
                    <td class="p-2 align-middle">
                        <?php if ($activity['winner_submission_id'] == $sub['id']): ?>
                            <span class="text-yellow-400 font-bold">🏆 ผู้ชนะ</span>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                                <button type="submit" name="set_winner" class="bg-yellow-500 text-white px-2 py-1 rounded text-xs">เลือกเป็นผู้ชนะ</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout_admin.php'; ?>