<?php
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
require_once __DIR__ . '/controller/export_helpers.php';

$settingsprint = $db->query('SELECT * FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC) ?: [];

$durumlar = $db->query('SELECT id, ad FROM durum ORDER BY siralama ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);

$drm_sel   = isset($_GET['drm']) ? $_GET['drm'] : 'all';
$bas_tarih = isset($_GET['bas_tarih']) ? $_GET['bas_tarih'] : '';
$bit_tarih = isset($_GET['bit_tarih']) ? $_GET['bit_tarih'] : '';

$durum_by_id = [0 => 'Yeni Gelen Siparişler'];
foreach ($durumlar as $d) {
	$durum_by_id[(int) $d['id']] = $d['ad'];
}

$show_preview = (string) ($_SERVER['QUERY_STRING'] ?? '') !== '';

$preview_rows   = [];
$preview_total  = 0;
$preview_limit  = 500;
$site_adi_urun  = trim((string) ($settingsprint['ayar_title'] ?? ''));
if ($site_adi_urun === '') {
	$site_adi_urun = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'site';
}

if ($show_preview) {
	$where  = ' WHERE 1=1';
	$params = [];

	if ($drm_sel !== 'all' && $drm_sel !== '') {
		$where .= ' AND siparis_durum = :durum';
		$params['durum'] = (int) $drm_sel;
	}
	if ($bas_tarih !== '') {
		$where .= ' AND siparis_tarih >= :bas_tarih';
		$params['bas_tarih'] = $bas_tarih . ' 00:00:00';
	}
	if ($bit_tarih !== '') {
		$where .= ' AND siparis_tarih <= :bit_tarih';
		$params['bit_tarih'] = $bit_tarih . ' 23:59:59';
	}

	$cnt_sql = 'SELECT COUNT(*) FROM siparis' . $where;
	$cnt_st  = $db->prepare($cnt_sql);
	$cnt_st->execute($params);
	$preview_total = (int) $cnt_st->fetchColumn();

	$list_sql = 'SELECT * FROM siparis' . $where . ' ORDER BY siparis_tarih DESC, siparis_id DESC LIMIT ' . (int) $preview_limit;
	$list_st  = $db->prepare($list_sql);
	$list_st->execute($params);
	$preview_rows = $list_st->fetchAll(PDO::FETCH_ASSOC);
}

?>

<section class="main-content container">
	<div class="page-header" style="border: none; margin-bottom: 20px;">
		<h2 style="margin: 0; font-weight: 700; color: #2c3e50;">
			<i class="fa fa-file-excel-o" style="color: #1d6f42;"></i> Aras Kargo Excel
		</h2>
		<p class="text-muted" style="margin-top: 8px; margin-bottom: 0; font-size: 13px;">
			Filtreleyin, listeden kontrol edin; ardından Excel indirin.
		</p>
	</div>

	<div class="card card-default" style="border-radius: 12px; border: 1px solid #e8ecef; box-shadow: 0 4px 18px rgba(0,0,0,0.06); margin-bottom: 24px;">
		<div class="card-heading" style="background: linear-gradient(135deg, #f8fafb 0%, #fff 100%); border-radius: 12px 12px 0 0; padding: 14px 20px; border-bottom: 1px solid #eef2f5;">
			<strong style="color: #34495e;"><i class="fa fa-sliders"></i> Filtre ve indirme</strong>
		</div>
		<div class="card-block" style="padding: 20px 22px;">
			<p class="text-muted" style="font-size: 13px; margin-bottom: 18px; line-height: 1.55;">
				Excel sütunları: <code style="background:#f1f3f5;padding:2px 6px;border-radius:4px;font-size:12px;">mok, urun, ad, adres, ilce, sehir, tel</code> …
				<strong>urun</strong> sütununda site başlığı kullanılır: <em><?php echo htmlspecialchars($site_adi_urun, ENT_QUOTES, 'UTF-8'); ?></em>
			</p>

			<form method="GET" action="aras_kargo.php" class="row" style="margin: 0 -8px;">
				<div class="col-sm-6 col-md-3" style="padding: 8px;">
					<label class="control-label" style="font-size: 11px; text-transform: uppercase; color: #7f8c8d; font-weight: 700;">Durum</label>
					<select name="drm" class="form-control" style="border-radius: 8px; height: 40px;">
						<option value="all" <?php echo $drm_sel === 'all' ? 'selected' : ''; ?>>Tüm durumlar</option>
						<option value="0" <?php echo $drm_sel === '0' ? 'selected' : ''; ?>>Yeni Gelen Siparişler</option>
						<?php foreach ($durumlar as $d): ?>
							<option value="<?php echo (int) $d['id']; ?>" <?php echo (string) $drm_sel === (string) $d['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['ad']); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-sm-6 col-md-3" style="padding: 8px;">
					<label class="control-label" style="font-size: 11px; text-transform: uppercase; color: #7f8c8d; font-weight: 700;">Başlangıç</label>
					<input type="date" name="bas_tarih" class="form-control" value="<?php echo htmlspecialchars($bas_tarih); ?>" style="border-radius: 8px; height: 40px;">
				</div>
				<div class="col-sm-6 col-md-3" style="padding: 8px;">
					<label class="control-label" style="font-size: 11px; text-transform: uppercase; color: #7f8c8d; font-weight: 700;">Bitiş</label>
					<input type="date" name="bit_tarih" class="form-control" value="<?php echo htmlspecialchars($bit_tarih); ?>" style="border-radius: 8px; height: 40px;">
				</div>
				<div class="col-sm-12 col-md-3" style="padding: 8px; display: flex; flex-direction: column; justify-content: flex-end; gap: 8px;">
					<button type="submit" class="btn btn-primary btn-block" style="border-radius: 8px; font-weight: 600;">
						<i class="fa fa-filter"></i> Filtrele / Önizle
					</button>
					<button type="submit" formaction="controller/aras_kargo_export.php" formmethod="get" class="btn btn-success btn-block" style="border-radius: 8px; font-weight: 600;">
						<i class="fa fa-download"></i> Excel indir (.xlsx)
					</button>
					<a href="aras_kargo.php" class="btn btn-default btn-block" style="border-radius: 8px;"><i class="fa fa-refresh"></i> Sıfırla</a>
				</div>
			</form>
		</div>
	</div>

	<?php if ($show_preview): ?>
	<div class="card card-default" style="border-radius: 12px; border: 1px solid #e8ecef; box-shadow: 0 4px 18px rgba(0,0,0,0.06); overflow: hidden;">
		<div class="card-heading" style="background: #fff; padding: 14px 20px; border-bottom: 1px solid #eef2f5; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px;">
			<strong style="color: #34495e;"><i class="fa fa-list"></i> Sipariş önizlemesi</strong>
			<span class="label label-primary" style="font-size: 12px; padding: 6px 12px; border-radius: 20px;">
				<?php echo (int) $preview_total; ?> kayıt<?php echo $preview_total > $preview_limit ? ' (tabloda ilk ' . $preview_limit . ')' : ''; ?>
			</span>
		</div>
		<div class="card-block" style="padding: 0;">
			<?php if (count($preview_rows) === 0): ?>
				<div class="text-center text-muted" style="padding: 48px 20px;">
					<i class="fa fa-inbox" style="font-size: 42px; opacity: 0.35;"></i>
					<p style="margin-top: 16px; font-size: 15px;">Bu filtrelere uygun sipariş yok.</p>
				</div>
			<?php else: ?>
			<div class="table-responsive" style="max-height: 65vh; overflow: auto;">
				<table class="table table-hover table-striped" style="margin: 0; font-size: 13px;">
					<thead>
						<tr style="background: #f4f6f8; color: #2c3e50;">
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6; white-space: nowrap;">#</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6; white-space: nowrap;">Tarih</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6;">Durum</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6;">Ad</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6;">İl / İlçe</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6;">Telefon</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6; text-align: right;">Tutar</th>
							<th style="padding: 12px 14px; border-bottom: 2px solid #dee2e6; white-space: nowrap;">İşlem</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($preview_rows as $r):
							$sid = (int) ($r['siparis_id'] ?? 0);
							$d_id = (int) ($r['siparis_durum'] ?? 0);
							$d_ad = $durum_by_id[$d_id] ?? ('Durum #' . $d_id);
							$ts   = $r['siparis_tarih'] ?? '';
							?>
						<tr>
							<td style="vertical-align: middle; font-weight: 600; color: #3498db;"><?php echo $sid; ?></td>
							<td style="vertical-align: middle; white-space: nowrap;"><?php echo htmlspecialchars((string) $ts, ENT_QUOTES, 'UTF-8'); ?></td>
							<td style="vertical-align: middle;"><span class="label label-default" style="font-weight: 600; border-radius: 4px;"><?php echo htmlspecialchars($d_ad, ENT_QUOTES, 'UTF-8'); ?></span></td>
							<td style="vertical-align: middle; max-width: 200px;"><span style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars((string) ($r['siparis_ad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($r['siparis_ad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
							<td style="vertical-align: middle;"><?php echo htmlspecialchars(trim((string) ($r['siparis_il'] ?? '') . ' / ' . (string) ($r['siparis_ilce'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
							<td style="vertical-align: middle; white-space: nowrap;"><?php echo htmlspecialchars(panel_export_normalize_phone($r['siparis_tel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
							<td style="vertical-align: middle; text-align: right; font-weight: 600;"><?php echo number_format((float) ($r['siparis_fiyat'] ?? 0), 2, ',', '.'); ?> ₺</td>
							<td style="vertical-align: middle;">
								<a href="siparis-detay.php?siparis_id=<?php echo $sid; ?>" class="btn btn-xs btn-info" style="border-radius: 6px;"><i class="fa fa-external-link"></i></a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ($preview_total > $preview_limit): ?>
			<div style="padding: 12px 18px; background: #fff9e6; border-top: 1px solid #ffeaa7; font-size: 12px; color: #856404;">
				<i class="fa fa-info-circle"></i> Excel indirme <strong>tüm <?php echo (int) $preview_total; ?></strong> kaydı içerir; tabloda yalnızca ilk <?php echo (int) $preview_limit; ?> satır gösterilir.
			</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
	<?php else: ?>
	<div class="alert alert-info" style="border-radius: 10px; border: none; background: rgba(52, 152, 219, 0.1); color: #2980b9;">
		<i class="fa fa-hand-o-right"></i> Filtreleri seçip <strong>Filtrele / Önizle</strong> ile siparişleri listede görün; doğruladıktan sonra <strong>Excel indir</strong> kullanın.
	</div>
	<?php endif; ?>
</section>

<?php include 'footer.php'; ?>
