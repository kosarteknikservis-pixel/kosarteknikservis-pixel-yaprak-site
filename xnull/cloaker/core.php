<?php
/**
 * CLOAKER CORE SYSTEM v2.1 - ULTIMATE HARDENED
 * Facebook, Instagram, Google Bot Detection & GeoIP Lock
 */

if (!function_exists('cloaker_ensure_traffic_log_table')) {
    function cloaker_ensure_traffic_log_table(PDO $db) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS cloaker_traffic_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip VARCHAR(45) NOT NULL,
                user_agent TEXT,
                vendor_label VARCHAR(32) NOT NULL DEFAULT '',
                blocked TINYINT(1) NOT NULL DEFAULT 0,
                reason VARCHAR(255) NOT NULL DEFAULT '',
                request_uri VARCHAR(500) NOT NULL DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_created (created_at),
                KEY idx_vendor (vendor_label),
                KEY idx_blocked (blocked)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            // tablo oluşturulamadıysa log atlanır
        }
    }
}

if (!function_exists('cloaker_ensure_ayar_stat_columns')) {
    function cloaker_ensure_ayar_stat_columns(PDO $db) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $cols = array(
            'ayar_cloaker_stat_total'   => 'INT NOT NULL DEFAULT 0',
            'ayar_cloaker_stat_passed'  => 'INT NOT NULL DEFAULT 0',
            'ayar_cloaker_stat_blocked' => 'INT NOT NULL DEFAULT 0',
        );
        foreach ($cols as $name => $def) {
            try {
                $db->exec('ALTER TABLE ayar ADD COLUMN `' . $name . '` ' . $def);
            } catch (Throwable $e) {
                // kolon zaten var
            }
        }
    }
}

if (!function_exists('cloaker_guess_vendor_label')) {
    /** User-agent (küçük harf) üzerinden tahmin — sadece günlük etiketi */
    function cloaker_guess_vendor_label($ua_lower) {
        if ($ua_lower === '') {
            return '';
        }
        if (preg_match('/facebook|meta-external|facebot|instagrambot|threadsbot|fbcrawl|whatsapp|\\binstagram\\//', $ua_lower)) {
            return 'meta';
        }
        if (preg_match('/googlebot|adsbot|google-inspection|feedfetcher|mediapartners-google|googleproducer|google web preview/', $ua_lower)) {
            return 'google';
        }
        if (strpos($ua_lower, 'bingbot') !== false || strpos($ua_lower, 'msnbot') !== false) {
            return 'microsoft';
        }
        return '';
    }
}

if (isset($settingsprint['ayar_cloaker_on']) && $settingsprint['ayar_cloaker_on'] == 1) {

    cloaker_ensure_ayar_stat_columns($db);

    // 0. SONSUZ DÖNGÜ VE SAYFA KONTROLÜ
    $current_page = basename($_SERVER['PHP_SELF']);
    $safe_page_setting = !empty($settingsprint['ayar_safe_page']) ? $settingsprint['ayar_safe_page'] : 'safe-page.php';
    
    // Eğer şu an güvenli sayfadaysak veya admin panelindeysek korumayı çalıştırma
    if ($current_page == $safe_page_setting || strpos($_SERVER['REQUEST_URI'] ?? '', '/xnull/') !== false) {
        return;
    }

    $is_bot = false;
    $detect_reason = "";
    
    // Yardımcı Fonksiyonlar (Geçerli)
    if (!function_exists('ipCIDRCheck')) {
        function ipCIDRCheck($ip, $cidr) {
            if (strpos($cidr, '/') === false) return $ip == $cidr;
            list($subnet, $mask) = explode('/', $cidr);
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            if (!$ip_long || !$subnet_long) return false;
            $mask_long = -1 << (32 - (int)$mask);
            return ($ip_long & $mask_long) == ($subnet_long & $mask_long);
        }
    }

    if (!function_exists('getHostnameWithTimeout')) {
        function getHostnameWithTimeout($ip, $timeout = 1) {
            $ptr = implode(".", array_reverse(explode(".", $ip))) . ".in-addr.arpa";
            $host_record = @dns_get_record($ptr, DNS_PTR);
            return ($host_record && isset($host_record[0]['target'])) ? $host_record[0]['target'] : $ip;
        }
    }

    $ip_address = GetIP();
    $user_agent_raw = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $user_agent = strtolower($user_agent_raw);
    
    // 1. WHITELIST (VIP)
    if (!empty($settingsprint['ayar_cloaker_whitelist'])) {
        $whitelist = preg_split('/\r\n|\r|\n/', $settingsprint['ayar_cloaker_whitelist']);
        foreach ($whitelist as $w_ip) {
            if (empty(trim($w_ip))) continue;
            if (ipCIDRCheck($ip_address, trim($w_ip))) return;
        }
    }

    // 2. REF KEY KONTROLÜ (Sadece Girişte Sorgula - İç sayfalarda hit kaçırma)
    // Reklam final URL'sinde mutlaka ?ref=ANAHTAR veya &ref=ANAHTAR olmalı (fbclid tek başına yetmez).
    $cloak_ref_key = trim((string) ($settingsprint['ayar_cloaker_key'] ?? ''));
    if ($cloak_ref_key !== '') {
        if (!isset($_SESSION['_cloaker_session_auth'])) {
            $got_ref = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
            if ($got_ref !== '' && hash_equals($cloak_ref_key, $got_ref)) {
                $_SESSION['_cloaker_session_auth'] = true;
            } else {
                 $is_bot = true;
                 $detect_reason = "Missing/Wrong Ref Key";
            }
        }
    }

    // 3. GEOIP ÜLKE KİLİDİ (Local Database - GeoLite2-City)
    if (!$is_bot && isset($settingsprint['ayar_cloaker_geoip_on']) && $settingsprint['ayar_cloaker_geoip_on'] == 1) {
        $dbPath = dirname(dirname(__DIR__)) . '/js/ajax/libs/GeoLite2-City.mmdb';
        $readerPath = dirname(dirname(__DIR__)) . '/js/ajax/libs/geoip2.php';
        
        if (file_exists($dbPath) && file_exists($readerPath)) {
            require_once $readerPath;
            $geoip = new GeoIP2($dbPath);
            // MMDBReader returns country data as part of city record
            // We need to check $geoip->reader->get($ip) to see the structure if city names are not enough
            try {
                $reader = new MMDBReader($dbPath);
                $record = $reader->get($ip_address);
                $user_country = $record['country']['iso_code'] ?? '??';
                
                $allowed = array_map('trim', explode(',', strtoupper($settingsprint['ayar_cloaker_allowed_countries'] ?? 'TR')));
                if (!in_array($user_country, $allowed)) {
                    $is_bot = true;
                    $detect_reason = "GeoIP: $user_country not allowed";
                }
            } catch (Exception $e) {
                // FALLBACK: If DB fails and aggressive mode is on
                if (($settingsprint['ayar_cloaker_dns_mode'] ?? 'esnek') == 'agresif') {
                    $is_bot = true;
                    $detect_reason = "GeoIP DB Error (Agresif Mode)";
                }
            }
        }
    }

    // 4. IP BLACKLIST (dosya + paneldeki manuel liste — ikisi de uygulanır)
    if (!$is_bot) {
        $blacklist_file = __DIR__ . '/blocked_ips.txt';
        if (file_exists($blacklist_file)) {
            $handle = fopen($blacklist_file, "r");
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
                if (ipCIDRCheck($ip_address, $line)) {
                    $is_bot = true;
                    $detect_reason = "IP Blocklist (file)";
                    break;
                }
            }
            fclose($handle);
        }
        if (!$is_bot && !empty(trim((string) ($settingsprint['ayar_cloaker_blacklist'] ?? '')))) {
            $db_bl = preg_split('/\r\n|\r|\n/', (string) $settingsprint['ayar_cloaker_blacklist']);
            foreach ($db_bl as $line) {
                $line = trim($line);
                if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                    continue;
                }
                if (ipCIDRCheck($ip_address, $line)) {
                    $is_bot = true;
                    $detect_reason = "IP Blocklist (panel)";
                    break;
                }
            }
        }
    }

    // 5. USER AGENT & HOSTNAME
    if (!$is_bot) {
        // Meta/Facebook yeni tarayıcıları (facebookbot, Meta-ExternalAgent vb.) eski listede yoktu — inceleme geçiyordu.
        // Not: "whatsapp" / "telegram" bilinçli olarak yok — uygulama içi tarayıcı UA'larında geçer;
        // reklam trafiğinin çoğu bu ortamlardan gelir; substring ile engellemek görselleri/siteyi "bozuk" gösterirdi.
        $bot_agents = [
            'facebookexternalhit', 'facebot', 'facebookbot', 'facebookcatalog',
            'meta-externalagent', 'meta-externalfetcher', 'meta-webindexer',
            'instagrambot', 'threadsbot',
            'googlebot', 'adsbot', 'google-inspectiontool', 'feedfetcher-google',
            'mediapartners-google', 'storebot-google', 'googleproducer',
            'twitterbot', 'linkedinbot', 'bingbot', 'slackbot', 'discordbot',
            'embedly', 'quora link preview', 'pinterestbot', 'tiktokspider', 'bytespider',
            'curl/', 'wget', 'python-requests', 'libwww-perl',
        ];
        foreach ($bot_agents as $agent) {
            if (strpos($user_agent, $agent) !== false) { $is_bot = true; $detect_reason = "Bot UA: $agent"; break; }
        }
        
        if (!$is_bot) {
            $hostname = strtolower(getHostnameWithTimeout($ip_address));
            $suspicious_hosts = ['amazonaws', 'googleusercontent', 'facebook.com', 'fbcdn', 'linode', 'digitalocean', 'datacenter', 'hosting', 'cloud'];
            foreach ($suspicious_hosts as $shost) {
                if (strpos($hostname, $shost) !== false) { $is_bot = true; $detect_reason = "Host: $shost"; break; }
            }
        }
    }

    // Trafik günlüğü (reklam / bot IP'lerini panelden izleme)
    cloaker_ensure_traffic_log_table($db);
    $vendor_lbl = cloaker_guess_vendor_label($user_agent);
    $uri_log = isset($_SERVER['REQUEST_URI']) ? substr((string) $_SERVER['REQUEST_URI'], 0, 500) : '';
    try {
        $ins = $db->prepare('INSERT INTO cloaker_traffic_log (ip, user_agent, vendor_label, blocked, reason, request_uri) VALUES (:ip,:ua,:vendor,:blocked,:reason,:uri)');
        $ins->execute(array(
            'ip' => substr($ip_address, 0, 45),
            'ua' => substr($user_agent_raw, 0, 2000),
            'vendor' => $vendor_lbl,
            'blocked' => $is_bot ? 1 : 0,
            'reason' => substr($detect_reason, 0, 255),
            'uri' => $uri_log,
        ));
    } catch (Exception $e) {
        // sessiz
    }

    // İstatistik ve Yönlendirme
    if ($is_bot) {
        try {
            $db->exec("UPDATE ayar SET ayar_cloaker_stat_total = ayar_cloaker_stat_total + 1, ayar_cloaker_stat_blocked = ayar_cloaker_stat_blocked + 1 WHERE ayar_id=0");
        } catch (Throwable $e) {
        }
        
        $method = $settingsprint['ayar_cloaker_method'] ?? 2;
        $redirect_url = (filter_var($safe_page_setting, FILTER_VALIDATE_URL)) ? $safe_page_setting : SITE_URL . $safe_page_setting;
        
        if ($method == 1) {
            header("Location: " . $redirect_url);
            exit();
        } else {
            // Shadow Mode: İçeriği sessizce safe-page'den çek
            $absolute_safe_path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $safe_page_setting;
            if (!filter_var($safe_page_setting, FILTER_VALIDATE_URL) && file_exists($absolute_safe_path)) {
                include($absolute_safe_path);
                exit();
            } else {
                header("Location: " . $redirect_url);
                exit();
            }
        }
    } else {
        try {
            $db->exec("UPDATE ayar SET ayar_cloaker_stat_total = ayar_cloaker_stat_total + 1, ayar_cloaker_stat_passed = ayar_cloaker_stat_passed + 1 WHERE ayar_id=0");
        } catch (Throwable $e) {
        }
        // JS Cookie (Headless Detection için)
        if (!headers_sent()) {
            setcookie('_cl_tk', 'active', time() + 86400, '/');
        }
    }
}
?>
