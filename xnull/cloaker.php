<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Dinamik URL Tespiti
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$detect_path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
$detect_path = rtrim($detect_path, '/');
$dynamic_site_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $detect_path . "/";
?>
<section class="main-content container">
    <div class="page-header" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <h2 style="margin:0;">Cloaker (Bot Koruması) <span class="label label-primary">v2.1</span></h2>
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#cloakerKilavuzModal"><i class="fa fa-book"></i> Kullanım kılavuzu</button>
    </div>
    <?php
    if ( isset( $_GET['status'] ) && $_GET['status'] === 'ok' ) {
        echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Cloaker ayarları kaydedildi.</div>';
    } elseif ( isset( $_GET['status'] ) && $_GET['status'] === 'no' ) {
        echo '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Ayarlar kaydedilemedi.</div>';
    }
    ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <i class="fa fa-shield"></i> CLOAKER PANELİ VE HASSAS AYARLAR
                    <a href="cloaker-traffic.php" class="btn btn-xs btn-primary pull-right" style="margin-top: -3px; margin-right: 6px;">
                        <i class="fa fa-list-alt"></i> Trafik günlüğü
                    </a>
                    <a href="cloaker/verify_system.php" target="_blank" class="btn btn-xs btn-info pull-right" style="margin-top: -3px; margin-right: 6px;">
                        <i class="fa fa-vial"></i> Simülatör (Hızlı Test)
                    </a>
                    <button type="button" class="btn btn-xs btn-default pull-right" style="margin-top: -3px; margin-right: 6px;" data-toggle="modal" data-target="#cloakerKilavuzModal"><i class="fa fa-book"></i> Kılavuz</button>
                </div>
                <div class="card-block">
                    
                    <!-- ANALİTİK DASHBOARD -->
                    <div class="row" style="margin-bottom: 20px;">
                        <?php 
                            $stat_total = $settingsprint['ayar_cloaker_stat_total'] ?? 0;
                            $stat_passed = $settingsprint['ayar_cloaker_stat_passed'] ?? 0;
                            $stat_blocked = $settingsprint['ayar_cloaker_stat_blocked'] ?? 0;
                        ?>
                        <div class="col-md-4">
                            <div class="widget bg-primary p-lg text-center">
                                <div class="m-b-md">
                                    <i class="fa fa-users fa-3x"></i>
                                    <h1 class="m-xs"><?php echo $stat_total; ?></h1>
                                    <h3 class="font-bold no-margins">Toplam Trafik</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="widget bg-success p-lg text-center">
                                <div class="m-b-md">
                                    <i class="fa fa-check-circle fa-3x"></i>
                                    <h1 class="m-xs"><?php echo $stat_passed; ?></h1>
                                    <h3 class="font-bold no-margins">İzin Verilen</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="widget bg-danger p-lg text-center">
                                <div class="m-b-md">
                                    <i class="fa fa-ban fa-3x"></i>
                                    <h1 class="m-xs"><?php echo $stat_blocked; ?></h1>
                                    <h3 class="font-bold no-margins">Engellenen</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 text-right" style="margin-top: 10px;">
                            <a href="cloaker/update_ips.php" class="btn btn-sm btn-info" onclick="return confirm('IP Listesi Facebook/Google kaynaklarından güncellenecek. Emin misiniz?')"><i class="fa fa-refresh"></i> IP Listesini Güncelle</a>
                            <form method="POST" action="controller/function.php" style="display:inline;" onsubmit="return confirm('Tüm istatistikleri sıfırlamak istediğinize emin misiniz?');">
                                <button type="submit" name="cloakersifirla" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i> İstatistikleri Sıfırla</button>
                            </form>
                        </div>
                    </div>
                    <hr>

                    <!-- LİNK OLUŞTURUCU -->
                    <?php if (!empty($settingsprint['ayar_cloaker_key'])) { ?>
                    <div class="alert alert-success">
                        <strong><i class="fa fa-link"></i> Reklam Linkiniz Hazır:</strong><br>
                        <small class="text-muted">Meta / Google reklamlarında <strong>nihai URL</strong> mutlaka bu adres olmalı (sadece ana alan adı değil). <code>fbclid</code> tek başına ref yerine geçmez; oturum çerezi ilk yüklemede <code>ref</code> ile oluşur.</small><br><br>
                        <div class="input-group">
                            <input type="text" id="refLink" class="form-control input-lg" style="font-weight: bold; color: #1c84c6;" readonly value="<?php echo $dynamic_site_url . '?ref=' . htmlspecialchars($settingsprint['ayar_cloaker_key'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="input-group-btn">
                                <button class="btn btn-primary btn-lg" type="button" onclick="copyLink()"><i class="fa fa-copy"></i> Kopyala</button>
                            </span>
                        </div>
                    </div>
                    <?php } ?>
                    
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="m-t-none">Temel Ayarlar</h4>
                                <div class="form-group">
                                    <label>Cloaker Durumu</label>
                                    <select name="ayar_cloaker_on" class="form-control">
                                        <option value="1" <?php echo (isset($settingsprint['ayar_cloaker_on']) && $settingsprint['ayar_cloaker_on'] == 1) ? 'selected' : ''; ?>>Aktif (Korumayı Aç)</option>
                                        <option value="0" <?php echo (!isset($settingsprint['ayar_cloaker_on']) || $settingsprint['ayar_cloaker_on'] == 0) ? 'selected' : ''; ?>>Pasif (Korumayı Kapat)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Yönlendirme Metodu</label>
                                    <select name="ayar_cloaker_method" class="form-control">
                                        <option value="2" <?php echo (isset($settingsprint['ayar_cloaker_method']) && $settingsprint['ayar_cloaker_method'] == 2) ? 'selected' : ''; ?>>Shadow Modu (Tavsiye Edilen - URL Değişmez)</option>
                                        <option value="1" <?php echo (isset($settingsprint['ayar_cloaker_method']) && $settingsprint['ayar_cloaker_method'] == 1) ? 'selected' : ''; ?>>301 Redirect Modu (URL Değişir)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Güvenli İçerik / Sayfa (Botlar için)</label>
                                    <input type="text" name="ayar_safe_page" value="<?php echo isset($settingsprint['ayar_safe_page']) ? $settingsprint['ayar_safe_page'] : 'safe-page.php'; ?>" class="form-control" placeholder="Örn: safe-page.php">
                                </div>

                                <div class="form-group">
                                    <label>Özel Giriş Anahtarı (Ref Key)</label>
                                    <input type="text" name="ayar_cloaker_key" value="<?php echo isset($settingsprint['ayar_cloaker_key']) ? $settingsprint['ayar_cloaker_key'] : ''; ?>" class="form-control" placeholder="Örn: kampanya">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h4 class="m-t-none text-danger">Hassas Koruma Modları</h4>
                                
                                <div class="form-group">
                                    <label>DNS Kontrol Hassasiyeti</label>
                                    <select name="ayar_cloaker_dns_mode" class="form-control">
                                        <option value="esnek" <?php echo (@$settingsprint['ayar_cloaker_dns_mode'] == 'esnek') ? 'selected' : ''; ?>>Esnek Mod (Yavaşlıkta İzin Ver)</option>
                                        <option value="agresif" <?php echo (@$settingsprint['ayar_cloaker_dns_mode'] == 'agresif') ? 'selected' : ''; ?>>Agresif Mod (Şüphede Engelle)</option>
                                    </select>
                                    <small class="text-muted">DNS sorgusu takılırsa gerçek müşteriyi mi kaçıralım, botun geçmesine mi izin verelim?</small>
                                </div>

                                <div class="form-group">
                                    <label>JS / Headless Bot Kontrolü</label>
                                    <select name="ayar_cloaker_js_mode" class="form-control">
                                        <option value="esnek" <?php echo (@$settingsprint['ayar_cloaker_js_mode'] == 'esnek') ? 'selected' : ''; ?>>Esnek Mod (Sadece İzle)</option>
                                        <option value="agresif" <?php echo (@$settingsprint['ayar_cloaker_js_mode'] == 'agresif') ? 'selected' : ''; ?>>Agresif Mod (Token Yoksa Engelle)</option>
                                    </select>
                                    <small class="text-muted">Not: Bu seçenek şu an veritabanında saklanır; <code>core.php</code> içinde henüz uygulanmıyor (gelecek sürüm). Şu an sadece <code>_cl_tk</code> çerezi yazılır.</small>
                                </div>

                                <hr>
                                <h4 class="m-t-none">Bölge (GeoIP) Kilidi</h4>
                                <div class="form-group">
                                    <label>GeoIP Durumu</label>
                                    <select name="ayar_cloaker_geoip_on" class="form-control">
                                        <option value="1" <?php echo (@$settingsprint['ayar_cloaker_geoip_on'] == 1) ? 'selected' : ''; ?>>Aktif (Sadece Belirli Ülkeler)</option>
                                        <option value="0" <?php echo (@$settingsprint['ayar_cloaker_geoip_on'] == 0) ? 'selected' : ''; ?>>Pasif (Tüm Dünyaya Açık)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>İzin Verilen Ülkeler (ISO Kodları)</label>
                                    <input type="text" name="ayar_cloaker_allowed_countries" value="<?php echo isset($settingsprint['ayar_cloaker_allowed_countries']) ? $settingsprint['ayar_cloaker_allowed_countries'] : 'TR'; ?>" class="form-control" placeholder="Örn: TR,DE,US">
                                    <small class="text-muted">Virgül ile ayırın. Sadece bu ülkeler gerçek siteyi görür.</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>IP Kara Liste (Manuel)</label>
                                    <textarea name="ayar_cloaker_blacklist" class="form-control" rows="2" placeholder="Satır satır IP veya CIDR (örn: 1.2.3.4 veya 10.0.0.0/24)..."><?php echo isset($settingsprint['ayar_cloaker_blacklist']) ? htmlspecialchars($settingsprint['ayar_cloaker_blacklist'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                                    <small class="text-muted">Bu liste artık <code>cloaker/blocked_ips.txt</code> ile birlikte uygulanır.</small>
                                </div>
                                <div class="form-group">
                                    <label>IP Beyaz Liste (Whitelist)</label>
                                    <textarea name="ayar_cloaker_whitelist" class="form-control" rows="2" placeholder="Asla engellenmeyecek IP'ler..."><?php echo isset($settingsprint['ayar_cloaker_whitelist']) ? $settingsprint['ayar_cloaker_whitelist'] : ''; ?></textarea>
                                </div>
                                <button type="submit" name="cloakerduzenle" class="btn btn-primary btn-lg btn-block"><i class="fa fa-save"></i> Tüm Ayarları ve Güvenlik Modlarını Kaydet</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Kullanım kılavuzu (popup) -->
<div class="modal fade" id="cloakerKilavuzModal" tabindex="-1" role="dialog" aria-labelledby="cloakerKilavuzTitle">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 720px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="cloakerKilavuzTitle"><i class="fa fa-book text-info"></i> Cloaker kullanım kılavuzu</h4>
            </div>
            <div class="modal-body" style="max-height: calc(100vh - 180px); overflow-y: auto; font-size: 14px; line-height: 1.55;">
                <p class="text-muted" style="margin-top:0;">Bu modül, reklam botlarını ve inceleme araçlarını <strong>güvenli sayfa</strong>ya yönlendirir; gerçek ziyaretçiye normal vitrin açılır. Aşağıdaki adımlar reklam hesabıyla uyumludur.</p>

                <h5 style="font-weight:700;margin-top:18px;">1) Reklamda kullanılacak URL (çok önemli)</h5>
                <ul>
                    <li>Meta / Google’da <strong>nihai URL</strong> olarak panelde üretilen adresi kullanın: <code>?ref=SİZİN_ANAHTAR</code> ile bitmeli.</li>
                    <li><code>fbclid</code>, <code>gclid</code> vb. tek başına “giriş” sayılmaz; ilk yüklemede <code>ref</code> oturuma işlenir, sonraki tıklamalarda aynı oturumda <code>ref</code> tekrar gerekmez.</li>
                    <li>Ana alan adını tek başına vermek, anahtarlı linki vermekten farklıdır — cloaker açıkken mutlaka anahtarlı linki kullanın.</li>
                </ul>

                <h5 style="font-weight:700;margin-top:18px;">2) Shadow mod vs 301</h5>
                <ul>
                    <li><strong>Shadow (önerilen):</strong> Adres çubuğu değişmez; botlara güvenli sayfa içeriği gösterilir.</li>
                    <li><strong>301:</strong> Bot tarayıcı başka URL’ye yönlendirilir (daha agresif, bazı senaryolarda dikkat gerektirir).</li>
                </ul>

                <h5 style="font-weight:700;margin-top:18px;">3) Güvenli sayfa</h5>
                <p>Botların gördüğü içerik varsayılan olarak <code>safe-page.php</code> (sunucuda <code>panel1/</code> kökünde). İsterseniz ayarlardan dosya adını değiştirebilirsiniz; dosya gerçekten o yolda olmalıdır.</p>

                <h5 style="font-weight:700;margin-top:18px;">4) GeoIP (ülke kilidi)</h5>
                <ul>
                    <li>Açıksa sadece yazdığınız ISO ülkeler (örn. <code>TR</code>) gerçek siteyi görür.</li>
                    <li>Veritabanı: <code>js/ajax/libs/GeoLite2-City.mmdb</code> ve <code>geoip2.php</code> gerekir; yoksa ayara göre esnek/agresif davranış uygulanır.</li>
                </ul>

                <h5 style="font-weight:700;margin-top:18px;">5) Kara / beyaz liste</h5>
                <ul>
                    <li><strong>Beyaz liste:</strong> Kendi ofis IP’nizi ekleyerek test edebilirsiniz (CIDR desteklenir).</li>
                    <li><strong>Kara liste:</strong> Panel metni + <code>cloaker/blocked_ips.txt</code> birlikte uygulanır. Şüpheli IP’leri <a href="cloaker-traffic.php" target="_blank">trafik günlüğünden</a> tek tıkla ekleyebilirsiniz.</li>
                </ul>
                <p style="font-size:13px;color:#555;"><strong>IP listesini güncelle</strong> şunları çeker: Google (<code>gstatic</code> <code>goog.json</code>), AWS resmi <code>ip-ranges.json</code>, RIPE Stat ile duyurulan IPv4 önekleri — Meta <strong>AS32934</strong> (dokümantasyondaki origin ASN), TikTok/ByteDance <strong>AS396986</strong> ve <strong>AS138699</strong>. Eski Facebook <code>facebook_ips.txt</code> adresi artık yok; Cloudflare listesi müşteri trafiği riski nedeniyle kaldırıldı. TikTok’un tek tek “crawler IP” metin dosyası yayımlanmıyor; ASN listesi geniştir, gerekirse beyaz liste kullanın.</p>

                <h5 style="font-weight:700;margin-top:18px;">6) Trafik günlüğü &amp; simülatör</h5>
                <ul>
                    <li><a href="cloaker-traffic.php" target="_blank">Cloaker trafik günlüğü</a> Meta / Google ayrımı ve engel nedeni gösterir.</li>
                    <li><a href="cloaker/verify_system.php" target="_blank">Simülatör</a> ile farklı User-Agent senaryolarını hızlıca deneyebilirsiniz.</li>
                </ul>

                <h5 style="font-weight:700;margin-top:18px;">7) Bilinen sınırlamalar</h5>
                <ul>
                    <li><strong>JS / headless modu</strong> panelde saklanır; şu an sadece <code>_cl_tk</code> çerezi yazılır — agresif token zorunluluğu henüz tam uygulanmıyor (formda not vardır).</li>
                    <li>DNS ters çözümlemesi bazı mobil / hosting çıkışlarında “şüpheli host” tetikleyebilir; test için IP’nizi beyaz listeye alın.</li>
                    <li>IPv6 ve bazı CDN çıkışları CIDR kontrolünde sınırlı kalabilir; sorun yaşarsanız beyaz liste kullanın.</li>
                </ul>

                <h5 style="font-weight:700;margin-top:18px;">8) Reklam önizlemesinde görseller</h5>
                <p>Meta önizlemesi çoğu zaman <strong>bot User-Agent</strong> ile sayfayı çeker → siz <strong>güvenli sayfayı</strong> görürsünüz; ürün görselleri bu yüzden farklı görünebilir. Gerçek müşteri, doğru <code>ref</code> ile girdiğinde vitrin yüklenir.</p>
                <p>Vitrinde ürün görselleri için <code>loading="lazy"</code> + sürekli <code>?v=time()</code> önbelleği kıran kullanım, mobil uygulama içi tarayıcılarda eksik yükleme yapabiliyordu; güncellemede ilk ürün görseli önceliklendirildi ve önbellek anahtarı dosya tarihine bağlandı. WhatsApp / Instagram uygulama içi UA’ları da genel “bot” listesinden çıkarıldı.</p>

                <div class="alert alert-warning" style="margin-bottom:0;font-size:13px;">
                    <strong>Yasal uyarı:</strong> Cloaker’ı yanıltıcı reklam politikalarına aykırı şekilde kullanmak platform hesabı riski doğurur. Yerel hukuk ve Meta / Google kurallarına uygun hareket edin.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink() {
  var copyText = document.getElementById("refLink");
  copyText.select();
  copyText.setSelectionRange(0, 99999);
  document.execCommand("copy");
  swal("Kopyalandı!", "Reklam linkiniz panoya kopyalandı.", "success");
}
</script>

<?php include 'footer.php'; ?>
