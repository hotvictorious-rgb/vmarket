<?php

$ch = curl_init('http://127.0.0.1:8000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

preg_match_all('/<script[^>]+src="([^"]+)"[^>]*>/i', $html, $matches);
$jsUrls = $matches[1] ?? [];

echo "Found " . count($jsUrls) . " JS tags:\n";
foreach ($jsUrls as $url) {
    if (str_starts_with($url, '/')) {
        $url = 'http://127.0.0.1:8000' . $url;
    }
    $c = curl_init($url);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_NOBODY, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 3);
    curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    echo "  [" . ($code == 200 ? "OK" : "ERR $code") . "] $url\n";
}
