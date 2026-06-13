(function ($) {
    "use strict";

    // Document ready içinde çalıştır
    $(document).ready(function () {
        // Mobilde nanoScroller sayfa dikey kaydırmasını bozabiliyor; sadece geniş ekranda başlat
        function setupNanoScroller() {
            var w = window.innerWidth || document.documentElement.clientWidth;
            try {
                $(".nano").nanoScroller({ destroy: true });
            } catch (e) { /* ignore */ }
            if (w > 991) {
                $(".nano").nanoScroller({
                    preventPageScrolling: false
                });
            }
        }
        setupNanoScroller();
        var nanoResizeTimer;
        $(window).on("resize", function () {
            clearTimeout(nanoResizeTimer);
            nanoResizeTimer = setTimeout(setupNanoScroller, 200);
        });

        // Left menu collapse - Hamburger menü
        $('.left-nav-toggle a, .left-nav-collapsed a').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            if ($(this).hasClass('nav-collapse')) {
                $("body").toggleClass("nav-toggle");
            } else if ($(this).hasClass('nav-collapsed')) {
                $("body").toggleClass("nav-collapsed");
            }
        });
    });

    // Left menu collapse
    $('.right-sidebar-toggle').on('click', function (event) {
        event.preventDefault();
        $("#right-sidebar-toggle").toggleClass("right-sidebar-toggle");
    });

    //metis menu
    $('#menu').metisMenu({
        triggerElement: '.nav-link',
        parentTrigger: '.nav-item',
        subMenu: '.nav.flex-column',
        toggle: true
    });
    // Aktif bölümde alt menü kapalı kalmasın (sayfa yenilendikten sonra da açık kalsın)
    $('#menu > li.nav-item.active > ul.sub-menu').addClass('in').each(function () {
        $(this).closest('li.nav-item').children('a.nav-link').first().attr('aria-expanded', 'true');
    });

    //slim scroll
    // $('.scrollDiv').slimScroll({
    //     color: '#eee',
    //     size: '5px',
    //     height: '300px',
    //     alwaysVisible: false
    // });

    //tooltip popover
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

})(jQuery);
/**
$status='ok';

if ($status=="ok") {
    $(document).ready(function () {
        swal({
            title: "İŞLEM BAŞARILI",
            text: "Yapılan işlem başarılı bir şekilde tamamlanmıştır.",
            type: "success",
            cancelButtonClass: 'btn-secondary ',
            confirmButtonClass: 'btn-success',
            confirmButtonText: 'Tamam!'
        });
    });
}

*/