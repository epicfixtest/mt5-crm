<?php
// ดึง % คอมมิชชั่นปัจจุบัน
function getCommissionPercent() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'commission_percent' LIMIT 1");
    $stmt->execute();
    $percent = $stmt->fetchColumn();
    return $percent !== false ? (float) $percent : 0;
}

// บันทึกค่าคอมมิชชั่น + เพิ่มแต้มสะสม
function giveReferralCommission($referred_member_id, $service_price, $service_name = '') {
    global $pdo;

    // หา referrer_id ของคนที่ถูกแนะนำ
    $stmt = $pdo->prepare("SELECT referrer_id FROM members WHERE id = ?");
    $stmt->execute([$referred_member_id]);
    $referrer_id = $stmt->fetchColumn();

    if ($referrer_id) {
        // ดึง % คอมมิชชั่น
        $commission_percent = getCommissionPercent();
        if ($commission_percent <= 0) {
            return; // ไม่ทำอะไรถ้าเปอร์เซ็นต์เป็น 0
        }

        // คำนวณจำนวนแต้ม (ค่าคอม)
        $commission_amount = ($commission_percent / 100) * $service_price;

        // บันทึกลงตาราง referral_earnings
        $stmt = $pdo->prepare("INSERT INTO referral_earnings (referrer_id, referred_id, amount, service_name, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$referrer_id, $referred_member_id, $commission_amount, $service_name]);

        // อัปเดตเพิ่ม point ให้สมาชิกที่เป็น referrer
        $stmt = $pdo->prepare("UPDATE members SET points = points + ? WHERE id = ?");
        $stmt->execute([$commission_amount, $referrer_id]);
    }
}


function redeemItem($member_id, $item_id) {
    global $pdo;

    // ดึงข้อมูลไอเท็มที่ต้องการแลก
    $stmt = $pdo->prepare("SELECT points_required FROM redeem_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        return ['status' => 'error', 'message' => 'ไม่พบรายการที่ต้องการแลก'];
    }

    $points_required = $item['points_required'];

    // เช็กแต้มสมาชิก
    $stmt = $pdo->prepare("SELECT points FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $current_points = $stmt->fetchColumn();

    if ($current_points < $points_required) {
        return ['status' => 'error', 'message' => 'แต้มไม่เพียงพอ'];
    }

    // หักแต้ม
    $stmt = $pdo->prepare("UPDATE members SET points = points - ? WHERE id = ?");
    $stmt->execute([$points_required, $member_id]);

    // บันทึกประวัติการแลก
    $stmt = $pdo->prepare("INSERT INTO redeem_history (member_id, redeem_item_id, redeemed_at) VALUES (?, ?, NOW())");
    $stmt->execute([$member_id, $item_id]);

    return ['status' => 'success', 'message' => 'แลกสำเร็จ'];
}

function get_member_permissions($member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT page_key FROM member_permissions WHERE member_id = ?");
        $stmt->execute([$member_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // ในกรณีที่เกิดข้อผิดพลาด, อาจจะคืนค่าเป็น array ว่าง หรือจัดการ error ตามความเหมาะสม
        error_log("Could not fetch member permissions: " . $e->getMessage());
        return [];
    }
}

?>
