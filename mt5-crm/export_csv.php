<?php
$token = 'a938dc40-d646-4ff2-9ebb-80be0ac4cdeb';
$from = date('Y-m-d\T00:00:00', strtotime('-365 days'));
$to = date('Y-m-d\T23:59:59');
$url = "https://mt5.mtapi.io/OrderHistory?id=$token&from=$from&to=$to";
$history = json_decode(file_get_contents($url), true);

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=trade_history.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Order', 'Symbol', 'Type', 'Lots', 'Open Price', 'Close Price', 'Profit', 'Open Time', 'Close Time']);

foreach ($history as $row) {
    fputcsv($output, [
        $row['order'] ?? '',
        $row['symbol'] ?? '',
        $row['type'] ?? '',
        $row['volume'] ?? '',
        $row['open_price'] ?? '',
        $row['close_price'] ?? '',
        $row['profit'] ?? '',
        $row['open_time'] ?? '',
        $row['close_time'] ?? ''
    ]);
}
fclose($output);
exit;