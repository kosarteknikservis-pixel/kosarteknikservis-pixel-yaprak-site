// XNULL Custom Scripts


$(document).ready(function () {

    // ---------------------------------------------------------
    // 1. Ürün Seçim ve Kaydırma (Product Click & Scroll)
    // ---------------------------------------------------------
    // Görsele veya kapsayıcıya tıklayınca çalışır
    $(document).on('click', '.product', function (e) {
        e.preventDefault();
        e.stopPropagation();

        

        var target = $(this);
        var id = target.attr('data-id');
        var baslik = target.attr('data-value');
        var fiyat = target.attr('data-fiyat');

        // Görsel Değişimi (Normal <-> Seçili)
        // Tüm ürünlerde normali göster, seçiliyi gizle
        $('.product .no-select').show();
        $('.product .select').hide();

        // Tıklanan üründe normali gizle, seçiliyi göster
        target.find('.no-select').hide();
        target.find('.select').show();

        // Gizli inputu güncelle
        $('#urun').val(baslik + '|' + fiyat);

        // Seçenekleri Getir
        // Base URL'i dinamik olarak al (sayfa/ gibi alt dizinlerden de çalışsın)
        var baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
        if (!baseUrl.endsWith('/')) baseUrl += '/';

        $.ajax({
            type: 'POST',
            url: baseUrl.replace(/\/sayfa\/.*$/, '/') + 'urun-secenek-getir.php',
            data: { id: id },
            success: function (data) {
                $('#urun_secenek_alani').html(data);
                /* Kaydırma: ana sayfa index.php .product-item akışında yok; çift scroll önlenir */
            },
            error: function (err) {
                console.error('Ajax hatası:', err);
            }
        });

        return false;
    });

    // ---------------------------------------------------------
    // 2. Mobil Menü Otomatik Kapanma (Mobile Menu Auto-Close)
    // ---------------------------------------------------------
    $(document).on('click', function (e) {
        if ($(window).width() <= 768) {
            var $target = $(e.target);
            var $menu = $('.main-sidebar-nav');
            var $toggle = $('.left-nav-toggle, .nav-collapse');

            // Menü açıksa ve tıklanan yer menü veya toggle butonu değilse
            // Ekstra kontrol: body'de nav-collapsed sınıfı YOKSA menü açıktır (genelde)
            // veya tam tersi şablona göre değişir. En garantisi: menü görünürse.

            // Eğer sidebar-overlay varsa ona tıklayınca kapat (en temiz yöntem)
            if ($target.attr('id') === 'sidebar-overlay') {
                $('.nav-collapse').trigger('click');
                return;
            }

            // Normal içerik tıklaması kontrolü
            // Menü görünür ve genişliği > 0 ise
            if ($menu.is(':visible') && $menu.width() > 0) {
                // Tıklanan yer menü değil ve toggle butonu değil
                if (!$target.closest('.main-sidebar-nav').length && !$target.closest('.left-nav-toggle').length && !$target.closest('.nav-collapse').length) {
                    // 
                    $('.nav-collapse').trigger('click');
                }
            }
        }
    });

    // ---------------------------------------------------------
    // 3. Yukarı Çık Butonu (Scroll to Top)
    // ---------------------------------------------------------
    // Butonu JS ile oluşturup ekleyelim, böylece HTML/CSS karmaşası olmaz
    // Iptal edildi: Statik olarak eklenecek.
    /*
    if ($('#js-scroll-top').length === 0) {
        var scrollBtnHtml = '<div id="js-scroll-top" style="display:block; position:fixed; right:20px; width:50px; height:50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; border-radius:50%; text-align:center; line-height:50px; font-size:24px; cursor:pointer; z-index:999999; box-shadow:0 5px 15px rgba(0,0,0,0.3); transition: transform 0.3s ease;"><i class="fa fa-arrow-up"></i></div>';
        $('body').append(scrollBtnHtml);
    }
    */

    var $scrollBtn = $('#static-scroll-top'); // ID guncellendi

    // Scroll animasyonu iptal edildi - buton hep sabit kalsın
    /*
    $(window).scroll(function () {
        if ($(window).scrollTop() > 300) {
            $scrollBtn.fadeIn();
        } else {
            $scrollBtn.fadeOut();
        }
    });
    */

    $scrollBtn.click(function () {
        $('html, body').animate({ scrollTop: 0 }, 600);
        return false;
    });

    // ---------------------------------------------------------
    // 4. Görsel Hatası Düzeltme (Image Error Handle)
    // ---------------------------------------------------------
    $('img').on('error', function () {
        // 
        $(this).hide();
    });

});
