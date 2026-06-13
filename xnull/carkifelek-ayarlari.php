    <?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';

// Çarkıfelek ayarlarını çek (yoksa varsayılan değerler)
$carkifelek_ayar = $db->prepare("SELECT * FROM ayar WHERE ayar_id=0");
$carkifelek_ayar->execute();
$carkifelek_print = $carkifelek_ayar->fetch(PDO::FETCH_ASSOC);

// Varsayılan değerler
$carkifelek_on = isset($carkifelek_print['ayar_carkifelek_on']) ? $carkifelek_print['ayar_carkifelek_on'] : 0;
$carkifelek_baslik = isset($carkifelek_print['ayar_carkifelek_baslik']) ? $carkifelek_print['ayar_carkifelek_baslik'] : 'Çarkıfelek Çevir, İndirim Kazan!';
$carkifelek_aciklama = isset($carkifelek_print['ayar_carkifelek_aciklama']) ? $carkifelek_print['ayar_carkifelek_aciklama'] : 'Her gün bir kez çarkıfelek çevirerek indirim kazanabilirsiniz!';
$carkifelek_renk1 = isset($carkifelek_print['ayar_carkifelek_renk1']) ? $carkifelek_print['ayar_carkifelek_renk1'] : '#ff6b6b';
$carkifelek_renk2 = isset($carkifelek_print['ayar_carkifelek_renk2']) ? $carkifelek_print['ayar_carkifelek_renk2'] : '#ee5a6f';
$carkifelek_vurgu = isset($carkifelek_print['ayar_carkifelek_vurgu']) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', trim((string)$carkifelek_print['ayar_carkifelek_vurgu'])) ? trim($carkifelek_print['ayar_carkifelek_vurgu']) : '#fbbf24';
$carkifelek_oduller = isset($carkifelek_print['ayar_carkifelek_oduller']) ? $carkifelek_print['ayar_carkifelek_oduller'] : '["%10 İndirim","%15 İndirim","%20 İndirim","%25 İndirim","Ücretsiz Kargo","Tekrar Deneyin"]';
$carkifelek_auto_on = isset($carkifelek_print['ayar_carkifelek_auto_on']) ? $carkifelek_print['ayar_carkifelek_auto_on'] : 1;
$carkifelek_auto_sn = isset($carkifelek_print['ayar_carkifelek_auto_sn']) ? $carkifelek_print['ayar_carkifelek_auto_sn'] : 5;
$carkifelek_zorla_kargo = isset($carkifelek_print['ayar_carkifelek_zorla_ucretsiz_kargo']) ? (int) $carkifelek_print['ayar_carkifelek_zorla_ucretsiz_kargo'] : 0;
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Çarkıfelek Oyunu Ayarları</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    ÇARKIFELEK AYARLARI
                </div>
                <div class="card-block">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'ok') { ?>
                    <div class="alert alert-success">
                        <strong>Başarılı!</strong> Çarkıfelek ayarları güncellendi.
                    </div>
                    <?php } elseif (isset($_GET['status']) && $_GET['status'] == 'no') { ?>
                    <div class="alert alert-danger">
                        <strong>Hata!</strong> Ayarlar güncellenirken bir hata oluştu.
                    </div>
                    <?php } ?>
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <div class="form-group">
                            <label>Çarkıfelek Durumu</label>
                            <select name="carkifelek_durum" class="form-control">
                                <option value="1" <?php echo $carkifelek_on == 1 ? 'selected' : ''; ?>>Aktif</option>
                                <option value="0" <?php echo $carkifelek_on == 0 ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Otomatik Açılma (Pop-up)</label>
                            <select name="ayar_carkifelek_auto_on" class="form-control">
                                <option value="1" <?php echo $carkifelek_auto_on == 1 ? 'selected' : ''; ?>>Açık</option>
                                <option value="0" <?php echo $carkifelek_auto_on == 0 ? 'selected' : ''; ?>>Kapalı</option>
                            </select>
                            <small class="text-muted">Site açıldığında çarkın otomatik olarak çıkıp çıkmayacağını belirler.</small>
                        </div>

                        <div class="form-group">
                            <label>Kaç Saniye Sonra Çıksın?</label>
                            <input type="number" name="ayar_carkifelek_auto_sn" value="<?php echo (int)$carkifelek_auto_sn; ?>" class="form-control" placeholder="5" min="0" max="600" step="1">
                            <small class="text-muted">0 = limit kontrolünden hemen sonra (milisaniyelik gecikme), 1–2 = tam o kadar saniye. Sunucu limit kontrolü tamamlanana kadar kısa bir bekleme normaldir.</small>
                        </div>

                        <div class="form-group">
                            <label>Ücretsiz kargo — her zaman bu ödül</label>
                            <select name="ayar_carkifelek_zorla_ucretsiz_kargo" class="form-control">
                                <option value="0" <?php echo $carkifelek_zorla_kargo === 0 ? 'selected' : ''; ?>>Kapalı (rastgele çekiliş)</option>
                                <option value="1" <?php echo $carkifelek_zorla_kargo === 1 ? 'selected' : ''; ?>>Aktif</option>
                            </select>
                            <small class="text-muted"><strong>Aktif</strong> iken ödül listesinde tanınan bir ücretsiz kargo satırı varsa (ör. «Ücretsiz Kargo», «Kargo Bedava», «free shipping»), çevirme <strong>her zaman</strong> o ödülü verir. Listede uygun satır yoksa yine rastgele seçilir. <strong>Kapalı</strong> iken davranış tamamen rastgeledir.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Başlık</label>
                            <input type="text" name="carkifelek_baslik" value="<?php echo htmlspecialchars($carkifelek_baslik); ?>" class="form-control" placeholder="Çarkıfelek Çevir, İndirim Kazan!">
                            <small class="text-muted">Çarkıfelek modalında gösterilecek başlık</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Açıklama</label>
                            <textarea name="carkifelek_aciklama" class="form-control" rows="3" placeholder="Her gün bir kez çarkıfelek çevirerek indirim kazanabilirsiniz!"><?php echo htmlspecialchars($carkifelek_aciklama); ?></textarea>
                            <small class="text-muted">Çarkıfelek modalında gösterilecek açıklama metni</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Ana Renk 1</label>
                            <input type="color" name="carkifelek_renk1" value="<?php echo htmlspecialchars($carkifelek_renk1); ?>" class="form-control" style="height: 50px;">
                            <small class="text-muted">Çark dilimleri (birinci renk), başlık ve buton gradyanı</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Ana Renk 2</label>
                            <input type="color" name="carkifelek_renk2" value="<?php echo htmlspecialchars($carkifelek_renk2); ?>" class="form-control" style="height: 50px;">
                            <small class="text-muted">Çark dilimleri (çift renk sırayla), buton gradyanı ve başlık gradyanı</small>
                        </div>

                        <div class="form-group">
                            <label>Vurgu rengi</label>
                            <input type="color" name="carkifelek_vurgu" value="<?php echo htmlspecialchars($carkifelek_vurgu); ?>" class="form-control" style="height: 50px;">
                            <small class="text-muted">Ok ucu, merkez halkası, dış parlama ve konfeti vurgusu</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Ödüller (Her satıra bir ödül yazın)</label>
                            <textarea name="carkifelek_oduller" class="form-control" rows="8" placeholder='%10 İndirim
%15 İndirim
%20 İndirim
%25 İndirim
Ücretsiz Kargo
Tekrar Deneyin'><?php 
$oduller_array = json_decode($carkifelek_oduller, true);
if (is_array($oduller_array)) {
    echo implode("\n", $oduller_array);
} else {
    echo $carkifelek_oduller;
}
?></textarea>
                            <small class="text-muted">Her satıra bir ödül yazın. Örnek: %10 İndirim, Ücretsiz Kargo, vb.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="carkifelek_guncelle" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                    
                    <div class="alert alert-info" style="margin-top: 20px;">
                        <strong>Bilgi:</strong> Çarkıfelek oyunu müşterilerin her gün bir kez çevirebileceği bir pazarlama aracıdır. Varsayılan olarak ödüller rastgele seçilir; «Ücretsiz kargo — her zaman bu ödül» açıksa ve listede kargo ödülü tanınıyorsa sonuç sabittir. Log kaydı tutulur.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
