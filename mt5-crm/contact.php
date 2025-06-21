<?php
require 'includes/connectdb.php';
$pageTitle = 'ช่องทางการติดต่อ';
ob_start();

$stmt = $pdo->query("SELECT content FROM contact_info WHERE id = 1");
$contact = $stmt->fetchColumn();
?>

<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-6 rounded shadow leading-relaxed">
    <h1 class="text-2xl font-bold mb-4">📬 ช่องทางการติดต่อ</h1>
    <div class="text-gray-800 dark:text-gray-100 whitespace-pre-line">
        <?= nl2br(htmlspecialchars($contact)) ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
