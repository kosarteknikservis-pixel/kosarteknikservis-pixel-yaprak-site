<?php
/**
 * Türkiye 81 il + ilçeler — il ve ilce tablolarını sıfırlayıp güncel veriyle doldurur.
 * Kaynak: controller/data/turkiye_il_ilce.json (turkiyeapi.dev)
 *
 * Web: oturum açıkken
 *   rebuild_turkiye_il_ilce.php?confirm=1&token=SABITTEKI_DEGER
 *
 * CLI (oturum gerekmez):
 *   php rebuild_turkiye_il_ilce.php --run --token=SABITTEKI_DEGER
 */
require_once __DIR__ . '/config.php';

define('REBUILD_SECRET', 'turkiye_il_ilce_2026_degistir');

$isCli = (PHP_SAPI === 'cli');

function turkiye_il_ilce_table_has_column(PDO $db, string $table, string $column): bool
{
    $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($t === '' || $c === '') {
        return false;
    }
    $q = $db->query('SHOW COLUMNS FROM `' . $t . '` LIKE ' . $db->quote($c));
    return $q && (bool) $q->fetch(PDO::FETCH_ASSOC);
}

/**
 * @return array{ok: bool, il: int, ilce: int, error?: string}
 */
function turkiye_il_ilce_rebuild(PDO $db, string $jsonPath): array
{
    if (!is_readable($jsonPath)) {
        return ['ok' => false, 'il' => 0, 'ilce' => 0, 'error' => 'JSON dosyası okunamadı: ' . $jsonPath];
    }
    $raw = file_get_contents($jsonPath);
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data['data']) || !is_array($data['data'])) {
        return ['ok' => false, 'il' => 0, 'ilce' => 0, 'error' => 'JSON parse hatası.'];
    }

    $has_ilce_key = turkiye_il_ilce_table_has_column($db, 'ilce', 'ilce_key');

    try {
        // TRUNCATE MySQL'de implicit COMMIT verir; transaction sadece INSERT'lerde
        $db->exec('SET FOREIGN_KEY_CHECKS=0');
        $db->exec('TRUNCATE TABLE `ilce`');
        $db->exec('TRUNCATE TABLE `il`');
        $db->exec('SET FOREIGN_KEY_CHECKS=1');

        $db->beginTransaction();

        $insIl = $db->prepare('INSERT INTO `il` (`id`, `il_adi`, `il_plaka`) VALUES (:id, :il_adi, :il_plaka)');

        if ($has_ilce_key) {
            $insIlce = $db->prepare('INSERT INTO `ilce` (`ilce_adi`, `il_plaka`, `il_id`, `ilce_key`) VALUES (:ilce_adi, :il_plaka, :il_id, :ilce_key)');
        } else {
            $insIlce = $db->prepare('INSERT INTO `ilce` (`ilce_adi`, `il_plaka`, `il_id`) VALUES (:ilce_adi, :il_plaka, :il_id)');
        }

        $ilCount = 0;
        $ilceCount = 0;

        foreach ($data['data'] as $prov) {
            $pid = (int) ($prov['id'] ?? 0);
            $pname = trim((string) ($prov['name'] ?? ''));
            if ($pid < 1 || $pid > 81 || $pname === '') {
                continue;
            }
            $plaka = str_pad((string) $pid, 2, '0', STR_PAD_LEFT);
            $insIl->execute([
                'id' => $pid,
                'il_adi' => $pname,
                'il_plaka' => $plaka,
            ]);
            $ilCount++;

            $districts = isset($prov['districts']) && is_array($prov['districts']) ? $prov['districts'] : [];
            foreach ($districts as $d) {
                $dname = trim((string) ($d['name'] ?? ''));
                if ($dname === '') {
                    continue;
                }
                $params = [
                    'ilce_adi' => $dname,
                    'il_plaka' => $plaka,
                    'il_id' => $pid,
                ];
                if ($has_ilce_key) {
                    $params['ilce_key'] = (int) ($d['id'] ?? 0);
                }
                $insIlce->execute($params);
                $ilceCount++;
            }
        }

        $db->commit();
        return ['ok' => true, 'il' => $ilCount, 'ilce' => $ilceCount];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['ok' => false, 'il' => 0, 'ilce' => 0, 'error' => $e->getMessage()];
    }
}

/* ---------- CLI ---------- */
if ($isCli) {
    $run = in_array('--run', $argv ?? [], true);
    $token = '';
    foreach ($argv ?? [] as $a) {
        if (strpos($a, '--token=') === 0) {
            $token = substr($a, 8);
        }
    }
    if (!$run || $token !== REBUILD_SECRET) {
        fwrite(STDERR, "Kullanım: php rebuild_turkiye_il_ilce.php --run --token=" . REBUILD_SECRET . "\n");
        exit(1);
    }
    $jsonPath = __DIR__ . '/data/turkiye_il_ilce.json';
    $out = turkiye_il_ilce_rebuild($db, $jsonPath);
    if ($out['ok']) {
        echo "Tamam: {$out['il']} il, {$out['ilce']} ilçe yazıldı.\n";
        exit(0);
    }
    fwrite(STDERR, "Hata: " . ($out['error'] ?? 'bilinmiyor') . "\n");
    exit(1);
}

/* ---------- Web ---------- */
if (!isset($_SESSION['kullanici_adi'])) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Yetkisiz.';
    exit;
}

$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
if ($token !== REBUILD_SECRET || !isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>Bu işlem <code>il</code> ve <code>ilce</code> tablolarını <strong>tamamen sıfırlar</strong> ve güncel veriyle doldurur.</p>';
    echo '<p>Devam: <code>?confirm=1&amp;token=...</code> (sabit: REBUILD_SECRET)</p>';
    exit;
}

$jsonPath = __DIR__ . '/data/turkiye_il_ilce.json';
$out = turkiye_il_ilce_rebuild($db, $jsonPath);
header('Content-Type: text/html; charset=utf-8');
if ($out['ok']) {
    echo '<p><strong>Tamam.</strong> ' . (int) $out['il'] . ' il, ' . (int) $out['ilce'] . ' ilçe yazıldı.</p>';
    echo '<p><a href="../siparis-ekle.php">Manuel sipariş</a> — formdan il/ilçe test edin.</p>';
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo '<p>Hata: ' . htmlspecialchars($out['error'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
}
