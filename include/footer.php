<?php 
$urunler = @$_SESSION['urunler'];
if (!isset($whatsappprint) || !is_array($whatsappprint)) {
    $whatsappprint = array(
        'whats_tiklaaradurum' => 0,
        'whats_durum'         => 0,
        'whats_tiklaara'      => '',
        'whats_tel'           => '',
    );
}
if (!isset($motorprint) || !is_array($motorprint)) {
    $motorprint = array('motor_analitik' => '', 'motor_gonay' => '', 'motor_yonay' => '');
}
?>
<style>
  .btn-quick-action { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important; }
  .btn-quick-action:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important; }
  .page-link-item { transition: all 0.2s ease !important; border-left: 3px solid transparent !important; }
  .page-link-item:hover { background: #fff !important; padding-left: 20px !important; border-left-color: var(--renk1) !important; color: var(--renk1) !important; }
  /* Sol sabit aksiyonlar: sadece ikon (FAB) */
  #quick-actions-container { align-items: flex-start; }
  #quick-actions-container .btn-quick-action--fab {
    width: 48px !important;
    height: 48px !important;
    min-width: 48px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 50% !important;
    margin-bottom: 8px !important;
    font-size: 18px !important;
    line-height: 1 !important;
    box-sizing: border-box !important;
  }
  #quick-actions-container .btn-quick-action--fab i { margin: 0 !important; }
  @media (max-width: 768px) {
    #quick-actions-container .btn-quick-action--fab {
      width: 40px !important;
      height: 40px !important;
      min-width: 40px !important;
      font-size: 15px !important;
      margin-bottom: 6px !important;
    }
  }
</style>

<?php if (!isset($form_slug) && !isset($sayfacek) && basename($_SERVER['PHP_SELF']) != 'siparis-onay.php') { // Form, Sayfalar ve Sipariş Onayda gizle ?>
<div id="quick-actions-container" style='position: fixed; bottom: <?php echo (!isset($settingsprint['ayar_siparis_bar']) || $settingsprint['ayar_siparis_bar'] == 1) ? '90px' : '20px'; ?>; left: 20px; z-index: 2147483646; max-width: min(280px, calc(100vw - 40px)); display: flex; flex-direction: column; touch-action: manipulation; -webkit-transform: translateZ(0); transform: translateZ(0); pointer-events: auto;'>
  <style>
    @media (max-width: 768px) {
      #quick-actions-container {
        /* bottom is handled by index.php global override (85px) */
        left: 10px !important;   /* Force LEFT */
        right: auto !important;
        max-width: min(280px, calc(100vw - 24px)) !important;
        z-index: 2147483646 !important;
        touch-action: manipulation;
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
        pointer-events: auto !important;
      }
    }
  </style>
  <!-- Sipariş Sorgula Modülü -->
  <?php if (isset($settingsprint['ayar_sorgula_on']) && $settingsprint['ayar_sorgula_on'] == 1) { ?>
  <div id="siparis-sorgula-container" style="margin-bottom: 10px; display: none; background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 10px 35px rgba(0,0,0,0.3); width: 280px; border: 2px solid var(--renk2); position: relative;">
    <button type="button" onclick="$('#siparis-sorgula-container').slideUp();" style="position: absolute; top: 5px; right: 8px; border: none; background: none; font-size: 18px; color: #999; cursor: pointer;">&times;</button>
    <h5 style="margin-top: 0; font-weight: 800; font-size: 14px; color: #333; border-bottom: 2px solid var(--renk2); padding-bottom: 8px; margin-bottom: 12px;">SİPARİŞ SORGULA</h5>
    <div class="form-group" style="margin-bottom: 10px;">
        <input type="text" id="sorgu_tel" class="form-control" placeholder="Telefon numaranız (05...)" style="height: 40px; font-size: 13px; border-radius: 8px; border: 1px solid #ddd; padding: 0 10px; width: 100%;">
    </div>
    <button type="button" onclick="siparisSorgulaBtn()" class="btn btn-block" style="background: var(--renk2); color: #fff; font-weight: 700; border-radius: 8px; height: 40px; width: 100%; border: none;">Sorgula</button>
    <div id="sorgu_sonuc" style="margin-top: 12px; font-size: 12px; display: none; padding: 10px; border-radius: 8px;"></div>
  </div>

  <button type="button" onclick="$('#siparis-sorgula-container').slideToggle();" class="btn btn-xl btn-quick-action btn-quick-action--fab" style="background: linear-gradient(135deg, #1e3799 0%, #0c2461 100%); border: none; color: #fff; box-shadow: 0 4px 15px rgba(30, 55, 153, 0.4);" title="Sipariş sorgula" aria-label="Sipariş sorgula">
    <i class="fa fa-search" aria-hidden="true"></i>
  </button>
  <?php } ?>


  <?php if (isset($settingsprint['ayar_carkifelek_on']) && $settingsprint['ayar_carkifelek_on'] == 1) { ?>
  <button type="button" onclick="openCarkifelekModal()" class="btn btn-xl btn-quick-action btn-quick-action--fab" style="background: linear-gradient(135deg, <?php echo isset($settingsprint['ayar_carkifelek_renk1']) ? $settingsprint['ayar_carkifelek_renk1'] : '#ff6b6b'; ?> 0%, <?php echo isset($settingsprint['ayar_carkifelek_renk2']) ? $settingsprint['ayar_carkifelek_renk2'] : '#ee5a6f'; ?> 100%); border: none; color: #fff; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);" title="Çarkifelek çevir" aria-label="Çarkifelek çevir">
    <i class="fa fa-gift" aria-hidden="true"></i>
  </button>
  <?php } ?>
  <?php
  $wa_tel_digits = isset( $whatsappprint['whats_tel'] ) ? preg_replace( '/\D+/', '', (string) $whatsappprint['whats_tel'] ) : '';
  $wa_tikla_digits = isset( $whatsappprint['whats_tiklaara'] ) ? preg_replace( '/\D+/', '', (string) $whatsappprint['whats_tiklaara'] ) : '';
  ?>
  <?php if ( ! empty( $whatsappprint['whats_tiklaaradurum'] ) && $wa_tikla_digits !== '' ) { ?>
    <a class="btn btn-xl btn-quick-action btn-quick-action--fab" href="tel:<?php echo htmlspecialchars( $whatsappprint['whats_tiklaara'], ENT_QUOTES, 'UTF-8' ); ?>" target="_blank" rel="noopener" style="background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%); border: none; color: #fff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4); text-decoration: none;" title="Telefonla sipariş" aria-label="Telefonla sipariş">
      <i class="fa fa-phone" aria-hidden="true"></i>
    </a>
  <?php } ?>
  <?php if ( ! empty( $whatsappprint['whats_durum'] ) && $wa_tel_digits !== '' ) { ?>
    <a class="btn btn-xl btn-quick-action btn-quick-action--fab" href="https://api.whatsapp.com/send?phone=90<?php echo htmlspecialchars( $wa_tel_digits, ENT_QUOTES, 'UTF-8' ); ?>" target="_blank" rel="noopener" style="background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); border: none; color: #fff; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4); text-decoration: none;" title="WhatsApp ile sipariş" aria-label="WhatsApp ile sipariş">
      <i class="fa fa-whatsapp" aria-hidden="true"></i>
    </a>
  <?php } ?>
</div>
<?php } ?>
<?php 
// Pixel ve Analitik kodları için dinamik veri yerleştirme (ViewContent vb.)
$analitik_codes = isset($motorprint['motor_analitik']) ? (string) $motorprint['motor_analitik'] : '';

// Ana sayfada görüntülenen ilk ürünün fiyatını veritabanından çek
// Not: Bu ViewContent eventi için kullanılır; siparis-onay.php kendi fiyatını ayrıca yönetir.
$current_price = 0;
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    try {
        $pixel_urun_sor = $db->query("SELECT urun_fiyat FROM urunler ORDER BY urun_siralama ASC, urun_id ASC LIMIT 1");
        if ($pixel_urun_sor) {
            $pixel_urun = $pixel_urun_sor->fetch(PDO::FETCH_ASSOC);
            if ($pixel_urun) {
                $current_price = intval(floatval($pixel_urun['urun_fiyat']));
            }
        }
    } catch (Exception $e) { $current_price = 0; }
}

$pixel_price = number_format($current_price, 2, '.', ''); // Facebook formatı: 6199.00

$analitik_codes = str_replace(
    array('{tutar}', '{currency}'),
    array($pixel_price, 'TRY'),
    $analitik_codes
);

echo $analitik_codes;
?>

<script>window.PANEL_SITE_URL = <?php echo json_encode(SITE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="<?php echo SITE_URL; ?>js/plugins.js"></script>
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/functions.js"></script>
<script src="<?php echo SITE_URL; ?>xnull/assets/lib/sweet-alerts2/sweetalert2.min.js"></script>
<script src="<?php echo SITE_URL; ?>xnull/assets/lib/lightbox2/dist/js/lightbox.min.js"></script>
<script>

function siparisSorgulaBtn() {
    var tel = $('#sorgu_tel').val();
    if (!tel) {
        swal("Hata", "Lütfen telefon numaranızı giriniz.", "error");
        return;
    }
    
    $('#sorgu_sonuc').fadeOut().html('<i class="fa fa-spinner fa-spin"></i> Sorgulanıyor...');
    
    $.ajax({
        type: 'POST',
        url: 'js/ajax/siparisSorgula.php',
        data: {tel: tel},
        dataType: 'json',
        success: function(res) {
            $('#sorgu_sonuc').fadeIn();
            if (res.status == 'success') {
                $('#sorgu_sonuc').css('background', '#e8f5e9').css('color', '#2e7d32');
                $('#sorgu_sonuc').html(
                    '<strong>Sayın ' + res.customer + ',</strong><br>' +
                    'Durum: <span style="font-weight:800; text-transform:uppercase;">' + res.order_status + '</span><br>' +
                    'Tarih: ' + res.date
                );
            } else {
                $('#sorgu_sonuc').css('background', '#ffebee').css('color', '#c62828');
                $('#sorgu_sonuc').html(res.message);
            }
        },
        error: function() {
            swal("Hata", "Bir hata oluştu, lütfen tekrar deneyiniz.", "error");
        }
    });
}

// Lightbox2 — yoksa ReferenceError tüm footer script'ini öldürüyordu (beyaz ekran)
if (typeof lightbox !== 'undefined' && lightbox && typeof lightbox.option === 'function') {
lightbox.option({
    'resizeDuration': 200,
    'wrapAround': true,
    'fadeDuration': 300,
    'imageFadeDuration': 300,
    'showImageNumberLabel': true,
    'alwaysShowNavOnTouchDevices': true,
    'fitImagesInViewport': true,
    'positionFromTop': 50,
    'disableScrolling': true
});
}

// ESC tuşu ile kapatma (lightbox2 zaten destekliyor ama ekstra güvenlik için)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        var lb = document.querySelector('#lightbox');
        if (lb && lb.style.display !== 'none' && lb.style.display !== '' && typeof lightbox !== 'undefined' && lightbox.end) {
            lightbox.end();
        }
    }
    // Ok tuşları ile gezinme (lightbox2 zaten destekliyor)
    if (e.key === 'ArrowLeft' || e.keyCode === 37) {
        var lbNav = document.querySelector('#lightbox .lb-prev');
        if (lbNav && lbNav.style.display !== 'none') {
            lbNav.click();
        }
    }
    if (e.key === 'ArrowRight' || e.keyCode === 39) {
        var lbNav = document.querySelector('#lightbox .lb-next');
        if (lbNav && lbNav.style.display !== 'none') {
            lbNav.click();
        }
    }
});

// Boşluğa (overlay'e) tıklayınca kapatma
$(document).on('click', '#lightboxOverlay', function(e) {
    if (e.target === this && typeof lightbox !== 'undefined' && lightbox.end) {
        lightbox.end();
    }
});
</script>
<style>
/* Lightbox2 özelleştirmeleri */
#lightboxOverlay {
    cursor: pointer;
}
#lightbox .lb-close {
    cursor: pointer;
    z-index: 9999;
}
#lightbox .lb-nav a.lb-prev,
#lightbox .lb-nav a.lb-next {
    cursor: pointer;
}
</style>          
<?php if (@$_GET['status']=='ok') { ?>
<script>
  $(document).ready(function () {
    swal({
      title: "Sipariş Tamamlandı!",
      html: "Siparişiniz başarıyla alındı!<br>Siparişiniz ilk iş gününde kargoya verilecektir.",
      icon: "success",
      showConfirmButton: false
    });
  });
</script>

<?php  
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"])){
  if($_SERVER["HTTPS"] == "on"){
    $sayfaURL .= "s";
  }
}
$hesapa=$db->prepare("SELECT * from smenu where smenu_id=11");
$hesapa->execute();
$hesapprinta=$hesapa->fetch(PDO::FETCH_ASSOC);

$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
?>
<meta http-equiv="refresh" content="7; URL=<?php echo substr($sayfaURL,0, -10);?>">
<?php

} elseif (@$_GET['status']=='no') {?>
  <script>
   $(document).ready(function () {
    swal({
      title: "BİLGİ!",
      text: "Siparişiniz bulunmaktadır. Lütfen teslim olmasını bekleyiniz.",
      type: "info",
      showConfirmButton: false,
      timer: '5000'
    });
  });
</script>
<?php  
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"])){
  if($_SERVER["HTTPS"] == "on"){
    $sayfaURL .= "s";
  }
}
$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; ?>
<meta http-equiv="refresh" content="5; URL=<?php echo substr($sayfaURL,0, -10);?>">
<?php }  elseif (@$_GET['bos']=='no') {?>
  <script>
   $(document).ready(function () {
    swal({
      title: "HATA!",
      text: "Lütfen tüm alanları eksiksiz doldurunuz.",
      type: "error",
      showConfirmButton: false,
      timer: '5000'
    });
  });
</script>
<?php  
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"])){
  if($_SERVER["HTTPS"] == "on"){
    $sayfaURL .= "s";
  }
}
$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; ?>
<meta http-equiv="refresh" content="5; URL=<?php echo substr($sayfaURL,0, -16);?>">
<?php } elseif (@$_GET['demo']=='ok') {?>
  <script>
   $(document).ready(function () {
    swal({
      title: "OoPs!",
      text: "Demo modda bu işleme izin verilmiyor.<br />",
      type: "warning",
      showConfirmButton: false,
      timer: '6000'
    });
  });
</script>
<?php  
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"])){
  if($_SERVER["HTTPS"] == "on"){
    $sayfaURL .= "s";
  }
}
$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; ?>
<meta http-equiv="refresh" content="6; URL=<?php echo substr($sayfaURL,0, -8);?>">
<?php } elseif (@$_GET['status']=='cookie_blocked') {?>
  <script>
   $(document).ready(function () {
    swal({
      title: "SİPARİŞİNİZ BULUNMAKTADIR!",
      text: "Daha önceden vermiş olduğunuz bir sipariş tespit edildiği için mükerrer sipariş verilmesi engellenmiştir.",
      type: "info",
      showConfirmButton: false,
      timer: '6000'
    });
  });
</script>
<?php  
$sayfaURL = "http";
if(isset($_SERVER["HTTPS"])){
  if($_SERVER["HTTPS"] == "on"){
    $sayfaURL .= "s";
  }
}
$sayfaURL .= "://";
$sayfaURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]; ?>
<meta http-equiv="refresh" content="6; URL=<?php echo substr($sayfaURL,0, -25);?>">
<?php } ?>
<script>
  function urunSecenekSyncSwatches($root) {
    var $ctx = ($root && $root.length) ? $root : $(document);
    $ctx.find('.urun-secenek-field').each(function() {
      var $g = $(this);
      var $sel = $g.find('select.urun-secenek-native-select');
      if (!$sel.length) return;
      var si = parseInt($sel.prop('selectedIndex'), 10) || 0;
      $g.find('.urun-secenek-swatch').removeClass('is-selected');
      if (si > 0) {
        $g.find('.urun-secenek-swatch[data-opt-idx="' + (si - 1) + '"]').addClass('is-selected');
      }
    });
  }
  $(document).on('click', '.urun-secenek-swatch', function(ev) {
    ev.preventDefault();
    var $g = $(this).closest('.urun-secenek-field');
    var idx = parseInt($(this).attr('data-opt-idx'), 10);
    if (isNaN(idx)) return;
    var $sel = $g.find('select.urun-secenek-native-select');
    var want = idx + 1;
    if ($sel.find('option').length > want) {
      $sel.prop('selectedIndex', want).trigger('change');
    }
  });
  $(document).on('change', '.urun-secenek-native-select', function() {
    urunSecenekSyncSwatches($(this).closest('#urun_secenek_alani').length ? $(this).closest('#urun_secenek_alani') : $(document));
  });
  $(function() {
    if ($('#urun_secenek_alani').find('.urun-secenek-native-select').length) {
      urunSecenekSyncSwatches($('#urun_secenek_alani'));
    }
  });
  // index.php ana sipariş formu `.product-item` ile kendi AJAX + scroll kullanıyor.
  // Buradaki `.product` handler da bağlanırsa çift istek + çift scroll (800ms vs 160ms) çakışır → 2. üründe zıplama / üste atlama hissi.
  $(function() {
    if ($('#urun_secenek_alani').length && $('.product-item').length) {
      return; // Ana sayfa sipariş akışı index.php'de
    }

    $.ajax({
      url: '<?php echo SITE_URL; ?>urun-secenek-getir.php',
      type: 'POST',
      dataType: 'html',
      data: {urun_id: $('#urun').attr('data-id')},
      success: function (result) {
        if($.trim(result) != ''){
          $('#urun_secenek_alani').html('').html(result);
          urunSecenekSyncSwatches($('#urun_secenek_alani'));
        } else {
          $('#urun_secenek_alani').html('');
        }
      },
      error: function (result) {
        alert('Ürün seçenekleri getirilirken hata oluştu');
      }
    });

    $('.product').not('.product-item').click(function(){
    var urun_id = $(this).attr('data-id');
    if (!urun_id) return;

    $('.product').removeClass('selected');
    $(this).addClass('selected');

    $('.product > .select').hide();
    $('.product > .no-select').show();
    $(this).find('.no-select').hide();
    $(this).find('.select').show();
    $('#urun').val($(this).data('value') + '|' + $(this).data('fiyat'));

    $.ajax({
      url: '<?php echo SITE_URL; ?>urun-secenek-getir.php',
      type: 'POST',
      dataType: 'html',
      data: {urun_id: urun_id},
      success: function (result) {
        if($.trim(result) != ''){
          $('#urun_secenek_alani').html('').html(result);
          urunSecenekSyncSwatches($('#urun_secenek_alani'));
        } else {
          $('#urun_secenek_alani').html('');
        }
        scrollToPayment();
      },
      error: function (result) {
        alert('Ürün seçenekleri getirilirken hata oluştu');
      }
    });
    });
  });

// Ürün tıklandığında ürün seçeneklerine veya ödeme yöntemine scroll et
function scrollToPayment(e) {
  if (e) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  // Ürün seçenekleri alanını kontrol et
  var urunSecenekAlani = $('#urun_secenek_alani');
  var hasContent = urunSecenekAlani.length > 0 && urunSecenekAlani.html().trim() !== '';
  
  var targetElement = null;
  var scrollOffset = 100;
  
  if (hasContent) {
    // Ürün seçenekleri varsa, oraya scroll et
    targetElement = urunSecenekAlani;
  } else {
    // Ürün seçenekleri yoksa, ödeme yöntemine scroll et
    var odemeLabel = $('#odeme-yontemi-label');
    if (odemeLabel.length > 0) {
      targetElement = odemeLabel;
    } else {
      // Label bulunamazsa, siparis formuna scroll et
      targetElement = $('#siparis');
    }
  }
  
  if (targetElement && targetElement.length > 0) {
    var offset = targetElement.offset();
    if (offset && isFinite(offset.top)) {
      var st = Math.max(0, offset.top - scrollOffset);
      $('html, body').stop(true).animate({
        scrollTop: st
      }, 800);
    } else {
      setTimeout(function() {
        var offset2 = targetElement.offset();
        if (offset2 && isFinite(offset2.top)) {
          var st2 = Math.max(0, offset2.top - scrollOffset);
          $('html, body').stop(true).animate({
            scrollTop: st2
          }, 800);
        }
      }, 100);
    }
  } else {
    // Element bulunamazsa, sayfanın yüklenmesini bekle
    setTimeout(function() {
      var urunSecenekAlani = $('#urun_secenek_alani');
      var hasContent = urunSecenekAlani.length > 0 && urunSecenekAlani.html().trim() !== '';
      
      var targetElement = null;
      if (hasContent) {
        targetElement = urunSecenekAlani;
      } else {
        var odemeLabel = $('#odeme-yontemi-label');
        if (odemeLabel.length > 0) {
          targetElement = odemeLabel;
        } else {
          targetElement = $('#siparis');
        }
      }
      
      if (targetElement && targetElement.length > 0) {
        var offset = targetElement.offset();
        if (offset && isFinite(offset.top)) {
          $('html, body').stop(true).animate({
            scrollTop: Math.max(0, offset.top - scrollOffset)
          }, 800);
        }
      }
    }, 200);
  }
}

<?php if (@$_GET['yorum']=='ok') { ?>
$(document).ready(function () {
    
    var title = "BİLGİ!";
    var text = "Yorumunuz başarıyla alındı! Editör onayından sonra yayınlanacaktır. Teşekkür ederiz.";
    
    if (typeof swal === 'function') {
        swal({
            title: title,
            text: text,
            icon: "success",
            type: "success", /* Hem icon hem type ekleyelim v1/v2 uyumu için */
            confirmButtonText: "Tamam"
        });
    } else if (typeof Swal === 'function') {
        Swal.fire({
            title: title,
            text: text,
            icon: "success",
            confirmButtonText: "Tamam"
        });
    } else {
        alert(text);
    }
});
<?php } ?>
</script>
<style>
/* SweetAlert Z-Index Override to appear above floating buttons */
.swal-overlay, .swal2-container, .sweet-alert, .swal2-popup {
  z-index: 2147483647 !important;
}
</style>
<?php
// NOT: </body></html> burada OLMAMALI — sayfa şablonu (#wrapper sonrası) kapatır.
// Erken kapanış, sayfa.php / form-goruntule vb. içinde çift </html> ve boş ekran üretiyordu.
