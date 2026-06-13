<?php
require_once 'controller/config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    header('Location: login.php');
    exit;
}

function il_ilce_yonetimi_has_column(PDO $db, string $table, string $column): bool
{
    $t = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($t === '' || $c === '') {
        return false;
    }
    $q = $db->query('SHOW COLUMNS FROM `' . $t . '` LIKE ' . $db->quote($c));
    return $q && (bool) $q->fetch(PDO::FETCH_ASSOC);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['yeni_il_ekle'])) {
        $il_adi = trim((string) ($_POST['il_adi'] ?? ''));
        if ($il_adi === '') {
            $error = 'İl adı giriniz.';
        } elseif (mb_strlen($il_adi) > 191) {
            $error = 'İl adı çok uzun.';
        } else {
            $plaka_in = preg_replace('/\s+/', '', trim((string) ($_POST['il_plaka'] ?? '')));
            $nextId = (int) $db->query('SELECT COALESCE(MAX(id), 0) + 1 FROM il')->fetchColumn();
            if ($nextId < 1) {
                $nextId = 1;
            }
            if ($plaka_in === '') {
                $plaka_in = (string) $nextId;
                if (strlen($plaka_in) <= 2) {
                    $plaka_in = str_pad($plaka_in, 2, '0', STR_PAD_LEFT);
                }
            }
            $dup = $db->prepare('SELECT id FROM il WHERE il_plaka = ? OR il_adi = ? LIMIT 1');
            $dup->execute([$plaka_in, $il_adi]);
            if ($dup->fetch(PDO::FETCH_ASSOC)) {
                $error = 'Bu plaka kodu veya il adı zaten kayıtlı.';
            } else {
                try {
                    $ins = $db->prepare('INSERT INTO il (id, il_adi, il_plaka) VALUES (?, ?, ?)');
                    $ins->execute([$nextId, $il_adi, $plaka_in]);
                    header('Location: il-ilce-yonetimi.php?ok=il');
                    exit;
                } catch (Throwable $e) {
                    $error = 'İl eklenemedi. Plaka kodu benzersiz olmalı.';
                }
            }
        }
    } elseif (isset($_POST['yeni_ilce_ekle'])) {
        $il_id = (int) ($_POST['il_id'] ?? 0);
        $ilce_adi = trim((string) ($_POST['ilce_adi'] ?? ''));
        if ($il_id < 1) {
            $error = 'Önce bir il seçin.';
        } elseif ($ilce_adi === '') {
            $error = 'İlçe adı giriniz.';
        } elseif (mb_strlen($ilce_adi) > 191) {
            $error = 'İlçe adı çok uzun.';
        } else {
            $ils = $db->prepare('SELECT id, il_plaka FROM il WHERE id = ? LIMIT 1');
            $ils->execute([$il_id]);
            $ilRow = $ils->fetch(PDO::FETCH_ASSOC);
            if (!$ilRow) {
                $error = 'Seçilen il bulunamadı.';
            } else {
                $hasKey = il_ilce_yonetimi_has_column($db, 'ilce', 'ilce_key');
                try {
                    if ($hasKey) {
                        $nextKey = (int) $db->query('SELECT COALESCE(MAX(ilce_key), 0) + 1 FROM ilce')->fetchColumn();
                        $ins = $db->prepare(
                            'INSERT INTO ilce (ilce_adi, il_plaka, il_id, ilce_key) VALUES (?, ?, ?, ?)'
                        );
                        $ins->execute([$ilce_adi, $ilRow['il_plaka'], $il_id, $nextKey]);
                    } else {
                        $ins = $db->prepare(
                            'INSERT INTO ilce (ilce_adi, il_plaka, il_id) VALUES (?, ?, ?)'
                        );
                        $ins->execute([$ilce_adi, $ilRow['il_plaka'], $il_id]);
                    }
                    header('Location: il-ilce-yonetimi.php?ok=ilce');
                    exit;
                } catch (Throwable $e) {
                    $error = 'İlçe eklenemedi.';
                }
            }
        }
    } elseif (isset($_POST['sil_il'])) {
        $sil_il_id = (int) ($_POST['il_id'] ?? 0);
        if ($sil_il_id < 1) {
            $error = 'Geçersiz il.';
        } else {
            try {
                $db->beginTransaction();
                $db->prepare('DELETE FROM ilce WHERE il_id = ?')->execute([$sil_il_id]);
                $st = $db->prepare('DELETE FROM il WHERE id = ?');
                $st->execute([$sil_il_id]);
                if ($st->rowCount() < 1) {
                    $db->rollBack();
                    $error = 'İl bulunamadı.';
                } else {
                    $db->commit();
                    header('Location: il-ilce-yonetimi.php?ok=sil_il');
                    exit;
                }
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $error = 'İl silinemedi.';
            }
        }
    } elseif (isset($_POST['sil_ilce'])) {
        $sil_ilce_id = (int) ($_POST['ilce_id'] ?? 0);
        if ($sil_ilce_id < 1) {
            $error = 'Geçersiz ilçe.';
        } else {
            try {
                $st = $db->prepare('DELETE FROM ilce WHERE id = ?');
                $st->execute([$sil_ilce_id]);
                if ($st->rowCount() < 1) {
                    $error = 'İlçe bulunamadı.';
                } else {
                    $retIl = (int) ($_POST['return_il_id'] ?? 0);
                    if ($retIl > 0) {
                        header('Location: il-ilce-yonetimi.php?' . http_build_query(['gor_il_id' => $retIl, 'ok' => 'sil_ilce']));
                    } else {
                        header('Location: il-ilce-yonetimi.php?ok=sil_ilce');
                    }
                    exit;
                }
            } catch (Throwable $e) {
                $error = 'İlçe silinemedi.';
            }
        }
    }
}

if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'il') {
        $success = 'İl veritabanına eklendi.';
    } elseif ($_GET['ok'] === 'ilce') {
        $success = 'İlçe veritabanına eklendi.';
    } elseif ($_GET['ok'] === 'sil_il') {
        $success = 'İl ve bağlı tüm ilçeler veritabanından silindi.';
    } elseif ($_GET['ok'] === 'sil_ilce') {
        $success = 'İlçe veritabanından silindi.';
    }
}

$gor_il_id = isset($_GET['gor_il_id']) ? (int) $_GET['gor_il_id'] : 0;
$ilce_filtered = [];
if ($gor_il_id > 0) {
    $qIlce = $db->prepare('SELECT id, ilce_adi, il_plaka, il_id FROM ilce WHERE il_id = ? ORDER BY ilce_adi ASC');
    $qIlce->execute([$gor_il_id]);
    $ilce_filtered = $qIlce->fetchAll(PDO::FETCH_ASSOC);
}

$il_list = $db->query('SELECT id, il_adi, il_plaka FROM il ORDER BY il_adi ASC')->fetchAll(PDO::FETCH_ASSOC);
$il_sayisi = count($il_list);
$ilce_sayisi = (int) $db->query('SELECT COUNT(*) FROM ilce')->fetchColumn();

$panel_il_acik = (isset($_GET['ok']) && $_GET['ok'] === 'sil_il')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_il']) && $error !== '');
$panel_ilce_acik = $gor_il_id > 0
    || (isset($_GET['ok']) && $_GET['ok'] === 'sil_ilce')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_ilce']) && $error !== '');
$panel_yeni_il_acik = (isset($_GET['ok']) && $_GET['ok'] === 'il')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_il_ekle']) && $error !== '');
$panel_yeni_ilce_acik = (isset($_GET['ok']) && $_GET['ok'] === 'ilce')
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_ilce_ekle']) && $error !== '');

$title = 'İl / İlçe Yönetimi';
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <style>
        .il-ilce-collapse-head { cursor: pointer; user-select: none; }
        .il-ilce-collapse-head:hover { background: rgba(0,0,0,.04) !important; }
        .il-ilce-collapse-head .il-ilce-chevron { transition: transform .2s ease; }
        .il-ilce-collapse-head:not(.collapsed) .il-ilce-chevron { transform: rotate(180deg); }
    </style>
    <div class="page-header">
        <h2>İl / İlçe (Manuel)</h2>
    </div>

    <?php if ($error !== '') { ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
    <?php if ($success !== '') { ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>

    <div class="row">
        <div class="col-md-12 margin-b-20">
            <div class="alert alert-info mb-0">
                Toplam <strong><?php echo (int) $il_sayisi; ?></strong> il,
                <strong><?php echo (int) $ilce_sayisi; ?></strong> ilçe kaydı.
                Toplu güncelleme için <code>xnull/controller/rebuild_turkiye_il_ilce.php</code> kullanılır.
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-heading card-default clearfix il-ilce-collapse-head<?php echo $panel_yeni_il_acik ? '' : ' collapsed'; ?>" data-toggle="collapse" data-target="#collapseYeniIl" aria-expanded="<?php echo $panel_yeni_il_acik ? 'true' : 'false'; ?>">
                    <span class="float-right il-ilce-chevron"><i class="fa fa-angle-down"></i></span>
                    Yeni il ekle
                </div>
                <div id="collapseYeniIl" class="collapse<?php echo $panel_yeni_il_acik ? ' show' : ''; ?>">
                    <div class="card-block">
                        <form method="post" class="form-horizontal">
                            <div class="form-group">
                                <label>İl adı</label>
                                <input type="text" name="il_adi" class="form-control" required maxlength="191" placeholder="Örn: Yurtdışı">
                            </div>
                            <div class="form-group">
                                <label>Plaka kodu (isteğe bağlı)</label>
                                <input type="text" name="il_plaka" class="form-control" maxlength="10" placeholder="Boş bırakılırsa sıradaki id’ye göre atanır">
                            </div>
                            <button type="submit" name="yeni_il_ekle" value="1" class="btn btn-success btn-icon">
                                <i class="fa fa-floppy-o"></i> İl kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-heading card-default clearfix il-ilce-collapse-head<?php echo $panel_yeni_ilce_acik ? '' : ' collapsed'; ?>" data-toggle="collapse" data-target="#collapseYeniIlce" aria-expanded="<?php echo $panel_yeni_ilce_acik ? 'true' : 'false'; ?>">
                    <span class="float-right il-ilce-chevron"><i class="fa fa-angle-down"></i></span>
                    Yeni ilçe ekle
                </div>
                <div id="collapseYeniIlce" class="collapse<?php echo $panel_yeni_ilce_acik ? ' show' : ''; ?>">
                    <div class="card-block">
                        <form method="post" class="form-horizontal">
                            <div class="form-group">
                                <label>Bağlı il</label>
                                <select name="il_id" class="form-control" required>
                                    <option value="">— Seçin —</option>
                                    <?php foreach ($il_list as $il) { ?>
                                        <option value="<?php echo (int) $il['id']; ?>">
                                            <?php echo htmlspecialchars($il['il_adi'] . ' (' . $il['il_plaka'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>İlçe adı</label>
                                <input type="text" name="ilce_adi" class="form-control" required maxlength="191" placeholder="İlçe adı">
                            </div>
                            <button type="submit" name="yeni_ilce_ekle" value="1" class="btn btn-success btn-icon">
                                <i class="fa fa-floppy-o"></i> İlçe kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row margin-t-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default clearfix il-ilce-collapse-head<?php echo $panel_il_acik ? '' : ' collapsed'; ?>" data-toggle="collapse" data-target="#collapseIlSil" aria-expanded="<?php echo $panel_il_acik ? 'true' : 'false'; ?>">
                    <span class="float-right il-ilce-chevron"><i class="fa fa-angle-down"></i></span>
                    İl sil <small class="text-muted">(<?php echo (int) $il_sayisi; ?> kayıt)</small>
                </div>
                <div id="collapseIlSil" class="collapse<?php echo $panel_il_acik ? ' show' : ''; ?>">
                    <div class="card-block">
                    <p class="text-muted">İl silindiğinde o ile bağlı <strong>tüm ilçeler</strong> de silinir.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>İl adı</th>
                                    <th>Plaka</th>
                                    <th style="width:100px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($il_list as $il) { ?>
                                    <tr>
                                        <td><?php echo (int) $il['id']; ?></td>
                                        <td><?php echo htmlspecialchars((string) $il['il_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string) $il['il_plaka'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Bu ili ve bağlı tüm ilçeleri silmek istediğinize emin misiniz?');">
                                                <input type="hidden" name="il_id" value="<?php echo (int) $il['id']; ?>">
                                                <button type="submit" name="sil_il" value="1" class="btn btn-danger btn-xs">
                                                    <i class="fa fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row margin-t-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default clearfix il-ilce-collapse-head<?php echo $panel_ilce_acik ? '' : ' collapsed'; ?>" data-toggle="collapse" data-target="#collapseIlceSil" aria-expanded="<?php echo $panel_ilce_acik ? 'true' : 'false'; ?>">
                    <span class="float-right il-ilce-chevron"><i class="fa fa-angle-down"></i></span>
                    İlçe sil
                    <?php if ($gor_il_id > 0) { ?>
                        <small class="text-muted">(<?php echo count($ilce_filtered); ?> ilçe listeleniyor)</small>
                    <?php } ?>
                </div>
                <div id="collapseIlceSil" class="collapse<?php echo $panel_ilce_acik ? ' show' : ''; ?>">
                    <div class="card-block">
                    <form method="get" class="form-inline margin-b-15">
                        <div class="form-group">
                            <label class="margin-r-10">İl seçin</label>
                            <select name="gor_il_id" class="form-control" style="min-width:220px;">
                                <option value="">— Seçin —</option>
                                <?php foreach ($il_list as $il) { ?>
                                    <option value="<?php echo (int) $il['id']; ?>"<?php echo $gor_il_id === (int) $il['id'] ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($il['il_adi'] . ' (' . $il['il_plaka'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary margin-l-10">Listele</button>
                    </form>
                    <?php if ($gor_il_id > 0) { ?>
                        <?php if (count($ilce_filtered) === 0) { ?>
                            <p class="text-muted">Bu ilde kayıtlı ilçe yok.</p>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>İlçe adı</th>
                                            <th>Plaka</th>
                                            <th style="width:100px;">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ilce_filtered as $ic) { ?>
                                            <tr>
                                                <td><?php echo (int) $ic['id']; ?></td>
                                                <td><?php echo htmlspecialchars((string) $ic['ilce_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars((string) $ic['il_plaka'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Bu ilçeyi silmek istediğinize emin misiniz?');">
                                                        <input type="hidden" name="ilce_id" value="<?php echo (int) $ic['id']; ?>">
                                                        <input type="hidden" name="return_il_id" value="<?php echo (int) $gor_il_id; ?>">
                                                        <button type="submit" name="sil_ilce" value="1" class="btn btn-danger btn-xs">
                                                            <i class="fa fa-trash"></i> Sil
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <p class="text-muted mb-0">İlçeleri görmek ve silmek için yukarıdan bir il seçip &quot;Listele&quot;ye basın.</p>
                    <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'footer.php'; ?>
