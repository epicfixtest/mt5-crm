<?php
// --- บังคับให้แสดง Error ทุกอย่างเพื่อการดีบั๊ก ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---------------------------------------------

header('Content-Type: application/json; charset=utf-8');

function fetchForexFactoryNews() {
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8\r\n" .
                        "Accept-Language: en-US,en;q=0.9\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $url = 'https://www.forexfactory.com/calendar';
    
    $html = @file_get_contents($url, false, $context);

    if ($html === false || empty($html)) {
        http_response_code(500);
        echo json_encode(['error' => 'ไม่สามารถดึงข้อมูล HTML จาก Forex Factory ได้, Server อาจถูกบล็อกหรือเชื่อมต่อไม่ได้']);
        exit;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!@$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
         http_response_code(500);
         echo json_encode(['error' => 'ไม่สามารถประมวลผล HTML ที่ดึงมาได้']);
         exit;
    }
    $xpath = new DOMXPath($dom);

    $items = [];
    $rows = $xpath->query("//tr[contains(@class, 'calendar__row')]");

    if ($rows->length === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'ไม่พบแถวข้อมูลข่าวใน XPath ที่กำหนด (โครงสร้างเว็บอาจมีการเปลี่ยนแปลง)']);
        exit;
    }
    
    $current_date = '';

    foreach ($rows as $row) {
        $row_class = $row->getAttribute('class');

        // ตรวจจับแถวที่เป็นวันที่ (Date row)
        if (strpos($row_class, 'calendar__row--day') !== false) {
             $date_node = $xpath->query(".//td[contains(@class, 'calendar__date')]/span", $row);
             if($date_node->length > 0) {
                $current_date = trim($date_node->item(0)->textContent);
             }
             continue;
        }

        // ตรวจจับแถวข้อมูลข่าว
        if (strpos($row_class, 'calendar__row--grey') !== false || strpos($row_class, 'calendar__row--white') !== false) {
            $time = trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__time')])", $row));
            $currency = trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__currency')])", $row));
            $impact_title = trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__impact')]/span/@title)", $row));
            $event = trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__event')])", $row));
            
            if (empty($event) && empty($currency)) continue;
            
            $items[] = [
                'date' => $current_date,
                'time' => $time,
                'currency' => $currency,
                'impact' => $impact_title ?: 'Holiday',
                'event' => $event,
                'actual' => trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__actual')])", $row)),
                'forecast' => trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__forecast')])", $row)),
                'previous' => trim($xpath->evaluate("string(.//td[contains(@class, 'calendar__previous')])", $row))
            ];
        }
    }

    if (empty($items)) {
         http_response_code(404);
         echo json_encode(['error' => 'สคริปต์ทำงานสำเร็จ แต่ไม่พบข้อมูลข่าวที่สามารถประมวลผลได้']);
         exit;
    }

    echo json_encode($items);
}

fetchForexFactoryNews();