<footer class="footer">
	<span style="font-size: 13px; color: #333;">
		Copyright &copy; <?php echo date('Y'); ?> 
		<a href="#" style="color: #000; text-decoration: none; font-weight: bold; font-family: 'Courier New', monospace; letter-spacing: 0.5px;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='#000'">
			<i class="fa fa-code"></i> phxcore0
		</a>
	</span>
</footer>
</section>
<!-- ============================================================== -->
<!-- 						Content End		 						-->
<!-- ============================================================== -->
<!-- Common Plugins -->
<script src="assets/lib/bootstrap/js/popper.min.js"></script>
<script src="assets/lib/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/lib/pace/pace.min.js"></script>
<script src="assets/lib/jasny-bootstrap/js/jasny-bootstrap.min.js"></script>
<!-- <script src="assets/lib/slimscroll/jquery.slimscroll.min.js"></script> -->
<script src="assets/lib/nano-scroll/jquery.nanoscroller.min.js"></script>
<script src="assets/lib/metisMenu/metisMenu.min.js"></script>
<script src="assets/js/custom.js"></script>
<script src="assets/dropzone/dropzone.js"></script>

<!--Chart Script-->
<script src="assets/lib/chartJs/Chart.min.js"></script>

<!--Vetor Map Script-->
<script src="assets/lib/vectormap/jquery-jvectormap-2.0.2.min.js"></script>
<script src="assets/lib/vectormap/jquery-jvectormap-world-mill-en.js"></script>

<!-- Datatables-->
<script src="assets/lib/datatables/dataTables.responsive.min.js"></script>
<script src="assets/js/jscolor.js"></script>

<script>
// MOBİL MENÜ KONTROLÜ (Gelişmiş & Çakışmasız Versiyon)
$(document).ready(function() {
    function isMobile() { return window.innerWidth <= 991; }
    
    // Overlay oluştur (Bir kez)
    if ($('#sidebar-overlay').length === 0) {
        $('body').append('<div id="sidebar-overlay" style="display: none; pointer-events: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; cursor: pointer; -webkit-tap-highlight-color: transparent;"></div>');
    }

    // Menü açma/kapama tetikleyicisi
    $(document).on('click', '.nav-collapse, .nav-collapsed, .left-nav-toggle', function(e) {
        if (!isMobile()) return;
        
        setTimeout(function() {
            if ($('body').hasClass('nav-toggle') || $('body').hasClass('nav-collapsed')) {
                $('#sidebar-overlay').css('pointer-events', 'auto').fadeIn(300);
            } else {
                $('#sidebar-overlay').fadeOut(300, function () { $(this).css('pointer-events', 'none'); });
            }
        }, 150);
    });

    // Boşluğa veya Overlay'e tıklayınca KAPAT
    $(document).on('click', function(e) {
        if (!isMobile()) return;
        
        var $target = $(e.target);
        if ($('body').hasClass('nav-toggle') || $('body').hasClass('nav-collapsed')) {
            // Eğer tıklanan yer sidebar veya toggle butonları DEĞİLSE kapat
            if (!$target.closest('.main-sidebar-nav').length && 
                !$target.closest('.left-nav-toggle').length &&
                !$target.closest('.nav-collapse').length ||
                $target.is('#sidebar-overlay')) {
                
                $('body').removeClass('nav-toggle nav-collapsed');
                $('.nav-collapse').removeClass('open');
                $('#sidebar-overlay').fadeOut(300, function () { $(this).css('pointer-events', 'none'); });
            }
        }
    });

    // Ekran boyutu değişince temizle
    $(window).on('resize', function() {
        if (!isMobile()) {
            $('#sidebar-overlay').hide().css('pointer-events', 'none');
        }
    });
});
</script>
<script>
  $(document).ready(function () {
    // Sadece tablo varken başlat — yoksa JS hatası tüm footer script'lerini (mobil kaydırma vb.) kırar
    if ($('.datatable-basic').length && $.fn.DataTable) {
      $('.datatable-basic').each(function () {
        if (!$.fn.DataTable.isDataTable(this)) {
          $(this).DataTable({
            language: {
              url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
            }
          });
        }
      });
    }
  });
</script>
<script>
  // Çerez Sıfırlama Fonksiyonu - Global olarak tanımla
  window.clearAllCookies = function() {
      var config = {
          title: "Emin misiniz?",
          text: "Tüm çerezler ve oturum bilgileri silinecektir. Bu işlem geri alınamaz!",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#f96262",
          confirmButtonText: "Evet, Temizle!",
          cancelButtonText: "Vazgeç",
          closeOnConfirm: false
      };

      var action = function(result) {
          // Hibrit kontrol: Hem v1 (result === true) hem v2 (result.value / result.isConfirmed)
          var confirmed = (result === true || (result && (result.value === true || result.isConfirmed)));
          
          if (confirmed) {
              try {
                  // Tüm çerezleri al
                  var cookies = document.cookie.split(";");
                  
                  // Her çerezi sil (daha agresif temizlik)
                  for (var i = 0; i < cookies.length; i++) {
                      var cookie = cookies[i];
                      var eqPos = cookie.indexOf("=");
                      var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
                      
                      if (name) {
                          // Farklı path ve domain kombinasyonlarını dene
                          var paths = ['/', window.location.pathname, '/xnull/'];
                          var domains = [window.location.hostname, "." + window.location.hostname, ""];
                          
                          paths.forEach(function(p) {
                              domains.forEach(function(d) {
                                  var cookieStr = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=" + p;
                                  if (d) cookieStr += ";domain=" + d;
                                  document.cookie = cookieStr;
                              });
                          });
                      }
                  }
                  
                  // LocalStorage ve SessionStorage'ı da temizle
                  try {
                      localStorage.clear();
                      sessionStorage.clear();
                  } catch(e) {
                      
                  }
                  
                  swal({
                      title: "TEMİZLENDİ!",
                      text: "Tüm çerezler başarıyla temizlendi. Sayfa yenileniyor...",
                      type: "success",
                      timer: 2000,
                      showConfirmButton: false
                  });
                  
                  setTimeout(function() {
                      location.reload(true);
                  }, 2000);
              } catch(e) {
                  console.error('Çerez temizleme hatası:', e);
                  swal("Hata!", "Çerez temizleme sırasında bir hata oluştu: " + e.message, "error");
              }
          }
      };

      // Hem callback hem promise desteği (Hibrit yapı)
      var swalResult = swal(config, action);
      if (swalResult && swalResult.then) {
          swalResult.then(action);
      }
  };
</script>
<script>
	$(document).ready(function () {
		if (!$.fn.DataTable) {
			return;
		}
		var dtLang = {
			"sDecimal":        ",",
			"sEmptyTable":     "Tabloda herhangi bir veri mevcut değil",
			"sInfo":           "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
			"sInfoEmpty":      "Kayıt yok",
			"sInfoFiltered":   "(_MAX_ kayıt içerisinden bulunan)",
			"sInfoPostFix":    "",
			"sInfoThousands":  ".",
			"sLengthMenu":     "Sayfada _MENU_ kayıt göster",
			"sLoadingRecords": "Yükleniyor...",
			"sProcessing":     "İşleniyor...",
			"sSearch":         "Ara:",
			"sZeroRecords":    "Eşleşen kayıt bulunamadı",
			"oPaginate": {
				"sFirst":    "İlk",
				"sLast":     "Son",
				"sNext":     "Sonraki",
				"sPrevious": "Önceki"
			}
		};
		var $dt1 = $('#datatable1');
		if ($dt1.length && !$.fn.DataTable.isDataTable($dt1)) {
			var dtOpts = {
				paging: true,
				pageLength: 100,
				lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "Tümü"]],
				language: dtLang
			};
			if ($dt1.closest('#urun-toplu-form').length) {
				dtOpts.columnDefs = [{ orderable: false, targets: 0 }, { searchable: false, targets: 0 }];
			}
			$dt1.DataTable(dtOpts);
		}
		var $dtCl = $('#datatable_cloaker_log');
		if ($dtCl.length && !$.fn.DataTable.isDataTable($dtCl)) {
			$dtCl.DataTable({
				paging: true,
				pageLength: 50,
				order: [[1, 'desc']],
				lengthMenu: [[25, 50, 100, 250, -1], [25, 50, 100, 250, "Tümü"]],
				language: dtLang,
				columnDefs: [{ orderable: false, searchable: false, targets: 0 }]
			});
		}
	});
</script>
<!-- Summernote -->
<script src="assets/lib/summernote/summernote.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.summernote').summernote({
			height:'300px',
		});
	});
</script>
<script>
	if (typeof CKEDITOR !== 'undefined') {
		CKEDITOR.replace( 'editor' );
	}
</script>
<!--Sweet Alerts-->
<script src="assets/lib/sweet-alerts2/sweetalert2.min.js"></script>          
<?php if (@$_GET['status']=='ok') { ?>
    <script>
        $(document).ready(function () {
            swal({
                title: "TAMAMLANDI!",
                text: "İşlem başarılı bir şekilde tamamlandı",
                type: "success",
                timer: '1000',
                showConfirmButton: false
            });
            // URL'deki ?status=ok kısmını sayfa yenilenmeden temizle
            if (window.history.replaceState) {
                var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path:url}, '', url);
            }
        });
    </script>
<?php } elseif (@$_GET['status']=='no') {?>
    <script>
        $(document).ready(function () {
            swal({
                title: "HATA!",
                text: "İşlem sırasında bir hata oluştu.",
                type: "error",
                showConfirmButton: false,
                timer: '1000'
            });
            // URL'deki ?status=no kısmını sayfa yenilenmeden temizle
            if (window.history.replaceState) {
                var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path:url}, '', url);
            }
        });
    </script>
<?php } elseif (@$_GET['status']=='eksik') {?>
    <script>
        $(document).ready(function () {
            swal({
                title: "RESİM SEÇMEDİNİZ!",
                text: "En az bir tane arkaplan seçiniz.",
                type: "error",
                showConfirmButton: false,
                timer: '2000'
            });
            // URL'deki ?status=eksik kısmını sayfa yenilenmeden temizle
            if (window.history.replaceState) {
                var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path:url}, '', url);
            }
        });
    </script>
<?php } ?>
<!-- Mobil: içerik ortasında kaydırma — .main-content tek scroll kökü (sayfa içi stillerden sonra) -->
<style id="xnull-admin-mobile-scroll-final">
@media screen and (max-width: 991px) {
  html {
    height: 100% !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
    overflow: hidden !important;
  }
  body {
    height: 100% !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
    overflow: hidden !important;
    position: relative !important;
  }
  body.nav-toggle,
  body.nav-collapsed {
    overflow: hidden !important;
    height: 100% !important;
    height: 100dvh !important;
    max-height: 100dvh !important;
  }
  section.main-content.container,
  .main-content.container {
    height: 100dvh !important;
    height: 100vh !important;
    max-height: 100dvh !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch !important;
    box-sizing: border-box !important;
    min-height: 0 !important;
  }
  .siparis-scroll-main,
  .siparis-scroll-top {
    touch-action: pan-x pan-y;
    -webkit-overflow-scrolling: touch;
  }
}

@media screen and (min-width: 992px) {
  html {
    height: auto !important;
    max-height: none !important;
    overflow-x: hidden !important;
  }
  body {
    height: auto !important;
    max-height: none !important;
    overflow-x: hidden !important;
    overflow-y: auto;
  }
  section.main-content.container,
  .main-content.container {
    height: auto !important;
    max-height: none !important;
    min-height: 100vh !important;
    overflow-x: hidden !important;
    overflow-y: visible !important;
    -webkit-overflow-scrolling: auto !important;
  }
}
</style>
</body>
</html>
