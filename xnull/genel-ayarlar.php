<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$_ayar_row = $db->query('SELECT * FROM ayar WHERE ayar_id=0')->fetch(PDO::FETCH_ASSOC);
if ($_ayar_row) {
    $settingsprint = $_ayar_row;
}
$sms=$db->prepare("SELECT * from sms where sms_id=?");
$sms->execute(array(0));
$smsprint=$sms->fetch(PDO::FETCH_ASSOC);
$mail=$db->prepare("SELECT * from mail where mail_id=?");
$mail->execute(array(0));
$mailprint=$mail->fetch(PDO::FETCH_ASSOC);
?>  
<section class="main-content container">

    <div class="page-header">
        <h2>Genel Ayarlar</h2>
    </div>
    <?php
    if (isset($_GET['tab']) && $_GET['tab'] === 'parasut' && isset($_GET['status'])) {
        if ($_GET['status'] === 'ok') {
            echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Paraşüt ayarları kaydedildi. Kimlik bilgileri değiştiyse yeni oturum için token sıfırlandı.</div>';
        } elseif ($_GET['status'] === 'no') {
            echo '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Paraşüt ayarları kaydedilemedi.</div>';
        }
    }
    if (isset($_GET['tab']) && $_GET['tab'] === 'mail') {
        if (isset($_GET['status'])) {
            if ($_GET['status'] === 'ok') {
                echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>SMTP ayarları kaydedildi.</div>';
            } elseif ($_GET['status'] === 'no') {
                echo '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>SMTP ayarları kaydedilemedi.</div>';
            }
        }
        if (isset($_GET['mail_test_ok']) && $_GET['mail_test_ok'] === '1') {
            echo '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>Test e-postası gönderildi. Gelen kutusu ve <strong>spam</strong> klasörünü kontrol edin.</div>';
        }
        if (!empty($_GET['mail_test_err'])) {
            echo '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button>' . htmlspecialchars($_GET['mail_test_err'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
    ?>
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="tabs tabs-responsive-wrapper">
                <ul class="nav nav-tabs">
                    <li class="nav-item" role="presentation"><a class="nav-link  active" href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Ayarlar</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" href="#modules" aria-controls="modules" role="tab" data-toggle="tab">Bölüm Yönetimi</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" href="#seo" aria-controls="seo" role="tab" data-toggle="tab">Seo</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" href="#mail" aria-controls="mail" role="tab" data-toggle="tab">SMTP</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" href="#sms_settings" aria-controls="sms_settings" role="tab" data-toggle="tab">SMS Ayarları</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" href="#parasut" aria-controls="parasut" role="tab" data-toggle="tab">Paraşüt</a></li>
                </ul>
            </div>
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="settings">
                        <div class="widget white-bg">
                            <div class="card-heading card-default">
                                GENEL AYARLAR
                            </div>
                            <div class="card-block">
                                <form id="signupForm" method="POST" enctype="multipart/form-data" class="form-horizontal" action="controller/function.php">
                                    <div class="form-group">
                                        <input type="hidden" name="eskiyol_logo" value="<?php echo $settingsprint['ayar_logo']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="eskiyol_fav" value="<?php echo $settingsprint['ayar_fav']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Yüklü Favicon</label> <small> Logo max 36x36 olmalıdır.</small>
                                        <p><img style="max-height: 100px;max-width: 100px;" src="<?php echo $settingsprint['ayar_fav']; ?>"></p>
                                    </div>
                                    <div class="form-group">
                                        <div class="fileinput fileinput-new input-group" data-provides="fileinput">
                                            <div class="form-control" data-trigger="fileinput"><span class="fileinput-filename"></span></div>
                                            <span class="input-group-addon btn btn-primary btn-file ">
                                                <span class="fileinput-new">Yeni Yükle</span>
                                                <span class="fileinput-exists">Değiştir</span>
                                                <input type="file"  name="ayar_fav">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-danger  hover fileinput-exists" data-dismiss="fileinput">Sil</a>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Logo Tipi</label>
                                        <select name="ayar_logo_tip" class="form-control">
                                            <option value="0" <?php echo ($settingsprint['ayar_logo_tip'] == 0) ? 'selected' : ''; ?>>Görsel Logo (Yüklü resim görünür)</option>
                                            <option value="1" <?php echo ($settingsprint['ayar_logo_tip'] == 1) ? 'selected' : ''; ?>>Yazı Logo (Sadece metin görünür)</option>
                                            <option value="2" <?php echo ($settingsprint['ayar_logo_tip'] == 2) ? 'selected' : ''; ?>>İkon Logo (Sadece seçilen ikon görünür)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Yazı Logo İçeriği (Logo Tipi "Yazı" ise geçerlidir)</label>
                                        <input type="text" name="ayar_logo_metin" value="<?php echo $settingsprint['ayar_logo_metin']; ?>" class="form-control" placeholder="Örn: Taktik Pantolon">
                                    </div>
                                    <div class="form-group">
                                        <label>Yazı Logo İkonu (FontAwesome)</label> <small>Aşağıdaki listeden seçebilir veya ikon adını yazabilirsiniz.</small>
                                        <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 15px;">
                                            <input type="text" id="icon_input" name="ayar_logo_icon" value="<?php echo $settingsprint['ayar_logo_icon']; ?>" class="form-control" placeholder="fa-shopping-cart" style="flex: 1;">
                                            <div id="icon_preview" style="width: 45px; height: 45px; background: #000; color: #fff; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #444;">
                                                <i class="fa <?php echo $settingsprint['ayar_logo_icon'] ? $settingsprint['ayar_logo_icon'] : 'fa-info-circle'; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="icon-picker-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(40px, 1fr)); gap: 8px; background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #eee;">
                                            <?php 
                                            $popular_icons = [
                                                'fa-shopping-cart', 'fa-shopping-bag', 'fa-shopping-basket', 'fa-tag', 'fa-tags', 'fa-credit-card',
                                                'fa-shield', 'fa-lock', 'fa-check-circle', 'fa-certificate', 'fa-star', 'fa-thumbs-up',
                                                'fa-truck', 'fa-rocket', 'fa-bolt', 'fa-flash', 'fa-plane', 'fa-anchor',
                                                'fa-heart', 'fa-diamond', 'fa-cube', 'fa-leaf', 'fa-gift', 'fa-magic',
                                                'fa-globe', 'fa-map-marker', 'fa-phone', 'fa-envelope', 'fa-user', 'fa-home'
                                            ];
                                            foreach ($popular_icons as $ico) {
                                                echo '<div class="icon-item" onclick="selectIcon(\''.$ico.'\')" style="cursor: pointer; padding: 8px; text-align: center; background: #fff; border: 1px solid #ddd; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background=\'#eee\'" onmouseout="this.style.background=\'#fff\'"><i class="fa '.$ico.'" title="'.$ico.'"></i></div>';
                                            }
                                            ?>
                                        </div>
                                        <script>
                                        function selectIcon(iconName) {
                                            document.getElementById('icon_input').value = iconName;
                                            document.getElementById('icon_preview').innerHTML = '<i class="fa ' + iconName + '"></i>';
                                            
                                            // Efekt: Tüm itemların arkaplanını sıfırla, seçileni vurgula
                                            var items = document.querySelectorAll('.icon-item');
                                            items.forEach(function(item) {
                                                item.style.borderColor = '#ddd';
                                                item.style.background = '#fff';
                                            });
                                            event.currentTarget.style.borderColor = 'var(--renk2)';
                                            event.currentTarget.style.background = '#f0f7ff';
                                        }
                                        
                                        document.getElementById('icon_input').addEventListener('input', function() {
                                            document.getElementById('icon_preview').innerHTML = '<i class="fa ' + this.value + '"></i>';
                                        });
                                        </script>
                                    </div>
                                    <div class="form-group" id="logo_container">
                                        <input type="hidden" name="eskiyol_logo" id="eskiyol_logo" value="<?php echo $settingsprint['ayar_logo']; ?>">
                                        <input type="hidden" name="sil_logo" id="sil_logo" value="0">
                                        <label>Header Logo (Ekranın En Üstündeki Logo)</label> <small>Önerilen boyut: 200x60px</small>
                                        <div style="position: relative; display: block; margin-top: 10px;">
                                            <p><img id="logo_preview_img" style="max-height: 80px; max-width: 250px; background: #333; padding: 10px; border-radius: 4px;" src="<?php echo $settingsprint['ayar_logo']; ?>"></p>
                                            <?php if (!empty($settingsprint['ayar_logo'])) { ?>
                                                <button type="button" onclick="removeImage('logo')" style="position: absolute; top: -5px; left: -5px; background: #ff4757; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"><i class="fa fa-times"></i></button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="fileinput fileinput-new input-group" data-provides="fileinput">
                                            <div class="form-control" data-trigger="fileinput"><span class="fileinput-filename"></span></div>
                                            <span class="input-group-addon btn btn-primary btn-file ">
                                                <span class="fileinput-new">Yeni Logo Yükle</span>
                                                <span class="fileinput-exists">Değiştir</span>
                                                <input type="file"  name="ayar_logo">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-danger  hover fileinput-exists" data-dismiss="fileinput">Sil</a>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group" id="altgorsel_container">
                                        <input type="hidden" name="eskiyol_altgorsel" id="eskiyol_altgorsel" value="<?php echo $settingsprint['ayar_altgorsel']; ?>">
                                        <input type="hidden" name="sil_altgorsel" id="sil_altgorsel" value="0">
                                        <label>Alt Görsel / Güven Rozetleri (Sipariş Sayfası & Alt Kısımlar)</label> <small>Önerilen boyut: 500x150px (Yatay)</small>
                                        <div style="position: relative; display: block; margin-top: 10px;">
                                            <p><img id="altgorsel_preview_img" style="max-height: 100px; max-width: 300px; border: 1px solid #ddd; padding: 5px;" src="<?php echo $settingsprint['ayar_altgorsel']; ?>"></p>
                                            <?php if (!empty($settingsprint['ayar_altgorsel'])) { ?>
                                                <button type="button" onclick="removeImage('altgorsel')" style="position: absolute; top: -5px; right: -5px; background: #ff4757; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"><i class="fa fa-times"></i></button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="fileinput fileinput-new input-group" data-provides="fileinput">
                                            <div class="form-control" data-trigger="fileinput"><span class="fileinput-filename"></span></div>
                                            <span class="input-group-addon btn btn-info btn-file ">
                                                <span class="fileinput-new">Yeni Görsel Yükle</span>
                                                <span class="fileinput-exists">Değiştir</span>
                                                <input type="file"  name="ayar_altgorsel">
                                            </span>
                                            <a href="#" class="input-group-addon btn btn-danger  hover fileinput-exists" data-dismiss="fileinput">Sil</a>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="ayar_siteurl">Site Link <small>Belirtilen linke <b style="color: red;">http://</b> veya <b style="color: red;">https://</b> dahil edip sonuna / ekleyiniz.</small></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="ayar_siteurl" name="ayar_siteurl" value="<?php echo $settingsprint['ayar_siteurl']; ?>" placeholder="http://mesela.com/" />
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default" onclick="autoDetectUrl()">Otomatik Çek</button>
                                            </span>
                                        </div>
                                        <script>
                                        function autoDetectUrl() {
                                            // PHP tarafından hesaplanan garanti doğru adresi al
                                            var detectedUrl = "<?php 
                                                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                                                $path = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'])));
                                                // Eğer root ise (/) çift slash olmasın diye temizle
                                                $path = rtrim($path, '/');
                                                echo $protocol . "://" . $_SERVER['HTTP_HOST'] . $path . "/";
                                            ?>";
                                            document.getElementById('ayar_siteurl').value = detectedUrl;
                                        }
                                        </script>
                                    </div>
                                    <div class="form-group">
                                        <label for="ayar_siteurl">Site Maximum Genişlik</label>
                                        <input type="text" class="form-control" id="ayar_harita" name="ayar_harita" value="<?php echo $settingsprint['ayar_harita']; ?>" />
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Ürün Listeleme Tasarımı</label>
                                        <?php $ayar_urun_sablon_val = isset($settingsprint['ayar_urun_sablon']) ? (int)$settingsprint['ayar_urun_sablon'] : 1; ?>
                                        <select name="ayar_urun_sablon" class="form-control">
                                            <option value="1" <?php echo ($ayar_urun_sablon_val === 1) ? 'selected' : ''; ?>>Standart Tasarım (Görsel Ağırlıklı)</option>
                                            <option value="2" <?php echo ($ayar_urun_sablon_val === 2) ? 'selected' : ''; ?>>Yeni Tasarım (Liste/Kart Tipi - Etiketli)</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Site Arkaplan Rengi</label> 
                                        <input class="jscolor form-control form-control-rounded" name="ayar_kod" value="<?php echo $settingsprint['ayar_kod']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Site Arkaplan Gölge</label>
                                        <select name="ayar_adres" class="form-control m-b">
                                            <?php if ($settingsprint['ayar_adres']==1) { ?>
                                                <option value="1">Aktif</option>
                                                <option value="0">Pasif</option>
                                            <?php } else { ?>
                                                <option value="0">Pasif</option>
                                                <option value="1">Aktif</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Site Arkaplan Gölge Rengi</label> 
                                        <input class="jscolor form-control form-control-rounded" name="ayar_fav2" value="<?php echo $settingsprint['ayar_fav2']; ?>">
                                    </div>


                                    <div class="form-group">
                                        <label>Müşteriler 24 saat içinde sipariş</label>
                                        <select name="ayar_il" class="form-control m-b">
                                            <?php if ($settingsprint['ayar_il']==1) { ?>
                                                <option value="1">Verebilsin</option>
                                                <option value="0">Veremesin</option>
                                            <?php } else { ?>
                                                <option value="0">Veremesin</option>
                                                <option value="1">Verebilsin</option>
                                            <?php } ?>
                                        </select>
                                        <label style="margin-top:8px;">Sipariş engeli süresi (saat)</label>
                                        <input type="number" name="ayar_il_sure" min="1" max="999999" step="1" value="<?php echo isset($settingsprint['ayar_il_sure']) ? intval($settingsprint['ayar_il_sure']) : 24; ?>" class="form-control">
                                        <small class="text-muted">“Veremesin” seçiliyken aynı IP, son siparişten bu kadar saat geçmeden yeni sipariş veremez. Örnek: <b>24</b> = 1 gün, <b>168</b> = 1 hafta, <b>720</b> ≈ 30 gün, <b>8760</b> ≈ 1 yıl. En fazla <b>999999</b> saat girebilirsiniz.</small>
                                    </div>

                                    
                                    <hr style="margin: 20px 0; border-color: #ddd;">
                                    <h4 style="margin-bottom: 20px; color: #337ab7;"><i class="fa fa-video-camera"></i> Manuel Video Ayarları (Yüklenen MP4)</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Otomatik Oynatma</label>
                                                <select name="ayar_video_autoplay" class="form-control">
                                                    <option value="1" <?php echo (isset($settingsprint['ayar_video_autoplay']) && $settingsprint['ayar_video_autoplay'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="0" <?php echo (isset($settingsprint['ayar_video_autoplay']) && $settingsprint['ayar_video_autoplay'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Sessiz Oynatma</label>
                                                <select name="ayar_video_muted" class="form-control">
                                                    <option value="1" <?php echo (isset($settingsprint['ayar_video_muted']) && $settingsprint['ayar_video_muted'] == 1) ? 'selected' : ''; ?>>Aktif (Sessiz)</option>
                                                    <option value="0" <?php echo (isset($settingsprint['ayar_video_muted']) && $settingsprint['ayar_video_muted'] == 0) ? 'selected' : ''; ?>>Pasif (Sesli)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Sürekli Döngü (Loop)</label>
                                                <select name="ayar_video_loop" class="form-control">
                                                    <option value="1" <?php echo (isset($settingsprint['ayar_video_loop']) && $settingsprint['ayar_video_loop'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="0" <?php echo (isset($settingsprint['ayar_video_loop']) && $settingsprint['ayar_video_loop'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr style="margin: 20px 0; border-color: #ddd;">
                                    <h4 style="margin-bottom: 20px; color: #ff0000;"><i class="fa fa-youtube-play"></i> YouTube Video Ayarları</h4>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>YouTube Sessiz Oynatma</label>
                                                <select name="ayar_youtube_muted" class="form-control">
                                                    <option value="1" <?php echo (isset($settingsprint['ayar_youtube_muted']) && $settingsprint['ayar_youtube_muted'] == 1) ? 'selected' : ''; ?>>Aktif (Sessiz)</option>
                                                    <option value="0" <?php echo (isset($settingsprint['ayar_youtube_muted']) && $settingsprint['ayar_youtube_muted'] == 0) ? 'selected' : ''; ?>>Pasif (Sesli)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>YouTube Sürekli Döngü (Loop)</label>
                                                <select name="ayar_youtube_loop" class="form-control">
                                                    <option value="1" <?php echo (isset($settingsprint['ayar_youtube_loop']) && $settingsprint['ayar_youtube_loop'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="0" <?php echo (isset($settingsprint['ayar_youtube_loop']) && $settingsprint['ayar_youtube_loop'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div style="padding-top: 25px;">
                                                <span class="label label-warning" style="white-space: normal; display: inline-block; padding: 10px; line-height: 1.4;">
                                                    <i class="fa fa-exclamation-triangle"></i> YouTube "Otomatik Oynatma" özelliği tarayıcıların güncel politikaları gereği stabil çalışmadığı için kaldırılmıştır.
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <hr style="margin: 20px 0; border-color: #ddd;">
                                    <h4 style="margin-bottom: 20px; color: #333;"><i class="fa fa-sitemap"></i> Ortak Panel Entegrasyonu</h4>

                                    <div class="form-group">
                                        <label>Durum</label>
                                        <select name="ayar_common_panel_status" class="form-control m-b">
                                            <?php 
                                            $cp_status = isset($settingsprint['ayar_common_panel_status']) ? $settingsprint['ayar_common_panel_status'] : 0;
                                            if ($cp_status == 1) { ?>
                                                <option value="1">Aktif</option>
                                                <option value="0">Pasif</option>
                                            <?php } else { ?>
                                                <option value="0">Pasif</option>
                                                <option value="1">Aktif</option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Panel URL (API Endpoint)</label>
                                        <input type="text" class="form-control" name="ayar_common_panel_url" value="<?php echo isset($settingsprint['ayar_common_panel_url']) ? $settingsprint['ayar_common_panel_url'] : 'http://localhost/ortak_panel/api/receive.php'; ?>" placeholder="http://localhost/ortak_panel/api/receive.php">
                                        <small class="text-muted">Örn: http://localhost/ortak_panel/api/receive.php</small>
                                    </div>

                                    <div class="form-group">
                                        <label>API Key (Opsiyonel)</label>
                                        <input type="text" class="form-control" name="ayar_common_panel_key" value="<?php echo isset($settingsprint['ayar_common_panel_key']) ? $settingsprint['ayar_common_panel_key'] : ''; ?>" placeholder="Panelde belirlenen API anahtarı">
                                    </div>

                                    <div class="form-group" style="background: #f0f7ff; padding: 15px; border-radius: 8px; border: 1px solid #cce5ff;">
                                        <label><i class="fa fa-search"></i> Sipariş Durumunu Ortak Panelden Sorgula</label>
                                        <select name="ayar_common_query_status" class="form-control m-b">
                                            <?php 
                                            $cq_status = isset($settingsprint['ayar_common_query_status']) ? $settingsprint['ayar_common_query_status'] : 0;
                                            if ($cq_status == 1) { ?>
                                                <option value="1">Aktif (Ortak Panelden Sorgula)</option>
                                                <option value="0">Pasif (Yerel Veritabanından Sorgula)</option>
                                            <?php } else { ?>
                                                <option value="0">Pasif (Yerel Veritabanından Sorgula)</option>
                                                <option value="1">Aktif (Ortak Panelden Sorgula)</option>
                                            <?php } ?>
                                        </select>
                                        <small class="text-muted">Aktif edilirse, sitedeki sipariş sorgulama formu verileri Ortak Panel'den çeker.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label><i class="fa fa-arrow-up"></i> Yukarı Çık Butonu</label>
                                        <select name="ayar_yukari_cik_on" class="form-control m-b">
                                            <?php 
                                            $yukari_cik_on = isset($settingsprint['ayar_yukari_cik_on']) ? $settingsprint['ayar_yukari_cik_on'] : 1;
                                            if ($yukari_cik_on == 1) { ?>
                                                <option value="1" selected>Aktif</option>
                                                <option value="0">Pasif</option>
                                            <?php } else { ?>
                                                <option value="1">Aktif</option>
                                                <option value="0" selected>Pasif</option>
                                            <?php } ?>
                                        </select>
                                        <small class="text-muted">Sayfa aşağı kaydırıldığında görünen yukarı çık butonu.</small>
                                    </div>

                                    <div class="form-group">
                                        <label><i class="fa fa-shield"></i> Sipariş Doğrulama (OTP / Tek Tık Link)</label>
                                        <select name="ayar_siparis_dogrulama_on" class="form-control m-b">
                                            <?php
                                            $siparis_dogrulama_on = isset($settingsprint['ayar_siparis_dogrulama_on']) ? (int)$settingsprint['ayar_siparis_dogrulama_on'] : 1;
                                            if ($siparis_dogrulama_on === 1) { ?>
                                                <option value="1" selected>Aktif</option>
                                                <option value="0">Pasif</option>
                                            <?php } else { ?>
                                                <option value="1">Aktif</option>
                                                <option value="0" selected>Pasif</option>
                                            <?php } ?>
                                        </select>
                                        <small class="text-muted">Aktifken sipariş sonrası SMS OTP ve tek tık doğrulama linki devreye girer.</small>
                                    </div>
                                    

                                    
                                    <hr style="margin: 20px 0; border-color: #ddd;">
                                    
                                    <div class="form-group">
                                        <label><i class="fa fa-trash"></i> Çerez Sıfırlama</label>
                                        <div class="alert alert-info">
                                            <p><strong>Çerez Sıfırlama:</strong> Tüm tarayıcı çerezlerini temizler. Tasarım değişikliklerinde veya cache sorunlarında kullanılabilir.</p>
                                            <button type="button" class="btn btn-warning" onclick="clearAllCookies()">
                                                <i class="fa fa-trash"></i> Tüm Çerezleri Sıfırla
                                            </button>
                                        </div>
                                        <small class="text-muted">Bu işlem tüm site çerezlerini temizler ve sayfayı yeniler.</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" name="genelayar" value="Sign up">Güncelle</button>
                                    </div>
                                </form>
                            </div>
                            <!-- FORM SON -->
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="seo">
                        <div class="widget white-bg">
                            <!-- FORM BAŞLA -->
                            <div class="card-heading card-default">
                                SEO AYARLARI
                            </div>
                            <div class="card-block">
                                <form id="signupForm" method="post" class="form-horizontal" action="controller/function.php">
                                    <div class="form-group">
                                        <label>Title</label>
                                        <input type="text" name="ayar_title" value="<?php echo $settingsprint['ayar_title']; ?>" class="form-control form-control-rounded">
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <input name="ayar_description" type="text" value="<?php echo $settingsprint['ayar_description']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Keywords</label>
                                        <input type="text" name="ayar_keywords" value="<?php echo $settingsprint['ayar_keywords']; ?>" class="form-control form-control-rounded">
                                        <small class="text-muted">Örnek : <code>elma, armut, muz, karpuz</code></small>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" name="seoayar" value="Sign up">Güncelle</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="modules">
                        <div class="widget white-bg">
                            <div class="card-heading card-default">
                                BÖLÜM AKTİVASYONU (Aç / Kapat)
                            </div>
                            <div class="card-block">
                                <form method="POST" class="form-horizontal" action="controller/function.php">
                                    <div class="form-group">
                                        <label>Sipariş Formu Sözleşme Onayı</label>
                                        <select name="ayar_sozlesme_on" class="form-control m-b">
                                            <option value="1" <?php echo $settingsprint['ayar_sozlesme_on'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo $settingsprint['ayar_sozlesme_on'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Sipariş Formu Gizlilik Politikası Onayı</label>
                                        <select name="ayar_gizlilik_on" class="form-control m-b">
                                            <option value="1" <?php echo $settingsprint['ayar_gizlilik_on'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo $settingsprint['ayar_gizlilik_on'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Kurumsal / Fatura Bilgileri (sipariş formunda, tıklanınca açılır)</label>
                                        <select name="ayar_kurumsal_fatura_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_kurumsal_fatura_on']) && (int)$settingsprint['ayar_kurumsal_fatura_on'] === 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (!isset($settingsprint['ayar_kurumsal_fatura_on']) || (int)$settingsprint['ayar_kurumsal_fatura_on'] === 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Açıkken müşteri isteğe bağlı olarak vergi no, vergi dairesi, firma ünvanı ve fatura adresi girebilir; sipariş listesinde görünür.</small>
                                    </div>

                                    <div class="form-group" style="background: #fff9f0; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba;">
                                        <label><strong>Tarayıcı Tabanlı Sipariş Engeli (Çerez)</strong></label>
                                        <select name="ayar_cookie_on" class="form-control m-b">
                                            <option value="1" <?php echo ($settingsprint['ayar_cookie_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo ($settingsprint['ayar_cookie_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Aktif edilirse, sipariş veren tarayıcıya çerez bırakılır ve belirlenen süre boyunca tekrar sipariş vermesi engellenir.</small>
                                        
                                        <label style="margin-top: 10px;">Engel süresi (dakika)</label>
                                        <input type="number" name="ayar_cookie_sure" min="1" max="99999999" step="1" value="<?php echo isset($settingsprint['ayar_cookie_sure']) ? intval($settingsprint['ayar_cookie_sure']) : 1440; ?>" class="form-control">
                                        <small class="text-muted">Siparişten sonra <code>order_blocked</code> çerezi bu kadar dakika kalır. Örnek: <b>60</b> = 1 saat, <b>1440</b> = 1 gün, <b>10080</b> = 1 hafta, <b>43200</b> ≈ 30 gün, <b>525600</b> ≈ 1 yıl. En fazla <b>99999999</b> dakika (kayıtta da aynı tavan).</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Alt Görsel (Sipariş Sonrası Resim)</label>
                                        <select name="ayar_altgorsel_on" class="form-control m-b">
                                            <option value="1" <?php echo isset($settingsprint['ayar_altgorsel_on']) && $settingsprint['ayar_altgorsel_on'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo isset($settingsprint['ayar_altgorsel_on']) && $settingsprint['ayar_altgorsel_on'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label> "Neden Biz?" Alanı (3'lü Kutu)</label>
                                        <select name="ayar_nedenbiz_on" class="form-control m-b">
                                            <option value="1" <?php echo isset($settingsprint['ayar_nedenbiz_on']) && $settingsprint['ayar_nedenbiz_on'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo isset($settingsprint['ayar_nedenbiz_on']) && $settingsprint['ayar_nedenbiz_on'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Footer (Alt Bilgi & Copyright)</label>
                                        <select name="ayar_footer_on" class="form-control m-b">
                                            <option value="1" <?php echo isset($settingsprint['ayar_footer_on']) && $settingsprint['ayar_footer_on'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo isset($settingsprint['ayar_footer_on']) && $settingsprint['ayar_footer_on'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Thank You Sayfası - Sonraki Adımlar Bölümü</label>
                                        <select name="ayar_sonraki_adim_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_sonraki_adim_on']) && $settingsprint['ayar_sonraki_adim_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_sonraki_adim_on']) && $settingsprint['ayar_sonraki_adim_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Thank You Sayfası - Sonraki Adımlar Metni</label>
                                        <textarea name="ayar_sonraki_adim_text" class="form-control summernote" rows="5" placeholder="Müşteri temsilcimiz en kısa sürede sizinle iletişime geçerek siparişinizi onaylayacak ve kargo sürecini başlatacaktır. Sipariş durumunuz hakkında bilgilendirmeler admin tarafından size iletilecektir."><?php echo isset($settingsprint['ayar_sonraki_adim_text']) ? htmlspecialchars($settingsprint['ayar_sonraki_adim_text']) : 'Müşteri temsilcimiz en kısa sürede sizinle iletişime geçerek siparişinizi onaylayacak ve kargo sürecini başlatacaktır. Sipariş durumunuz hakkında bilgilendirmeler admin tarafından size iletilecektir.'; ?></textarea>
                                        <small class="text-muted">Bu metin thank you sayfasında "Sonraki Adımlar" bölümünde gösterilecektir.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Sipariş Sorgulama Bölümü</label>
                                        <select name="ayar_sorgula_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_sorgula_on']) && $settingsprint['ayar_sorgula_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_sorgula_on']) && $settingsprint['ayar_sorgula_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>FOMO Geri Sayım Sayacı</label>
                                        <select name="ayar_fomo_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_fomo_on']) && $settingsprint['ayar_fomo_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_fomo_on']) && $settingsprint['ayar_fomo_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>FOMO Bitiş Saati (Örn: 16:00)</label>
                                        <input type="text" name="ayar_fomo_saat" value="<?php echo $settingsprint['ayar_fomo_saat'] ?? '16:00'; ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Canlı Satış Bildirimleri (Social Proof)</label>
                                        <select name="ayar_bildirim_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_bildirim_on']) && $settingsprint['ayar_bildirim_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_bildirim_on']) && $settingsprint['ayar_bildirim_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>IP Adresinden Otomatik Şehir Tahmini</label>
                                        <select name="ayar_ip_sehir_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_ip_sehir_on']) && $settingsprint['ayar_ip_sehir_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_ip_sehir_on']) && $settingsprint['ayar_ip_sehir_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Müşteri sipariş formuna girdiğinde şehrini otomatik seçer.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Gelişmiş Sahte Stok Çubuğu</label>
                                        <select name="ayar_stok_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_stok_on']) && $settingsprint['ayar_stok_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_stok_on']) && $settingsprint['ayar_stok_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Frontend tarafında "Son X ürün kaldı" çubuğunu gösterir.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Sahte Stok Başlangıç Sayısı</label>
                                        <input type="number" name="ayar_stok_sayi" value="<?php echo $settingsprint['ayar_stok_sayi'] ?? '15'; ?>" class="form-control">
                                        <small class="text-muted">Stok çubuğu bu sayıdan başlar (Örn: 25 yazarsanız %100 dolu görünür).</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Ürün Seçiminde İsim Gösterimi</label>
                                        <select name="ayar_urun_ad_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_urun_ad_on']) && $settingsprint['ayar_urun_ad_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_urun_ad_on']) && $settingsprint['ayar_urun_ad_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ürün Seçiminde Fiyat Gösterimi</label>
                                        <select name="ayar_urun_fiyat_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_urun_fiyat_on']) && $settingsprint['ayar_urun_fiyat_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_urun_fiyat_on']) && $settingsprint['ayar_urun_fiyat_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Ürün Adı Yazı Boyutu (Örn: 1.4)</label>
                                        <input type="text" name="ayar_urun_ad_boyut" value="<?php echo $settingsprint['ayar_urun_ad_boyut'] ?? '1.4'; ?>" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Ürün Fiyat Yazı Boyutu (Örn: 1.5)</label>
                                        <input type="text" name="ayar_urun_fiyat_boyut" value="<?php echo $settingsprint['ayar_urun_fiyat_boyut'] ?? '1.5'; ?>" class="form-control">
                                    </div>
                                     <div class="form-group">
                                         <label>Ürün Seçenekleri (Alt Seçenekler)</label>
                                         <select name="ayar_urun_secenek_on" class="form-control m-b">
                                             <option value="1" <?php echo (isset($settingsprint['ayar_urun_secenek_on']) && $settingsprint['ayar_urun_secenek_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                             <option value="0" <?php echo (isset($settingsprint['ayar_urun_secenek_on']) && $settingsprint['ayar_urun_secenek_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                         </select>
                                         <small class="text-muted">Ürün seçildiğinde alt seçeneklerin (beden, renk vb.) gösterilip gösterilmeyeceğini kontrol eder.</small>
                                     </div>
                                    <div class="form-group">
                                        <label>Yarım Kalanlar WhatsApp Mesaj Şablonu</label>
                                        <textarea name="ayar_wa_sablon" class="form-control" rows="3"><?php echo $settingsprint['ayar_wa_sablon'] ?? ''; ?></textarea>
                                        <small class="text-muted">Müşteriye tek tıkla gönderilecek mesaj metni.</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Sipariş Barı (Mobil Alt Bar)</label>
                                        <select name="ayar_siparis_bar" class="form-control m-b">
                                            <option value="1" <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_siparis_bar']) && $settingsprint['ayar_siparis_bar'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Mobilde sayfanın en altında sabit duran "Hemen Sipariş Ver" barını açar/kapatır.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Ana Sayfa Yorumları (Yeni Tasarım)</label>
                                        <select name="ayar_yorum_anasayfa_on" class="form-control m-b">
                                            <option value="1" <?php echo (isset($settingsprint['ayar_yorum_anasayfa_on']) && $settingsprint['ayar_yorum_anasayfa_on'] == 1) ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo (isset($settingsprint['ayar_yorum_anasayfa_on']) && $settingsprint['ayar_yorum_anasayfa_on'] == 0) ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <small class="text-muted">Sipariş formunun üzerindeki premium yorum kartlarını açar/kapatır.</small>
                                    </div>
                                    <button type="submit" name="modulyonetimi" class="btn btn-success">Değişiklikleri Kaydet</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="mail">
                        <div class="widget white-bg">
                            <!-- FORM BAŞLA -->
                            <div class="card-heading card-default">
                                SMTP AYARLARI
                            </div>
                            <div class="card-block">
                                <!-- SMTP BİLGİLENDİRME -->
                                <div class="alert alert-info" style="border-left: 5px solid #3498db; background: #f0f7fd; color: #2c3e50; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                    <h4 style="margin-top: 0; font-weight: 700; color: #2980b9;"><i class="fa fa-info-circle"></i> SMTP Yapılandırma Kılavuzu</h4>
                                    <p style="margin-bottom: 15px; line-height: 1.6;">Sistem üzerinden mail gönderimi yapabilmek için geçerli bir SMTP servisine ihtiyacınız vardır. İşte dikkat etmeniz gerekenler:</p>
                                    <p style="margin-bottom: 12px; line-height: 1.6;"><strong>Bu ayarlar nerede kullanılıyor?</strong> Veritabanındaki <code>mail</code> tablosuna kaydedilir. <strong>Bildirim Yapılacak Mail</strong> adresine vitrin siparişi ve panelden manuel sipariş sonrası otomatik “yeni sipariş” e-postası gider. Ayrıca <code>phpmail/siparis.php</code> (örn. <code>guvenli-odeme.php</code> dönüşü) ve <code>phpmail/yorum.php</code> (yorum onayı) bu SMTP’yi kullanır. Canlı sunucuda <code>phpmail/</code> (PHPMailer) yüklü olmalıdır.</p>
                                    <ul style="padding-left: 20px; line-height: 2;">
                                        <li><strong>Bildirim Yapılacak Mail:</strong> Sipariş veya form bildirimlerinin hangi mail adresine ulaşmasını istiyorsanız buraya yazın. (Örn: admin@siteadi.com)</li>
                                        <li><strong>Mail Adresi & Şifre:</strong> Gönderimi yapacak olan asıl mail hesabınızın (kurumsal mail) bilgileridir.</li>
                                        <li><strong>Mail Sunucu:</strong> Genellikle <code>mail.teknomask.com</code> veya <code>smtp.gmail.com</code> şeklindedir.</li>
                                        <li><strong>Mail Port:</strong> SSL için <code>465</code>, TLS için <code>587</code> tercih edilmelidir.</li>
                                        <li><strong>Güvenlik (SSL/TLS):</strong> Sunucunuzun desteklediği protokolü seçin. Kurumsal maillerde genelde TLS (587) kullanılır.</li>
                                        <li><strong>Mail Gönderici & Adı:</strong> Alıcının gelen kutusunda göreceği "Kimden" bilgisidir.</li>
                                    </ul>
                                    <div style="margin-top: 10px; padding: 10px; background: #e8f4fd; border-radius: 4px; font-size: 0.9rem;">
                                        <i class="fa fa-lightbulb-o"></i> <strong>İpucu:</strong> Eğer Gmail kullanacaksanız, Google hesabınızdan "Uygulama Şifresi" oluşturmanız gerekebilir.
                                    </div>
                                </div>
                                <form id="signupForm" method="post" class="form-horizontal" action="controller/function.php">
                                    <div class="form-group">
                                        <label>Bildirim Yapılacak Mail</label>
                                        <input type="text" name="mail_bildirim" value="<?php echo $mailprint['mail_bildirim']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Adresi</label>
                                        <input type="text" name="mail_user" value="<?php echo $mailprint['mail_user']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Şifre</label>
                                        <input type="text" name="mail_pass" value="<?php echo $mailprint['mail_pass']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Sunucu</label>
                                        <input type="text" name="mail_host" value="<?php echo $mailprint['mail_host']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Port</label>
                                        <input type="text" name="mail_port" value="<?php echo $mailprint['mail_port']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Gönderici</label>
                                        <input type="text" name="mail_sender" value="<?php echo $mailprint['mail_sender']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Mail Adı</label>
                                        <input type="text" name="mail_name" value="<?php echo $mailprint['mail_name']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label>Durum</label>
                                        <select name="mail_secure" class="form-control m-b">
                                            <?php if ($mailprint['mail_secure']=='ssl') { ?>
                                                <option value="ssl">SSL</option>
                                                <option value="tls">TLS</option>
                                                <?php
                                            } else {?>
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                            <?php }?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" name="mailayarlari" value="Sign up">Güncelle</button>
                                    </div>
                                </form>
                                <hr style="margin: 28px 0;">
                                <h4 style="font-weight: 700; margin-bottom: 12px;"><i class="fa fa-paper-plane-o"></i> Test e-postası</h4>
                                <p class="text-muted" style="margin-bottom: 15px;">Canlı sunucuda SMTP’nin çalıştığını doğrulamak için kayıtlı ayarlarla tek seferlik test maili gönderir. Önce yukarıdaki formdan ayarları kaydedin.</p>
                                <form method="post" class="form-horizontal" action="controller/function.php">
                                    <input type="hidden" name="mail_test_gonder" value="1">
                                    <div class="form-group">
                                        <label>Alıcı e-posta</label>
                                        <input type="email" name="mail_test_adres" value="<?php echo htmlspecialchars(isset($mailprint['mail_bildirim']) ? $mailprint['mail_bildirim'] : '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-rounded" placeholder="ornek@alanadi.com" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-outline-primary">Test maili gönder</button>
                                    </div>
                                </form>
                            </div>
                            <!-- FORM SON -->
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="sms_settings">
                        <div class="widget white-bg">
                            <!-- FORM BAŞLA -->
                            <div class="card-heading card-default">
                                SMS AYARLARI (MUTLUCELL / NETGSM)
                            </div>
                            <div class="card-block">
                                <?php if (isset($_GET['sms_test_ok']) && $_GET['sms_test_ok'] == '1') { ?>
                                <div class="alert alert-success" style="font-size:13px; margin-bottom:12px;">
                                    Test SMS gönderimi başarılı.
                                </div>
                                <?php } ?>
                                <?php if (isset($_GET['sms_test_err']) && $_GET['sms_test_err'] !== '') { ?>
                                <div class="alert alert-danger" style="font-size:13px; margin-bottom:12px;">
                                    Test SMS hatası: <?php echo htmlspecialchars($_GET['sms_test_err'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php } ?>
                                <?php if (isset($_GET['sms_api_test_ok']) && $_GET['sms_api_test_ok'] !== '') { ?>
                                <div class="alert alert-success" style="font-size:13px; margin-bottom:12px;">
                                    API test başarılı: <?php echo htmlspecialchars($_GET['sms_api_test_ok'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php } ?>
                                <?php if (isset($_GET['sms_api_test_err']) && $_GET['sms_api_test_err'] !== '') { ?>
                                <div class="alert alert-danger" style="font-size:13px; margin-bottom:12px;">
                                    API test hatası: <?php echo htmlspecialchars($_GET['sms_api_test_err'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php } ?>
                                <div class="alert alert-info" style="font-size: 13px;">
                                    <strong>SMS sağlayıcı seçimi:</strong> Aşağıdan <strong>Mutlucell</strong> veya <strong>Netgsm</strong> seçin. Sistem sadece seçili sağlayıcı ile gönderir.
                                    <br>Bu alanda girdiğiniz kullanıcı adı / şifre / başlık seçili sağlayıcı için kullanılır.
                                    <br>Telefon numarası <code>05xx</code>, <code>5xx</code> veya <code>+90 5xx</code> girilse de sistem otomatik normalize eder.
                                    <br><a href="https://www.mutlucell.com.tr/api" target="_blank" rel="noopener">Mutlucell API</a>
                                    · <a href="https://www.netgsm.com.tr/dokuman" target="_blank" rel="noopener">Netgsm API dokümanı</a>
                                </div>
                                <div class="row" style="margin-bottom: 10px;">
                                    <div class="col-md-6">
                                        <div style="border:1px solid #d9edf7; background:#f4fbff; border-radius:8px; padding:10px 12px; font-size:12px;">
                                            <strong><i class="fa fa-check-circle"></i> Mutlucell</strong><br>
                                            Kullanıcı adı + <strong>API key</strong> + onaylı başlık kullanın.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div style="border:1px solid #dff0d8; background:#f7fff4; border-radius:8px; padding:10px 12px; font-size:12px;">
                                            <strong><i class="fa fa-check-circle"></i> Netgsm</strong><br>
                                            API alt kullanıcı + şifre + onaylı başlık kullanın.
                                        </div>
                                    </div>
                                </div>
                                <form id="signupForm" method="post" class="form-horizontal" action="controller/function.php">
                                    <input type="hidden" name="sms_id" value="0">
                                    <div class="form-group">
                                        <label>SMS Sağlayıcı</label>
                                        <select name="sms_provider" id="sms_provider_select" class="form-control m-b">
                                            <?php $smsProvider = isset($smsprint['sms_provider']) ? strtolower((string)$smsprint['sms_provider']) : 'mutlucell'; ?>
                                            <option value="mutlucell" <?php echo $smsProvider === 'mutlucell' ? 'selected' : ''; ?>>Mutlucell</option>
                                            <option value="netgsm" <?php echo $smsProvider === 'netgsm' ? 'selected' : ''; ?>>Netgsm</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label id="sms_user_label">Kullanıcı Adı</label>
                                        <input type="text" name="sms_kullanici" id="sms_kullanici_input" value="<?php echo $smsprint['sms_kullanici']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label id="sms_pass_label">Şifre / API Key</label>
                                        <input type="password" name="sms_sifre" id="sms_sifre_input" value="<?php echo $smsprint['sms_sifre']; ?>" class="form-control form-control-rounded">
                                    </div>
                                    <div class="form-group">
                                        <label id="sms_header_label">Mesaj Başlığı (Onaylı)</label>
                                        <input type="text" name="sms_baslik" id="sms_baslik_input" value="<?php echo $smsprint['sms_baslik']; ?>" class="form-control form-control-rounded">
                                        <small class="text-muted" id="sms_header_help">Sağlayıcı panelindeki onaylı başlığı birebir girin.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>SMS Gönderimi</label>
                                        <select name="sms_durum" class="form-control m-b">
                                            <option value="1" <?php echo $smsprint['sms_durum'] == 1 ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="0" <?php echo $smsprint['sms_durum'] == 0 ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary" name="smsayarlari">Güncelle</button>
                                    </div>
                                </form>
                                <hr style="margin: 24px 0;">
                                <h4 style="font-weight:700; margin-bottom: 10px;"><i class="fa fa-paper-plane-o"></i> Test SMS Gönder</h4>
                                <p class="text-muted" style="font-size:13px; margin-bottom: 12px;">Ayarlar kaydedildikten sonra test numarasına anında doğrulama mesajı gönderir.</p>
                                <form method="post" class="form-horizontal" action="controller/function.php">
                                    <input type="hidden" name="sms_test_gonder" value="1">
                                    <div class="form-group">
                                        <label>Test Telefonu</label>
                                        <input type="text" name="sms_test_tel" class="form-control form-control-rounded" placeholder="05XXXXXXXXX veya +905XXXXXXXXX" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-outline-primary"><i class="fa fa-send"></i> Test SMS Gönder</button>
                                    </div>
                                </form>
                                <hr style="margin: 18px 0;">
                                <h4 style="font-weight:700; margin-bottom: 10px;"><i class="fa fa-plug"></i> API Bağlantı Testi (Ham Yanıt)</h4>
                                <p class="text-muted" style="font-size:13px; margin-bottom: 12px;">Seçili sağlayıcıya API isteği atar ve dönen yanıtı gösterir.</p>
                                <form method="post" class="form-horizontal" action="controller/function.php">
                                    <input type="hidden" name="sms_api_test_gonder" value="1">
                                    <div class="form-group">
                                        <label>API Test Telefonu</label>
                                        <input type="text" name="sms_api_test_tel" class="form-control form-control-rounded" placeholder="05XXXXXXXXX veya +905XXXXXXXXX" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-warning"><i class="fa fa-bolt"></i> API Test Et</button>
                                    </div>
                                </form>
                                <script>
                                (function() {
                                    var providerEl = document.getElementById('sms_provider_select');
                                    if (!providerEl) return;
                                    var userLabel = document.getElementById('sms_user_label');
                                    var passLabel = document.getElementById('sms_pass_label');
                                    var headerLabel = document.getElementById('sms_header_label');
                                    var headerHelp = document.getElementById('sms_header_help');
                                    var userInput = document.getElementById('sms_kullanici_input');
                                    var passInput = document.getElementById('sms_sifre_input');
                                    var headerInput = document.getElementById('sms_baslik_input');

                                    function updateSmsLabels() {
                                        var p = (providerEl.value || '').toLowerCase();
                                        if (p === 'netgsm') {
                                            if (userLabel) userLabel.textContent = 'Netgsm API Kullanıcı Adı';
                                            if (passLabel) passLabel.textContent = 'Netgsm API Şifre';
                                            if (headerLabel) headerLabel.textContent = 'Netgsm Mesaj Başlığı (Onaylı)';
                                            if (headerHelp) headerHelp.textContent = 'Netgsm panelindeki onaylı başlığı birebir girin.';
                                            if (userInput) userInput.placeholder = 'örn: api_alt_kullanici';
                                            if (passInput) passInput.placeholder = 'Netgsm API şifresi';
                                            if (headerInput) headerInput.placeholder = 'örn: FIRMAISMI';
                                        } else {
                                            if (userLabel) userLabel.textContent = 'Mutlucell Kullanıcı Adı';
                                            if (passLabel) passLabel.textContent = 'Mutlucell API Key';
                                            if (headerLabel) headerLabel.textContent = 'Mutlucell Mesaj Başlığı (Onaylı)';
                                            if (headerHelp) headerHelp.textContent = 'Mutlucell başlıklar ekranındaki onaylı başlığı birebir girin.';
                                            if (userInput) userInput.placeholder = 'örn: kosarelektrik';
                                            if (passInput) passInput.placeholder = 'Mutlucell API key';
                                            if (headerInput) headerInput.placeholder = 'örn: KOSAR';
                                        }
                                    }

                                    providerEl.addEventListener('change', updateSmsLabels);
                                    updateSmsLabels();
                                })();
                                </script>
                            </div>
                            <!-- FORM SON -->
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="parasut">
                        <div class="widget white-bg">
                            <div class="card-heading card-default">
                                PARAŞÜT ENTEGRASYONU (API v4)
                            </div>
                            <div class="card-block">
                                <p class="text-muted">Paraşüt geliştirici hesabından <strong>Uygulama</strong> oluşturup Client ID / Secret alın. Firma numarası ve giriş e-posta/şifre ile panelden satış faturası oluşturulur. Ayrıntılar: <a href="https://apidocs.parasut.com/" target="_blank" rel="noopener">apidocs.parasut.com</a></p>
                                <div class="alert alert-info" style="font-size: 13px;">
                                    <strong>Cari tipi:</strong> Siparişte kurumsal fatura alanlarından <em>herhangi biri</em> doluysa (vergi no, vergi dairesi, ünvan, fatura adresi) Paraşüt’e <strong>ticari (company)</strong> cari olarak gider; vergi numarası, vergi dairesi ve fatura adresi siparişteki değerlerle gönderilir. Hepsi boşsa <strong>bireysel (person)</strong> cari oluşturulur ve aşağıdaki sabit T.C./vergi numarası kullanılır (varsayılan 11 adet 1).
                                </div>
                                <form method="post" class="form-horizontal" action="controller/parasut_settings_save.php" autocomplete="off">
                                    <input type="hidden" name="parasut_ayar_kaydet" value="1">
                                    <div class="form-group">
                                        <label><input type="checkbox" name="ayar_parasut_enabled" value="1" <?php echo !empty($settingsprint['ayar_parasut_enabled']) ? 'checked' : ''; ?>> Paraşüt entegrasyonunu aç</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Firma numarası (Şirket ID)</label>
                                        <input type="text" class="form-control" name="ayar_parasut_company_id" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_company_id']) ? $settingsprint['ayar_parasut_company_id'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Örn: 123456">
                                    </div>
                                    <div class="form-group">
                                        <label>Client ID</label>
                                        <input type="text" class="form-control" name="ayar_parasut_client_id" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_client_id']) ? $settingsprint['ayar_parasut_client_id'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Client Secret</label>
                                        <input type="password" class="form-control" name="ayar_parasut_client_secret" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_client_secret']) ? $settingsprint['ayar_parasut_client_secret'] : '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Uygulama gizli anahtarı">
                                    </div>
                                    <div class="form-group">
                                        <label>Paraşüt giriş e-postası (kullanıcı adı)</label>
                                        <input type="email" class="form-control" name="ayar_parasut_username" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_username']) ? $settingsprint['ayar_parasut_username'] : '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Paraşüt şifresi</label>
                                        <input type="password" class="form-control" name="ayar_parasut_password" value="" placeholder="<?php echo !empty($settingsprint['ayar_parasut_password']) ? 'Değiştirmek için yeni şifre yazın' : 'Şifre'; ?>">
                                        <small class="text-muted">Kayıtlı şifre güvenlik için gösterilmez. Boş bırakırsanız mevcut şifre korunur.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Bireysel siparişlerde Paraşüt vergi / T.C. numarası</label>
                                        <input type="text" class="form-control" name="ayar_parasut_bireysel_vergi_no" maxlength="11" pattern="[0-9]{1,11}" inputmode="numeric" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_bireysel_vergi_no']) && $settingsprint['ayar_parasut_bireysel_vergi_no'] !== '' ? preg_replace('/[^0-9]/', '', (string) $settingsprint['ayar_parasut_bireysel_vergi_no']) : '11111111111', ENT_QUOTES, 'UTF-8'); ?>" placeholder="11111111111">
                                        <small class="text-muted">Kurumsal fatura bilgisi <strong>tamamen boş</strong> olan siparişlerde Paraşüt carisinde kullanılır (en fazla 11 rakam). Varsayılan: on bir adet <code>1</code>.</small>
                                    </div>
                                    <div class="form-group">
                                        <label><input type="checkbox" name="ayar_parasut_kdv_dahil" value="1" <?php echo (!isset($settingsprint['ayar_parasut_kdv_dahil']) || (int) $settingsprint['ayar_parasut_kdv_dahil'] === 1) ? 'checked' : ''; ?>> Sipariş tutarı sitede KDV dahildir (brüt)</label>
                                        <small class="text-muted d-block">Açıkken sipariş tutarı brüt kabul edilir; Paraşüt’e <strong>KDV hariç birim fiyat</strong> (brüt ÷ (1+KDV/100)) ve aşağıdaki <strong>KDV oranı</strong> gönderilir — örn. 2.999₺ ve %20 → birim ≈ 2.499,17₺, satırda KDV %20, genel toplam ≈ 2.999₺. Kapatırsanız sipariş tutarı zaten KDV hariç kabul edilir, aynı oran üstüne eklenir.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>KDV oranı (satırda, %)</label>
                                        <input type="text" class="form-control" name="ayar_parasut_vat_rate" value="<?php echo htmlspecialchars(isset($settingsprint['ayar_parasut_vat_rate']) ? (string) $settingsprint['ayar_parasut_vat_rate'] : '0', ENT_QUOTES, 'UTF-8'); ?>" placeholder="0 veya 10, 20">
                                        <small class="text-muted">KDV dahil modunda brüt tutarın içinden bu oranla ayrıştırma yapılır; KDV hariç modunda bu oran birim fiyatın üzerine eklenir.</small>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Paraşüt ayarlarını kaydet</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <script>
                    function removeImage(type) {
                        if (confirm('Bu görseli kalıcı olarak silmek istediğinize emin misiniz?')) {
                            document.getElementById('sil_' + type).value = '1';
                            document.getElementById(type + '_preview_img').style.display = 'none';
                            // Hide the delete button itself
                            var btn = event.currentTarget || event.target.closest('button');
                            if (btn) btn.style.display = 'none';
                            
                            $.toast({
                                heading: 'Bilgi',
                                text: 'Görsel işaretlendi. Ayarları kaydettiğinizde silinecektir.',
                                position: 'top-right',
                                loaderBg:'#ff6849',
                                icon: 'info',
                                hideAfter: 3500,
                                stack: 6
                            });
                        }
                    }
                    </script>
                    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'parasut') { ?>
                    <script>$(function () { $('a[href="#parasut"]').tab('show'); });</script>
                    <?php } ?>
                    <?php if (isset($_GET['tab']) && $_GET['tab'] === 'mail') { ?>
                    <script>$(function () { $('a[href="#mail"]').tab('show'); });</script>
                    <?php } ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!--                        Content End                             -->
    <!-- ============================================================== -->
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
