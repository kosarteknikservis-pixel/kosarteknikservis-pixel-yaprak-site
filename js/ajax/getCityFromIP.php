<?php
include __DIR__ . '/../../xnull/controller/config.php';
header('Content-Type: application/json; charset=utf-8');

function GetIP(){
    if(getenv("HTTP_CF_CONNECTING_IP")) {
        $ip = getenv("HTTP_CF_CONNECTING_IP");
    } elseif(getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } elseif(getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
        if (strstr($ip, ',')) {
            $tmp = explode (',', $ip);
            $ip = trim($tmp[0]);
        }
    } else {
        $ip = getenv("REMOTE_ADDR");
    }
    return $ip;
}

$ip = GetIP();
if ($ip === '::1') {
    $ip = '127.0.0.1';
}

// Use local MaxMind Database (GeoLite2-City.mmdb) exclusively
$dbPath = __DIR__ . '/libs/GeoLite2-City.mmdb';

if ($ip !== '' && $ip !== '127.0.0.1' && file_exists($dbPath)) {
    require_once __DIR__ . '/libs/geoip2.php';
    $geoip = new GeoIP2($dbPath);
    $city = $geoip->getCityByIP($ip);
    
    if ($city) {
        echo json_encode(['status' => 'success', 'city' => $city, 'source' => 'local']);
        exit;
    }
}

// Fallback: external IP geolocation
if ($ip !== '' && $ip !== '127.0.0.1') {
    $apiUrl = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,city,regionName,countryCode';
    $ctx = stream_context_create(array('http' => array('timeout' => 2)));
    $raw = @file_get_contents($apiUrl, false, $ctx);
    if ($raw !== false) {
        $j = json_decode($raw, true);
        if (is_array($j) && isset($j['status']) && $j['status'] === 'success' && strtoupper((string)($j['countryCode'] ?? '')) === 'TR') {
            $city = trim((string)($j['city'] ?? ''));
            if ($city === '') {
                $city = trim((string)($j['regionName'] ?? ''));
            }
            if ($city !== '') {
                echo json_encode(['status' => 'success', 'city' => $city, 'source' => 'ip-api']);
                exit;
            }
        }
    }
}

// Final fallback
echo json_encode(['status' => 'success', 'city' => 'Şehir Seçiniz', 'source' => 'fallback']);
?>
