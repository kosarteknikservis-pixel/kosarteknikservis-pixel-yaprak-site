<?php
/**
 * PayTR / yasal zorunlu sayfalar — şema, varsayılan içerik, footer.
 */

if (!function_exists('legal_pages_ensure_schema')) {
	function legal_pages_ensure_schema(PDO $db) {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;

		$alters = array(
			"ALTER TABLE ayar ADD COLUMN ayar_firma_unvan VARCHAR(255) NOT NULL DEFAULT ''",
			"ALTER TABLE ayar ADD COLUMN ayar_firma_tel VARCHAR(64) NOT NULL DEFAULT ''",
			"ALTER TABLE ayar ADD COLUMN ayar_firma_adresi TEXT",
			"ALTER TABLE ayar ADD COLUMN ayar_firma_email VARCHAR(255) NOT NULL DEFAULT ''",
		);
		foreach ($alters as $sql) {
			try {
				$db->exec($sql);
			} catch (Throwable $e) {
			}
		}

		$tables = array(
			'teslimat_kosullari' => 'Teslimat Koşulları',
			'satis_politikasi'     => 'Satış Politikası',
			'iptal_iade'           => 'İptal ve İade Prosedürü',
		);
		foreach ($tables as $table => $defaultTitle) {
			try {
				$db->exec(
					"CREATE TABLE IF NOT EXISTS {$table} (
						id INT NOT NULL PRIMARY KEY DEFAULT 1,
						ad VARCHAR(255) NOT NULL DEFAULT '',
						icerik MEDIUMTEXT
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
				);
			} catch (Throwable $e) {
			}

			$chk = $db->query("SELECT id FROM {$table} WHERE id=1 LIMIT 1");
			if (!$chk || !$chk->fetch(PDO::FETCH_ASSOC)) {
				try {
					$ins = $db->prepare("INSERT INTO {$table} (id, ad, icerik) VALUES (1, :ad, :icerik)");
					$ins->execute(array(
						'ad'     => $defaultTitle,
						'icerik' => legal_pages_default_content($table, array()),
					));
				} catch (Throwable $e) {
				}
			}
		}

		foreach (array('gizlilik', 'sozlesme') as $legacy) {
			try {
				$chk = $db->query("SELECT id FROM {$legacy} WHERE id=1 LIMIT 1");
				if (!$chk || !$chk->fetch(PDO::FETCH_ASSOC)) {
					$db->exec("INSERT INTO {$legacy} (id, ad, icerik) VALUES (1, '', '')");
				}
			} catch (Throwable $e) {
			}
		}
	}
}

if (!function_exists('legal_pages_firma_info')) {
	function legal_pages_firma_info(array $settings, $whatsappprint = null) {
		$unvan = trim((string) ($settings['ayar_firma_unvan'] ?? ''));
		if ($unvan === '') {
			$unvan = trim((string) ($settings['ayar_title'] ?? ''));
		}

		$tel = trim((string) ($settings['ayar_firma_tel'] ?? ''));
		if ($tel === '' && is_array($whatsappprint)) {
			$tel = trim((string) ($whatsappprint['whats_tiklaara'] ?? ''));
			if ($tel === '') {
				$tel = trim((string) ($whatsappprint['whats_tel'] ?? ''));
			}
		}

		$adres = trim((string) ($settings['ayar_firma_adresi'] ?? ''));
		$email = trim((string) ($settings['ayar_firma_email'] ?? ''));

		return array(
			'unvan' => $unvan,
			'tel'   => $tel,
			'adres' => $adres,
			'email' => $email,
		);
	}
}

if (!function_exists('legal_pages_default_content')) {
	function legal_pages_default_content($table, array $firma) {
		$unvan = trim((string) ($firma['unvan'] ?? 'Firmamız'));
		$tel   = trim((string) ($firma['tel'] ?? ''));
		$adres = trim((string) ($firma['adres'] ?? ''));
		$email = trim((string) ($firma['email'] ?? ''));

		$iletisim = '<p><strong>İletişim</strong><br>';
		if ($unvan !== '') {
			$iletisim .= htmlspecialchars($unvan, ENT_QUOTES, 'UTF-8') . '<br>';
		}
		if ($tel !== '') {
			$iletisim .= 'Telefon: ' . htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') . '<br>';
		}
		if ($email !== '') {
			$iletisim .= 'E-posta: ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>';
		}
		if ($adres !== '') {
			$iletisim .= 'Adres: ' . nl2br(htmlspecialchars($adres, ENT_QUOTES, 'UTF-8')) . '<br>';
		}
		$iletisim .= '</p>';

		if ($table === 'teslimat_kosullari') {
			return $iletisim . '
<h3>Teslimat Süresi</h3>
<p>Siparişleriniz, ödeme/onay sonrası 1–3 iş günü içinde kargoya verilir. Yoğun dönemlerde süre kargo firmasına teslim süresine kadar 5 iş gününü bulabilir.</p>
<h3>Kargo ve Teslimat</h3>
<p>Ürünler anlaşmalı kargo firmaları ile Türkiye geneline gönderilir. Teslimat, müşterinin sipariş formunda belirttiği adrese yapılır.</p>
<h3>Teslimat Ücreti</h3>
<p>Kargo ücreti sipariş özetinde ayrıca belirtilir. Kampanya dönemlerinde ücretsiz kargo uygulanabilir.</p>
<h3>Teslim Alamama</h3>
<p>Kargo firmasının teslimat denemelerine rağmen ulaşılamayan siparişler iade sürecine tabidir. Lütfen telefon numaranızın doğru olduğundan emin olun.</p>';
		}

		if ($table === 'satis_politikasi') {
			return $iletisim . '
<h3>Genel</h3>
<p>' . htmlspecialchars($unvan, ENT_QUOTES, 'UTF-8') . ' internet sitesi üzerinden sunulan ürün ve hizmetler, ilgili mevzuata uygun şekilde satışa sunulmaktadır.</p>
<h3>Fiyatlar</h3>
<p>Sitede yer alan fiyatlar KDV dahil veya hariç olarak ürün sayfasında belirtilir. Fiyatlar önceden haber verilmeksizin güncellenebilir; sipariş anındaki fiyat geçerlidir.</p>
<h3>Stok ve Ürün Bilgisi</h3>
<p>Ürün görselleri temsilidir. Stok tükenmesi halinde müşteri bilgilendirilir; alternatif ürün veya iade seçenekleri sunulur.</p>
<h3>Ödeme</h3>
<p>Kapıda ödeme ve/veya güvenli online ödeme yöntemleri kullanılabilir. Online ödemeler 256-bit SSL ve 3D Secure altyapısı ile korunur.</p>';
		}

		if ($table === 'iptal_iade') {
			return $iletisim . '
<h3>Cayma Hakkı</h3>
<p>Mesafeli satışlarda tüketici, ürünü teslim aldığı tarihten itibaren 14 gün içinde cayma hakkını kullanabilir (Cayma hakkının kullanılamayacağı ürünler yasal istisnalara tabidir).</p>
<h3>İade Koşulları</h3>
<p>İade edilecek ürün kullanılmamış, orijinal ambalajında ve tekrar satılabilir durumda olmalıdır. Hasarlı veya eksik ürünlerde kargo hasar tutanağı gerekebilir.</p>
<h3>İade Süreci</h3>
<ol>
<li>Müşteri hizmetlerimizle telefon veya e-posta ile iletişime geçin.</li>
<li>İade onayı sonrası ürünü belirtilen adrese gönderin.</li>
<li>Ürün depomuza ulaştığında kontrol edilir; uygun bulunursa ödeme iadesi 14 gün içinde yapılır.</li>
</ol>
<h3>İptal</h3>
<p>Kargoya verilmemiş siparişler telefon veya e-posta ile iptal edilebilir. Kargoya verilen siparişlerde iade prosedürü uygulanır.</p>';
		}

		return $iletisim . '<p>İçerik panelden düzenlenebilir.</p>';
	}
}

if (!function_exists('legal_pages_public_map')) {
	function legal_pages_public_map() {
		return array(
			'teslimat' => array('table' => 'teslimat_kosullari', 'slug' => 'teslimat-kosullari', 'label' => 'Teslimat Koşulları'),
			'satis'    => array('table' => 'satis_politikasi', 'slug' => 'satis-politikasi', 'label' => 'Satış Politikası'),
			'iptal'    => array('table' => 'iptal_iade', 'slug' => 'iptal-iade', 'label' => 'İptal ve İade'),
			'sozlesme' => array('table' => 'sozlesme', 'slug' => 'sozlesme', 'label' => 'Mesafeli Satış Sözleşmesi'),
			'gizlilik' => array('table' => 'gizlilik', 'slug' => 'gizlilik', 'label' => 'Gizlilik Politikası'),
		);
	}
}

if (!function_exists('legal_pages_fetch_row')) {
	function legal_pages_fetch_row(PDO $db, $table) {
		$allowed = array('teslimat_kosullari', 'satis_politikasi', 'iptal_iade', 'sozlesme', 'gizlilik');
		if (!in_array($table, $allowed, true)) {
			return null;
		}
		legal_pages_ensure_schema($db);
		$q = $db->prepare("SELECT * FROM {$table} WHERE id=1 LIMIT 1");
		$q->execute();
		$row = $q->fetch(PDO::FETCH_ASSOC);
		return is_array($row) ? $row : null;
	}
}

if (!function_exists('legal_pages_site_base')) {
	function legal_pages_site_base(array $settings) {
		if (defined('SITE_URL') && SITE_URL !== '') {
			return rtrim(SITE_URL, '/');
		}
		return rtrim((string) ($settings['ayar_siteurl'] ?? ''), '/');
	}
}

if (!function_exists('legal_pages_render_footer')) {
	function legal_pages_render_footer(PDO $db, array $settings, $whatsappprint = null) {
		legal_pages_ensure_schema($db);
		$firma = legal_pages_firma_info($settings, $whatsappprint);
		$base  = legal_pages_site_base($settings);
		if ($base === '') {
			return;
		}

		$links = legal_pages_public_map();
		?>
<footer class="site-legal-footer" style="background:#1e293b;color:#94a3b8;padding:32px 0 24px;font-size:0.9rem;">
	<div class="container" style="max-width:960px;margin:0 auto;padding:0 16px;">
		<div style="background:#0f172a;border:1px solid #334155;border-radius:12px;padding:18px 20px;margin-bottom:20px;text-align:left;">
			<div style="color:#e2e8f0;font-weight:700;font-size:1rem;margin-bottom:10px;">
				<i class="fa fa-building-o" style="margin-right:6px;color:#22c55e;"></i> İletişim Bilgileri
			</div>
			<?php if ($firma['unvan'] !== '') { ?>
				<div style="margin-bottom:6px;"><strong style="color:#cbd5e1;">Unvan:</strong> <?php echo htmlspecialchars($firma['unvan'], ENT_QUOTES, 'UTF-8'); ?></div>
			<?php } ?>
			<?php if ($firma['tel'] !== '') { ?>
				<div style="margin-bottom:6px;"><strong style="color:#cbd5e1;">Telefon:</strong> <a href="tel:<?php echo htmlspecialchars(preg_replace('/\s+/', '', $firma['tel']), ENT_QUOTES, 'UTF-8'); ?>" style="color:#86efac;text-decoration:none;"><?php echo htmlspecialchars($firma['tel'], ENT_QUOTES, 'UTF-8'); ?></a></div>
			<?php } ?>
			<?php if ($firma['email'] !== '') { ?>
				<div style="margin-bottom:6px;"><strong style="color:#cbd5e1;">E-posta:</strong> <a href="mailto:<?php echo htmlspecialchars($firma['email'], ENT_QUOTES, 'UTF-8'); ?>" style="color:#86efac;text-decoration:none;"><?php echo htmlspecialchars($firma['email'], ENT_QUOTES, 'UTF-8'); ?></a></div>
			<?php } ?>
			<?php if ($firma['adres'] !== '') { ?>
				<div><strong style="color:#cbd5e1;">Adres:</strong> <?php echo nl2br(htmlspecialchars($firma['adres'], ENT_QUOTES, 'UTF-8')); ?></div>
			<?php } ?>
			<?php if ($firma['tel'] === '' && $firma['adres'] === '') { ?>
				<div style="color:#64748b;font-size:0.85rem;">Panel → Yasal Sayfalar (PayTR) bölümünden telefon ve adres bilgilerinizi girin.</div>
			<?php } ?>
		</div>

		<nav style="display:flex;flex-wrap:wrap;gap:8px 14px;justify-content:center;margin-bottom:18px;line-height:1.5;">
			<?php foreach ($links as $item) { ?>
				<a href="<?php echo htmlspecialchars($base . '/' . $item['slug'], ENT_QUOTES, 'UTF-8'); ?>" style="color:#cbd5e1;text-decoration:none;font-weight:600;font-size:0.88rem;" onmouseover="this.style.color='#86efac'" onmouseout="this.style.color='#cbd5e1'"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
			<?php } ?>
		</nav>

		<div style="text-align:center;margin-bottom:12px;">
			<span style="color:#cbd5e1;font-size:14px;font-weight:500;">
				<i class="fa fa-lock" style="color:#22c55e;margin-right:5px;"></i>
				256-Bit SSL ve güvenli ödeme altyapısı ile korunmaktadır.
			</span>
		</div>
		<div style="text-align:center;font-size:0.85rem;color:#64748b;">
			&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($firma['unvan'] !== '' ? $firma['unvan'] : 'Tüm hakları saklıdır', ENT_QUOTES, 'UTF-8'); ?>.
		</div>
	</div>
</footer>
		<?php
	}
}

if (!function_exists('legal_pages_render_public_shell')) {
	function legal_pages_render_public_shell($table, $defaultTitle) {
		global $db, $settingsprint, $whatsappprint;

		legal_pages_ensure_schema($db);
		$row = legal_pages_fetch_row($db, $table);
		$pageTitle = trim((string) ($row['ad'] ?? ''));
		if ($pageTitle === '') {
			$pageTitle = $defaultTitle;
		}
		$pageBody = $row['icerik'] ?? '';
		if (trim(strip_tags((string) $pageBody)) === '') {
			$pageBody = legal_pages_default_content($table, legal_pages_firma_info($settingsprint ?? array(), $whatsappprint ?? null));
		}

		$patternUrl = rtrim(SITE_URL, '/') . '/xnull/assets/img/genel/pattern10.png';
		$siteBase   = legal_pages_site_base($settingsprint ?? array());
		?>
<section id="page-title" class="page-title-classic" style="background:url(<?php echo htmlspecialchars($patternUrl, ENT_QUOTES, 'UTF-8'); ?>)">
	<div class="container">
		<div class="text-center">
			<h1 style="font-size:1.6rem;"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
		</div>
	</div>
</section>
<section style="background:#fff;padding:30px 0 10px;">
	<div class="container" style="max-width:860px;">
		<div class="legal-page-content" style="line-height:1.7;color:#334155;font-size:15px;">
			<?php echo $pageBody; ?>
		</div>
		<p style="margin:28px 0 10px;">
			<a href="<?php echo htmlspecialchars($siteBase . '/', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" style="border-radius:8px;">Siteye Dön</a>
		</p>
	</div>
</section>
		<?php
	}
}
