<?php
$secretToken = "epictest1234";
file_put_contents("log_header_debug.txt", print_r(getallheaders(), true));

// 1. ตรวจสอบ Header
$headers = getallheaders();
if (!isset($headers['X-Auth-Token']) || $headers['X-Auth-Token'] !== $secretToken) {
    http_response_code(403);
    echo "Forbidden: Invalid token";
    exit;
}

// 2. รับ JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data || !isset($data['type'])) {
    http_response_code(400);
    echo "Invalid JSON";
    exit;
}

// 3. แยกข้อมูล
$type = $data['type'];
$logfile = ($type === 'trade') ? 'trades.log' : 'status.log';
$entry = "[" . date('Y-m-d H:i:s') . "] " . $json . "\n";

file_put_contents($logfile, $entry, FILE_APPEND);

// 4. ส่งกลับ
echo json_encode(['status' => 'ok', 'received' => $type]);
