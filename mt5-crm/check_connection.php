<?php
echo "<h1>Connectivity Test to MetaAPI Server</h1>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

$hostname = 'mt-manager-api-v1.agiliumtrade.ag';
$google_hostname = 'www.google.com';

echo "<hr><h2>1. Testing DNS Resolution</h2>";

echo "<h4>Attempting to resolve: {$hostname}</h4>";
$ip = gethostbyname($hostname);

if ($ip === $hostname) {
    echo "<p style='color:red; font-weight:bold;'>[FAIL] Could not resolve host: {$hostname}. This is the main problem.</p>";
} else {
    echo "<p style='color:green; font-weight:bold;'>[SUCCESS] Successfully resolved {$hostname} to IP address: {$ip}</p>";
}

echo "<h4>Attempting to resolve: {$google_hostname} (for comparison)</h4>";
$google_ip = gethostbyname($google_hostname);
if ($google_ip === $google_hostname) {
    echo "<p style='color:red; font-weight:bold;'>[FAIL] Could not resolve google.com. This indicates a general DNS problem on the server.</p>";
} else {
    echo "<p style='color:green; font-weight:bold;'>[SUCCESS] Successfully resolved google.com to IP address: {$google_ip}</p>";
}

echo "<hr><h2>2. Testing cURL Connection</h2>";
echo "<h4>Attempting cURL connection to: {$hostname}</h4>";

$ch = curl_init($hostname);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_exec($ch);

if(curl_errno($ch)){
    echo '<p style="color:red; font-weight:bold;">[FAIL] cURL returned an error: ' . curl_error($ch) . '</p>';
} else {
    echo '<p style="color:green; font-weight:bold;">[SUCCESS] cURL connection test to the host was successful (no cURL error reported).</p>';
}
curl_close($ch);

echo "<hr><p><b>Conclusion:</b> If you see a '[FAIL]' message, please send a screenshot of this page to your hosting provider or server administrator for assistance.</p>";
?>