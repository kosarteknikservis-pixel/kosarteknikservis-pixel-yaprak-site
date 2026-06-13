<?php
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

$ayar_bl = $db->prepare( 'SELECT ayar_cloaker_blacklist FROM ayar WHERE ayar_id=0 LIMIT 1' );
$ayar_bl->execute();
$bl_row   = $ayar_bl->fetch( PDO::FETCH_ASSOC );
$bl_text  = isset( $bl_row[ 'ayar_cloaker_blacklist' ] ) ? (string) $bl_row[ 'ayar_cloaker_blacklist' ] : '';
$bl_ips   = array();
foreach ( preg_split( '/\r\n|\r|\n/', $bl_text ) as $ln ) {
	$ln = trim( $ln );
	if ( $ln !== '' && ( ! isset( $ln[0] ) || $ln[0] !== '#' ) ) {
		$bl_ips[ $ln ] = true;
	}
}

$vendor_f  = isset( $_GET['vendor'] ) ? trim( (string) $_GET['vendor'] ) : '';
$blocked_f = isset( $_GET['blocked'] ) ? trim( (string) $_GET['blocked'] ) : '';

$keep_params = array();
if ( $vendor_f !== '' ) {
	$keep_params['vendor'] = $vendor_f;
}
if ( $blocked_f !== '' ) {
	$keep_params['blocked'] = $blocked_f;
}
$cloaker_redirect_qs = http_build_query( $keep_params );

$where  = array();
$params = array();
if ( $vendor_f === 'meta' ) {
	$where[] = 'vendor_label = :v';
	$params['v'] = 'meta';
} elseif ( $vendor_f === 'google' ) {
	$where[] = 'vendor_label = :v';
	$params['v'] = 'google';
}
if ( $blocked_f === '1' ) {
	$where[] = 'blocked = 1';
} elseif ( $blocked_f === '0' ) {
	$where[] = 'blocked = 0';
}

$sql = 'SELECT id, ip, user_agent, vendor_label, blocked, reason, request_uri, created_at FROM cloaker_traffic_log';
if ( $where ) {
	$sql .= ' WHERE ' . implode( ' AND ', $where );
}
$sql .= ' ORDER BY id DESC';

$rows         = array();
$table_hatasi = '';
$log_toplam   = 0;
try {
	$st = $db->prepare( $sql );
	$st->execute( $params );
	$rows = $st->fetchAll( PDO::FETCH_ASSOC );
	$log_toplam = (int) $db->query( 'SELECT COUNT(*) FROM cloaker_traffic_log' )->fetchColumn();
} catch ( Exception $e ) {
	$table_hatasi = 'Günlük tablosu henüz oluşmadı veya okunamadı. Cloaker açıkken siteye bir ziyaret gelince tablo otomatik oluşur.';
}
?>
<section class="main-content container">
	<div class="page-header">
		<h2>Cloaker trafik günlüğü</h2>
	</div>

	<?php if ( isset( $_GET['durum'] ) && $_GET['durum'] === 'eklendi' ) { ?>
		<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>IP panel kara listesine eklendi (Cloaker ayarlarındaki manuel liste).</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'zaten_var' ) { ?>
		<div class="alert alert-info alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Bu IP zaten kara listede.</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'gecersiz_ip' ) { ?>
		<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Geçersiz IP.</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'no' ) { ?>
		<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Kaydedilemedi.</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'silindi' ) { ?>
		<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?php echo (int) ( $_GET['silinen'] ?? 0 ); ?> kayıt silindi.</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'silme_bos' ) { ?>
		<div class="alert alert-warning alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Silinecek satır seçilmedi.</div>
	<?php } elseif ( isset( $_GET['durum'] ) && $_GET['durum'] === 'tum_silindi' ) { ?>
		<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Tüm günlük temizlendi (<?php echo (int) ( $_GET['silinen'] ?? 0 ); ?> satır).</div>
	<?php } ?>

	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					Gelen istekler (Cloaker açıkken vitrin sayfaları)
					<a href="cloaker.php" class="btn btn-xs btn-default pull-right"><i class="fa fa-cog"></i> Cloaker ayarları</a>
				</div>
				<div class="card-block">
					<p class="text-muted small">Kayıtlar <strong>üst sınır olmadan</strong> saklanır; silmek için bu sayfadaki toplu sil / tümünü sil kullanın. Çok yüksek kayıt sayısında liste yüklenmesi yavaşlayabilir. <strong>Meta</strong> etiketi user-agent tahminidir.</p>
					<?php if ( $table_hatasi === '' ) { ?>
					<p class="text-muted small">Toplam kayıt: <strong><?php echo $log_toplam; ?></strong></p>
					<?php } ?>
					<p style="margin-bottom:15px;">
						<strong>Filtre:</strong>
						<a href="cloaker-traffic.php" class="btn btn-xs btn-default<?php echo ( $vendor_f === '' && $blocked_f === '' ) ? ' active' : ''; ?>">Tümü</a>
						<a href="cloaker-traffic.php?vendor=meta" class="btn btn-xs btn-primary<?php echo ( $vendor_f === 'meta' ) ? ' active' : ''; ?>">Sadece Meta</a>
						<a href="cloaker-traffic.php?vendor=google" class="btn btn-xs btn-info<?php echo ( $vendor_f === 'google' ) ? ' active' : ''; ?>">Sadece Google</a>
						<a href="cloaker-traffic.php?blocked=1" class="btn btn-xs btn-warning<?php echo ( $blocked_f === '1' ) ? ' active' : ''; ?>">Engellenen</a>
						<a href="cloaker-traffic.php?blocked=0" class="btn btn-xs btn-success<?php echo ( $blocked_f === '0' ) ? ' active' : ''; ?>">Geçen</a>
					</p>

					<?php if ( $table_hatasi !== '' ) { ?>
						<div class="alert alert-warning"><?php echo htmlspecialchars( $table_hatasi ); ?></div>
					<?php } else { ?>
					<div class="clearfix" style="margin-bottom:12px;">
						<form id="frm_cloaker_toplu_sil" method="post" action="controller/function.php" class="pull-left" style="margin-right:10px;" onsubmit="return cloakerTopluSilPrepare(this);">
							<input type="hidden" name="cloaker_redirect_qs" value="<?php echo htmlspecialchars( $cloaker_redirect_qs, ENT_QUOTES, 'UTF-8' ); ?>">
							<div id="cloaker_dyn_log_ids"></div>
							<button type="submit" name="cloaker_traffic_toplu_sil" value="1" class="btn btn-sm btn-warning"><i class="fa fa-trash"></i> Seçilenleri sil</button>
							<label class="checkbox-inline" style="margin-left:10px;"><input type="checkbox" id="cloaker-tumunu-sec"> Tablodaki mevcut sayfa (DataTables)</label>
						</form>
						<form method="post" action="controller/function.php" class="pull-left" onsubmit="return confirm('TÜM cloaker trafik günlüğü silinecek. Emin misiniz?');">
							<input type="hidden" name="cloaker_redirect_qs" value="<?php echo htmlspecialchars( $cloaker_redirect_qs, ENT_QUOTES, 'UTF-8' ); ?>">
							<button type="submit" name="cloaker_traffic_tumunu_sil" value="1" class="btn btn-sm btn-danger"><i class="fa fa-times-circle"></i> Tümünü sil</button>
						</form>
					</div>
					<div class="table-responsive">
						<table id="datatable_cloaker_log" class="mobile-table table table-striped table-hover table-bordered">
							<thead>
								<tr>
									<th class="text-center" style="width:40px;"><input type="checkbox" id="cloaker-tumunu-sec-head" title="Bu sayfadaki tümünü seç"></th>
									<th>Tarih</th>
									<th>IP</th>
									<th class="text-center">Tahmin</th>
									<th class="text-center">Durum</th>
									<th>Sebep</th>
									<th>User-Agent</th>
									<th class="text-center">İşlem</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $r ) {
									$ip = $r['ip'];
									$zaten = isset( $bl_ips[ $ip ] );
									$vl = $r['vendor_label'];
									$rid = (int) $r['id'];
									?>
								<tr>
									<td data-label="Seç" class="text-center">
										<input type="checkbox" class="cloaker-log-cb-vis" data-log-id="<?php echo $rid; ?>" value="1">
									</td>
									<td data-label="Tarih"><?php echo htmlspecialchars( $r['created_at'] ); ?></td>
									<td data-label="IP"><code><?php echo htmlspecialchars( $ip ); ?></code></td>
									<td data-label="Tahmin" class="text-center">
										<?php
										if ( $vl === 'meta' ) {
											echo '<span class="label label-primary">Meta</span>';
										} elseif ( $vl === 'google' ) {
											echo '<span class="label label-info">Google</span>';
										} elseif ( $vl === 'microsoft' ) {
											echo '<span class="label label-default">Microsoft</span>';
										} else {
											echo '<span class="text-muted">—</span>';
										}
										?>
									</td>
									<td data-label="Durum" class="text-center">
										<?php if ( (int) $r['blocked'] === 1 ) { ?>
											<span class="label label-danger">Engellendi</span>
										<?php } else { ?>
											<span class="label label-success">Geçti</span>
										<?php } ?>
									</td>
									<td data-label="Sebep"><small><?php echo htmlspecialchars( $r['reason'] ); ?></small></td>
									<td data-label="UA"><small title="<?php echo htmlspecialchars( $r['user_agent'] ); ?>"><?php
										$ua_show = (string) $r['user_agent'];
										if ( function_exists( 'mb_strimwidth' ) ) {
											$ua_show = mb_strimwidth( $ua_show, 0, 90, '…', 'UTF-8' );
										} elseif ( strlen( $ua_show ) > 90 ) {
											$ua_show = substr( $ua_show, 0, 87 ) . '…';
										}
										echo htmlspecialchars( $ua_show );
									?></small></td>
									<td data-label="İşlem" class="text-center">
										<?php if ( $zaten ) { ?>
											<span class="text-muted small">Listede</span>
										<?php } elseif ( filter_var( $ip, FILTER_VALIDATE_IP ) ) { ?>
											<form method="post" action="controller/function.php" style="display:inline;" onsubmit="return confirm('Bu IP panel kara listesine eklensin mi?');">
												<input type="hidden" name="ip" value="<?php echo htmlspecialchars( $ip, ENT_QUOTES, 'UTF-8' ); ?>">
												<button type="submit" name="cloaker_traffic_ip_ekle" value="1" class="btn btn-xs btn-danger">Kara listeye ekle</button>
											</form>
										<?php } else { ?>
											<span class="text-muted">—</span>
										<?php } ?>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php include 'footer.php'; ?>
<?php if ( $table_hatasi === '' ) { ?>
<script>
function cloakerTopluSilPrepare(f) {
	var dyn = document.getElementById('cloaker_dyn_log_ids');
	dyn.innerHTML = '';
	var ids = [];
	document.querySelectorAll('.cloaker-log-cb-vis:checked').forEach(function(cb) {
		var id = cb.getAttribute('data-log-id');
		if (id) ids.push(id);
	});
	if (ids.length === 0) {
		alert('Lütfen silmek için en az bir satır işaretleyin.');
		return false;
	}
	if (!confirm(ids.length + ' kayıt silinecek. Emin misiniz?')) return false;
	ids.forEach(function(id) {
		var h = document.createElement('input');
		h.type = 'hidden';
		h.name = 'log_id[]';
		h.value = id;
		dyn.appendChild(h);
	});
	return true;
}
$(function() {
	var $tbl = $('#datatable_cloaker_log');
	function syncHeaderChecks(state) {
		$('#cloaker-tumunu-sec, #cloaker-tumunu-sec-head').prop('checked', state);
	}
	$('#cloaker-tumunu-sec, #cloaker-tumunu-sec-head').on('change', function() {
		var on = $(this).prop('checked');
		if ($.fn.DataTable.isDataTable($tbl)) {
			var dt = $tbl.DataTable();
			$(dt.rows({ page: 'current' }).nodes()).find('.cloaker-log-cb-vis').prop('checked', on);
		} else {
			$tbl.find('.cloaker-log-cb-vis').prop('checked', on);
		}
		syncHeaderChecks(on);
	});
	$tbl.on('change', '.cloaker-log-cb-vis', function() {
		syncHeaderChecks(false);
	});
	$tbl.on('page.dt', function() {
		syncHeaderChecks(false);
	});
});
</script>
<?php } ?>
