<?php
session_start();
require 'includes/connectdb.php';

// !! อย่าลืม! แทนที่ค่าเหล่านี้ด้วย Client ID, Client Secret และ Redirect URI ของคุณ
$google_client_id = '179663872858-04fajg6s8bl7kmb2948nhij5jq9bf1f9.apps.googleusercontent.com';
$google_client_secret = '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';
$google_redirect_uri = 'https://epictest.info/mt5-crm/google_callback.php'; // เปลี่ยนเป็น URL จริงของคุณ

if (!isset($_GET['code'])) {
    die("Error: ไม่มี Authorization Code จาก Google");
}

try {
    // 1. แลก Authorization Code เป็น Access Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_params = [
        'code' => $_GET['code'],
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($token_response, true);
    if (!isset($token_data['access_token'])) {
        die("Error: ไม่สามารถแลก Access Token ได้. " . ($token_data['error_description'] ?? ''));
    }
    $access_token = $token_data['access_token'];

    // 2. ใช้ Access Token เพื่อดึงข้อมูลผู้ใช้
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $userinfo_response = curl_exec($ch);
    curl_close($ch);
    
    $user_data = json_decode($userinfo_response, true);
    $google_id = $user_data['id'];
    $email = $user_data['email'];
    $name = $user_data['name'];

    // 3. ตรวจสอบผู้ใช้ในฐานข้อมูล
    $stmt = $pdo->prepare("SELECT * FROM members WHERE google_id = ? OR email = ?");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // ถ้าเจอผู้ใช้: ทำการ Login
        if (empty($user['google_id'])) {
            $update_stmt = $pdo->prepare("UPDATE members SET google_id = ? WHERE id = ?");
            $update_stmt->execute([$google_id, $user['id']]);
        }
    } else {
        // ถ้าไม่เจอผู้ใช้: สร้างบัญชีใหม่
        $username = explode('@', $email)[0] . rand(100, 999);
        
        // เพิ่ม referrer_id ในคำสั่ง INSERT
        $referrer_id = $_SESSION['referrer_id'] ?? null;

        $stmt = $pdo->prepare(
            "INSERT INTO members (name, email, google_id, username, role, referrer_id) VALUES (?, ?, ?, ?, 'User', ?)"
        );
        $stmt->execute([$name, $email, $google_id, $username, $referrer_id]);
        $user_id = $pdo->lastInsertId();
        
        // กำหนดสิทธิ์เริ่มต้น
        $default_permissions = ['index', 'history', 'trade', 'news', 'tradingview'];
        $perm_stmt = $pdo->prepare("INSERT INTO member_permissions (member_id, page_key) VALUES (?, ?)");
        foreach ($default_permissions as $page_key) {
            $perm_stmt->execute([$user_id, $page_key]);
        }
        
        // ดึงข้อมูลผู้ใช้ที่เพิ่งสร้างใหม่
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
    
    // ล้าง session ของผู้แนะนำหลังจากใช้งานแล้ว
    if (isset($_SESSION['referrer_id'])) {
        unset($_SESSION['referrer_id']);
    }

    // 4. สร้าง Session และ Redirect
    if ($user) {
        $_SESSION['member_id'] = $user['id'];
        $_SESSION['member_role'] = $user['role'];
        $_SESSION['member_name'] = $user['name'];
        $_SESSION['is_twofa_enabled'] = $user['is_twofa_enabled'] ?? 0;

        // บันทึก log การ login
        $stmt = $pdo->prepare("INSERT INTO login_logs (member_id, ip_address) VALUES (?, ?)");
        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

        header("Location: index.php");
        exit();
    } else {
        die("เกิดข้อผิดพลาดร้ายแรง ไม่สามารถเข้าสู่ระบบหรือสร้างบัญชีได้");
    }

} catch (Exception $e) {
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>