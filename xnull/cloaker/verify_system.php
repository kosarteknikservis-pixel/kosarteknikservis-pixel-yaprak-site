<?php
/**
 * CLOAKER SIMULATOR & VERIFIER v1.0
 * Bu araç, sisteminizin botları nasıl yakaladığını test etmenizi sağlar.
 */

include '../controller/config.php';
include '../../include/head.php'; // GetIP() vb. için

if (!isset($_SESSION['kullanici_adi'])) {
    die("Sadece admin girişi yapmış olanlar bu testi çalıştırabilir.");
}

$test_scenarios = [
    [
        'name' => 'Googlebot Taklidi (User-Agent)',
        'ua' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'ip' => '66.249.66.1',
        'ref' => '',
        'expected' => 'BLOCKED (Bot UA)'
    ],
    [
        'name' => 'Veri Merkezi IP (Amazon AWS)',
        'ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0.4472.124',
        'ip' => '3.5.140.2',
        'ref' => 'true_key', // Ref key olsa bile IP'den yakalanmalı
        'expected' => 'BLOCKED (IP Blocklist)'
    ],
    [
        'name' => 'Yanlış/Eksik Ref Key (Ana Sayfada)',
        'ua' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)',
        'ip' => '85.105.100.100', // Normal TR IP
        'ref' => 'wrong',
        'expected' => 'BLOCKED (Wrong Ref Key)'
    ],
    [
        'name' => 'Gerçek Kullanıcı (Doğru Ref Key + Normal IP)',
        'ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        'ip' => '85.105.100.100',
        'ref' => 'true_key',
        'expected' => 'PASSED'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cloaker Test Paneli</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2><i class="fa fa-shield"></i> Cloaker Güvenlik Simülatörü</h2>
    <p class="text-muted">Sisteminizin farklı senaryolarda nasıl tepki verdiğini buradan görebilirsiniz.</p>
    <hr>
    
    <div class="table-responsive">
        <table class="table table-bordered bg-white">
            <thead class="thead-dark">
                <tr>
                    <th>Senaryo</th>
                    <th>Simüle Edilen Veri</th>
                    <th>Beklenen</th>
                    <th>Sistem Sonucu</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Gerçek Fonksiyonu Buraya Tanımlayalım (Test için)
                if (!function_exists('testCIDR')) {
                    function testCIDR($ip, $cidr) {
                        if (strpos($cidr, '/') === false) return $ip == $cidr;
                        list($subnet, $mask) = explode('/', $cidr);
                        $ip_long = ip2long($ip);
                        $subnet_long = ip2long($subnet);
                        if (!$ip_long || !$subnet_long) return false;
                        $mask_long = -1 << (32 - (int)$mask);
                        return ($ip_long & $mask_long) == ($subnet_long & $mask_long);
                    }
                }

                foreach ($test_scenarios as $test): ?>
                <tr>
                    <td><strong><?php echo $test['name']; ?></strong></td>
                    <td class="small">
                        IP: <?php echo $test['ip']; ?><br>
                        UA: <?php echo substr($test['ua'], 0, 50); ?>...<br>
                        Ref: <?php echo $test['ref']; ?>
                    </td>
                    <td><span class="badge badge-info"><?php echo $test['expected']; ?></span></td>
                    <td>
                        <?php 
                        $is_bot = false;
                        $reason = "Geçti (PASSED)";
                        
                        // 1. Ref Key Kontrolü
                        $ref_key = $settingsprint['ayar_cloaker_key'] ?? '';
                        $test_ref = ($test['ref'] == 'true_key') ? $ref_key : $test['ref'];
                        if (!empty($ref_key) && $test_ref != $ref_key) {
                            $is_bot = true; $reason = "Engellendi (Yanlış Ref Key)";
                        }
                        
                        // 2. UA Kontrolü
                        if (!$is_bot) {
                            $bot_agents = ['facebookexternalhit', 'facebot', 'googlebot', 'adsbot', 'google'];
                            foreach ($bot_agents as $agent) {
                                if (strpos(strtolower($test['ua']), $agent) !== false) { 
                                    $is_bot = true; $reason = "Engellendi (Bot UA: $agent)"; 
                                    break; 
                                }
                            }
                        }
                        
                        // 3. IP Blacklist (Dosyadan Gerçek CIDR Kontrolü)
                        if (!$is_bot) {
                            $file = dirname(__FILE__) . '/blocked_ips.txt';
                            if (file_exists($file)) {
                                $handle = fopen($file, "r");
                                while (($line = fgets($handle)) !== false) {
                                    $line = trim($line);
                                    if (empty($line) || $line[0] === '#') continue;
                                    if (testCIDR($test['ip'], $line)) {
                                        $is_bot = true;
                                        $reason = "Engellendi (IP Blocklist: $line)";
                                        break;
                                    }
                                }
                                fclose($handle);
                            }
                        }

                        if ($is_bot) {
                            echo '<span class="badge badge-danger">🛡️ ' . $reason . '</span>';
                        } else {
                            echo '<span class="badge badge-success">✅ ' . $reason . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">Sistem Durumu</div>
                <div class="card-body">
                    <p><strong>Cloaker:</strong> <?php echo ($settingsprint['ayar_cloaker_on'] == 1) ? '<span class="text-success">AKTİF</span>' : '<span class="text-danger">KAPALI</span>'; ?></p>
                    <p><strong>Mod:</strong> <?php echo ($settingsprint['ayar_cloaker_dns_mode'] == 'agresif') ? '<span class="text-danger">AGRESİF (Sıkı)</span>' : '<span class="text-info">ESNEK (Hit Dostu)</span>'; ?></p>
                    <p><strong>Safe Page:</strong> <code><?php echo $settingsprint['ayar_safe_page']; ?></code></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-info border-0 shadow-sm">
                <strong>İpucu:</strong> Bu sayfada sadece simülasyon yapılıyor. Gerçek bir bot geldiğinde <code>core.php</code> dosyası bu mantığı kullanarak botu <code><?php echo $settingsprint['ayar_safe_page']; ?></code> sayfasına fırlatır.
            </div>
        </div>
    </div>
</div>
</body>
</html>
