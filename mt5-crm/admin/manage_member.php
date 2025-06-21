<?php
session_start();
require '../includes/connectdb.php';
require '../includes/require_login.php';
require '../includes/require_role.php';
require_role(['Admin', 'SuperAdmin']);

$pageTitle = 'จัดการสมาชิก';
ob_start();

// ฟังก์ชันตรวจสิทธิ์เฉพาะหน้า
function member_has_permission($member_id, $page_key)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM member_permissions WHERE member_id = ? AND page_key = ?");
    $stmt->execute([$member_id, $page_key]);
    return $stmt->fetchColumn() ? true : false;
}

// === ACTION: ADD NEW MEMBER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    // ... โค้ดส่วนนี้เหมือนเดิม ไม่ต้องแก้ไข ...
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['name'];
    $role = $_POST['role'];
    $referrer_id = !empty($_POST['referrer_id']) ? intval($_POST['referrer_id']) : null;
    $vps_ip = $_POST['vps_ip'] ?? null;
    $vps_user = $_POST['vps_user'] ?? null;
    $vps_password = $_POST['vps_password'] ?? null;
    $stmt = $pdo->prepare(
        "INSERT INTO members (username, password, name, role, referrer_id, vps_ip, vps_user, vps_password) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$username, $password, $name, $role, $referrer_id, $vps_ip, $vps_user, $vps_password]);
    $new_member_id = $pdo->lastInsertId();
    if (!empty($_POST['permissions']['new'])) {
        $stmt = $pdo->prepare("INSERT INTO member_permissions (member_id, page_key) VALUES (?, ?)");
        foreach ($_POST['permissions']['new'] as $page_key) {
            $stmt->execute([$new_member_id, $page_key]);
        }
    }
    header("Location: manage_member.php");
    exit();
}

// === ACTION: DELETE MEMBER ===
if (isset($_GET['delete'])) {
    // ... โค้ดส่วนนี้เหมือนเดิม ไม่ต้องแก้ไข ...
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ? AND role != 'SuperAdmin'");
    $stmt->execute([$id]);
    $pdo->prepare("DELETE FROM member_permissions WHERE member_id = ?")->execute([$id]);
    header("Location: manage_member.php");
    exit();
}

// === ACTION: UPDATE MEMBER ===
// ✅ ส่วนที่แก้ไข: เพิ่มการอัปเดต MetaAPI credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_member'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    
    // ดึงข้อมูล VPS จากฟอร์ม
    $vps_ip = $_POST['vps_ip'] ?? null;
    $vps_user = $_POST['vps_user'] ?? null;
    $vps_password = $_POST['vps_password'] ?? null;

    // ดึงข้อมูล MetaAPI จากฟอร์ม
    $meta_api_account_id = $_POST['meta_api_account_id'] ?? null;
    $meta_api_token = $_POST['meta_api_token'] ?? null;

    $stmt = $pdo->prepare(
        "UPDATE members SET 
            name = ?, 
            role = ?, 
            vps_ip = ?, 
            vps_user = ?, 
            vps_password = ?,
            meta_api_account_id = ?,
            meta_api_token = ?
         WHERE id = ?"
    );
    $stmt->execute([$name, $role, $vps_ip, $vps_user, $vps_password, $meta_api_account_id, $meta_api_token, $id]);

    if (!empty($_POST['new_password'])) {
        $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
        $stmt->execute([$newPassword, $id]);
    }

    // อัปเดต permission
    $pdo->prepare("DELETE FROM member_permissions WHERE member_id = ?")->execute([$id]);
    if (!empty($_POST['permissions'][$id])) {
        $stmt = $pdo->prepare("INSERT INTO member_permissions (member_id, page_key) VALUES (?, ?)");
        foreach ($_POST['permissions'][$id] as $page_key) {
            $stmt->execute([$id, $page_key]);
        }
    }

    header("Location: manage_member.php");
    exit();
}

// ดึงข้อมูลสำหรับแสดงผล
$members = $pdo->query("SELECT * FROM members ORDER BY id DESC")->fetchAll();
$pages = ['index' => 'Dashboard', 'history' => 'ประวัติการเทรด', 'trade' => 'คำสั่งเทรด', 'news' => 'ข่าว', 'tradingview' => 'กราฟหุ้น'];
$referrers = $pdo->query("SELECT id, username FROM members ORDER BY username ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto py-10">
    <h1 class="text-3xl font-bold mb-6">👥 จัดการสมาชิก</h1>

    <form method="POST" class="bg-gray-800 p-6 rounded-xl mb-10">
        <h2 class="text-lg font-semibold text-white mb-4">➕ เพิ่มสมาชิกใหม่</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <input name="username" placeholder="ชื่อผู้ใช้ (สำหรับล็อกอิน)" required class="p-2 rounded bg-gray-700 border border-gray-600" />
            <input name="password" placeholder="รหัสผ่าน (สำหรับล็อกอิน)" type="password" required class="p-2 rounded bg-gray-700 border border-gray-600" />
            <input name="name" placeholder="ชื่อ-นามสกุล" required class="p-2 rounded bg-gray-700 border border-gray-600" />
            <select name="role" class="p-2 rounded bg-gray-700 border border-gray-600">
                <option value="User">User</option>
                <option value="Admin">Admin</option>
                <option value="SuperAdmin">SuperAdmin</option>
            </select>
            <select name="referrer_id" class="p-2 rounded bg-gray-700 border border-gray-600">
                <option value="">-- เลือกผู้แนะนำ (ไม่บังคับ) --</option>
                <?php foreach ($referrers as $referrer): ?>
                    <option value="<?= $referrer['id'] ?>"><?= htmlspecialchars($referrer['username']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="lg:col-span-3 border-t border-gray-700 my-2"></div>
            <input name="vps_ip" placeholder="VPS IP:Port" class="p-2 rounded bg-gray-700 border border-gray-600" />
            <input name="vps_user" placeholder="VPS Username" class="p-2 rounded bg-gray-700 border border-gray-600" />
            <input name="vps_password" placeholder="VPS Password" class="p-2 rounded bg-gray-700 border border-gray-600" />
        </div>
        <div class="mt-4">
            <label class="block mb-2 font-semibold">สิทธิ์เข้าถึงหน้า:</label>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                <?php foreach ($pages as $key => $label): ?>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="permissions[new][]" value="<?= $key ?>" class="mr-2 h-4 w-4 bg-gray-600 text-blue-500 border-gray-500 rounded focus:ring-blue-400">
                        <?= $label ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" name="add_member" class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md font-semibold">
            ➕ เพิ่มสมาชิก
        </button>
    </form>

    <div class="overflow-x-auto bg-gray-800 rounded-xl p-4">
        <table class="w-full text-sm text-left text-white">
            <thead class="bg-gray-700 text-gray-300">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">ข้อมูลสมาชิก</th>
                    <th class="p-3">ข้อมูล VPS & API</th>
                    <th class="p-3">สิทธิ์</th>
                    <th class="p-3 text-right">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $m): ?>
                    <tr class="border-t border-gray-700 hover:bg-gray-900">
                        <td class="p-3 align-top">#<?= $m['id'] ?></td>
                        <td class="p-3 align-top">
                            <div class="font-bold"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                            <div class="text-xs text-gray-400">ชื่อผู้ใช้: <?= htmlspecialchars($m['username'] ?? '') ?></div>
                            <div class="text-xs text-gray-400">ระดับ: <?= $m['role'] ?></div>
                        </td>
                        <td class="p-3 align-top">
                             <div class="text-xs text-gray-400">IP: <?= htmlspecialchars($m['vps_ip'] ?? '-') ?></div>
                             <div class="text-xs text-gray-400">User: <?= htmlspecialchars($m['vps_user'] ?? '-') ?></div>
                             <div class="text-xs text-gray-400">Pass: <?= htmlspecialchars($m['vps_password'] ? '********' : '-') ?></div>
                             <div class="text-xs text-blue-400 mt-1">API Account: <?= htmlspecialchars($m['meta_api_account_id'] ? 'SET' : '-') ?></div>
                             <div class="text-xs text-blue-400">API Token: <?= htmlspecialchars($m['meta_api_token'] ? 'SET' : '-') ?></div>
                        </td>
                        <td class="p-3 align-top">
                            <?php foreach ($pages as $key => $label): ?>
                                <?php if (member_has_permission($m['id'], $key)): ?>
                                    <span class="inline-block text-xs bg-green-800 text-green-200 px-2 py-0.5 rounded mr-1 mb-1"><?= $label ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td class="p-3 text-right align-top">
                            <?php if ($m['role'] !== 'SuperAdmin'): ?>
                                <form method="POST" class="space-y-2">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    
                                    <input type="text" name="name" value="<?= htmlspecialchars($m['name'] ?? '') ?>" class="bg-gray-700 text-white p-1 rounded w-full text-xs" placeholder="ชื่อ-สกุล">
                                    <input type="password" name="new_password" placeholder="รหัสใหม่ (ถ้ามี)" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                    <select name="role" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                        <option value="User" <?= $m['role'] === 'User' ? 'selected' : '' ?>>User</option>
                                        <option value="Admin" <?= $m['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    
                                    <div class="border-t border-gray-600 my-2"></div>
                                    
                                    <input type="text" name="vps_ip" value="<?= htmlspecialchars($m['vps_ip'] ?? '') ?>" placeholder="VPS IP:Port" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                    <input type="text" name="vps_user" value="<?= htmlspecialchars($m['vps_user'] ?? '') ?>" placeholder="VPS Username" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                    <input type="text" name="vps_password" value="<?= htmlspecialchars($m['vps_password'] ?? '') ?>" placeholder="VPS Password" class="bg-gray-700 text-white p-1 rounded w-full text-xs">

                                    <div class="border-t border-gray-600 my-2"></div>
                                    <input type="text" name="meta_api_account_id" value="<?= htmlspecialchars($m['meta_api_account_id'] ?? '') ?>" placeholder="MetaAPI Account ID" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                    <input type="text" name="meta_api_token" value="<?= htmlspecialchars($m['meta_api_token'] ?? '') ?>" placeholder="MetaAPI Token" class="bg-gray-700 text-white p-1 rounded w-full text-xs">
                                    <div class="border-t border-gray-600 my-2"></div>

                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-left">
                                        <?php foreach ($pages as $key => $label): ?>
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" name="permissions[<?= $m['id'] ?>][]" value="<?= $key ?>" class="mr-1 h-3 w-3" <?= member_has_permission($m['id'], $key) ? 'checked' : '' ?>>
                                                <span class="text-xs"><?= $label ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="flex gap-2 justify-end mt-2">
                                        <button type="submit" name="edit_member" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs">💾 บันทึก</button>
                                        <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('ลบสมาชิกคนนี้หรือไม่?')" class="bg-red-600 hover:bg-red-700 px-2 py-1 rounded text-white text-xs">🗑️ ลบ</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span class="text-gray-500 text-xs">ล็อกไว้</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
$currentPage = basename($_SERVER['PHP_SELF']);
include 'layout_admin.php';
?>