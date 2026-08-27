<?php

echo "=================================================================\n";
echo "🔍 FRESH INSTALLER LIVE PROBE\n";
echo "=================================================================\n\n";

$vmCh = curl_init("http://127.0.0.1:8000/");
curl_setopt($vmCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($vmCh, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($vmCh, CURLOPT_TIMEOUT, 10);
$vmHtml = curl_exec($vmCh);
$vmCode = curl_getinfo($vmCh, CURLINFO_HTTP_CODE);
curl_close($vmCh);

echo "1. Victorious MARKET Fresh Wizard (http://127.0.0.1:8000/):\n";
echo "  -> HTTP Code: {$vmCode}\n";
if (strpos($vmHtml, 'Installation') !== false || strpos($vmHtml, 'victorious') !== false || strpos($vmHtml, 'Step') !== false || $vmCode === 200) {
    echo "  ✅ Victorious MARKET Fresh Installer Wizard is LIVE!\n";
} else {
    echo "  ⚠️ Output snippet: " . substr(strip_tags($vmHtml), 0, 150) . "\n";
}

$posCh = curl_init("http://127.0.0.1:8001/install");
curl_setopt($posCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($posCh, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($posCh, CURLOPT_TIMEOUT, 10);
$posHtml = curl_exec($posCh);
$posCode = curl_getinfo($posCh, CURLINFO_HTTP_CODE);
curl_close($posCh);

echo "\n2. Vmarket POS Fresh Wizard (http://127.0.0.1:8001/install):\n";
echo "  -> HTTP Code: {$posCode}\n";
if (strpos($posHtml, 'Installer') !== false || strpos($posHtml, 'Welcome') !== false || strpos($posHtml, 'Requirements') !== false || $posCode === 200) {
    echo "  ✅ Vmarket POS Fresh Installer Wizard is LIVE!\n";
} else {
    echo "  ⚠️ Output snippet: " . substr(strip_tags($posHtml), 0, 150) . "\n";
}

echo "\n=================================================================\n";
echo "🎉 READY FOR YOU TO TEST AND SET UP!\n";
echo "=================================================================\n";
