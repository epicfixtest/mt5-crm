<?php
header('Content-Type: application/json');

$secretToken = "epictest1234";

// 🔐 ตรวจสอบ Token (เฉพาะ POST)
$headers = getallheaders();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($headers['X-Auth-Token']) || $headers['X-Auth-Token'] !== $secretToken)) {
    http_response_code(403);
    echo json_encode(["error" => "Token required or invalid"]);
    exit;
}

// ✅ 1. รับข้อมูล POST จาก MT5
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    if (!$data || !isset($data['type']) || !isset($data['account_id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON or missing account_id"]);
        exit;
    }

    $account_id = preg_replace('/[^a-zA-Z0-9_]/', '', $data['account_id']);
    $type = $data['type'];

    $logfile = ($type === 'trade') ? "trades_{$account_id}.log" : "status_{$account_id}.log";
    file_put_contents($logfile, "[" . date("Y-m-d H:i:s") . "] " . $json . "\n", FILE_APPEND);

    echo json_encode(["status" => "ok", "received" => $type]);
    exit;
}

// ✅ 2. Dashboard Data
if ($_GET['action'] === 'get_dashboard_data') {
    $account_id = $_GET['account_id'] ?? '';
    $safe_id = preg_replace('/[^a-zA-Z0-9_]/', '', $account_id);
    $statusFile = "status_{$safe_id}.log";
    $tradeFile = "trades_{$safe_id}.log";

    $lastStatus = null;
    if (file_exists($statusFile)) {
        $lines = @file($statusFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines && count($lines) > 0) {
            $lastLine = trim(end($lines));
            $jsonStart = strpos($lastLine, '{');
            if ($jsonStart !== false) {
                $lastStatus = json_decode(substr($lastLine, $jsonStart), true);
            }
        }
    }

    // ✅ กราฟจาก trades.log
    $chartData = ['labels' => [], 'buy_profits' => [], 'sell_profits' => []];
    if (file_exists($tradeFile)) {
        $lines = file($tradeFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) continue;
            $trade = json_decode(substr($line, $jsonStart), true);
            if (!$trade || !isset($trade['deal_type'])) continue;

            $date = substr($line, 1, 19);
            $chartData['labels'][] = $date;
            $chartData['buy_profits'][] = $trade['deal_type'] == 0 ? $trade['profit'] : 0;
            $chartData['sell_profits'][] = $trade['deal_type'] == 1 ? $trade['profit'] : 0;
        }
    }

    // ✅ Orders จาก status.log → orders[]
    $openOrders = [];
    if (isset($lastStatus['orders']) && is_array($lastStatus['orders'])) {
        foreach ($lastStatus['orders'] as $o) {
            $openOrders[] = [
                "symbol" => $o['symbol'] ?? '-',
                "type" => ($o['type'] == 1 ? 'Sell' : 'Buy'),
                "volume" => $o['volume'] ?? '-',
                "openPrice" => $o['price'] ?? '-',
                "profit" => $o['profit'] ?? 0,
            ];
        }
    }

    echo json_encode([
        "account_info" => [
            "balance" => $lastStatus['balance'] ?? 0,
            "equity" => $lastStatus['equity'] ?? 0,
            "profit" => $lastStatus['profit'] ?? 0,
            "credit" => $lastStatus['credit'] ?? 0,
            "margin_level" => $lastStatus['margin_level'] ?? 0,
        ],
        "open_orders" => $openOrders,
        "chart_data" => $chartData
    ]);
    exit;
}

// ✅ 3. History
if ($_GET['action'] === 'get_history') {
    $account_id = $_GET['account_id'] ?? '';
    if (!$account_id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing account_id"]);
        exit;
    }

    $safe_id = preg_replace('/[^a-zA-Z0-9_]/', '', $account_id);
    $logFile = "trades_{$safe_id}.log";

    if (!file_exists($logFile)) {
        echo json_encode([]);
        exit;
    }

    $from = isset($_GET['from']) ? strtotime($_GET['from']) : 0;
    $to = isset($_GET['to']) ? strtotime($_GET['to']) : time();

    $results = [];
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $ts = strtotime(substr($line, 1, 19));
        if ($ts < $from || $ts > $to) continue;

        $jsonStart = strpos($line, '{');
        if ($jsonStart !== false) {
            $trade = json_decode(substr($line, $jsonStart), true);
            if ($trade) {
                $results[] = [
                    "ticket" => $trade['ticket'] ?? '-',
                    "symbol" => $trade['symbol'] ?? '-',
                    "type" => "DEAL_TYPE_" . ($trade['deal_type'] == 0 ? 'BUY' : 'SELL'),
                    "volume" => $trade['volume'] ?? 1,
                    "openTime" => date('c', $ts),
                    "closeTime" => date('c', $ts),
                    "profit" => $trade['profit']
                ];
            }
        }
    }

    echo json_encode($results);
    exit;
}

// ❌ ไม่มี action ที่รู้จัก
http_response_code(400);
echo json_encode(["error" => "No valid action"]);
exit;
