$(document).ready(function () {
    function initMobileTable() {
        if (window.innerWidth > 991) return;

        $('.mobile-table').each(function () {
            var $table = $(this);
            var $headers = $table.find('thead th');

            // Başlıkları eşle
            $table.find('tbody tr').each(function () {
                var $row = $(this);

                // Zaten işlendiyse atla
                if ($row.hasClass('mobile-processed')) return;

                $row.find('td').each(function (index) {
                    var headerText = $headers.eq(index).text().trim();
                    if (headerText && !$(this).attr('data-label')) {
                        $(this).attr('data-label', headerText);
                    }
                });

                // Varsayılan olarak kapalı başlat
                $row.addClass('mobile-collapsed mobile-processed');

                // Renklendirme varsa koru ama sınıfı ekle
            });
        });
    }

    // Sayfa yüklendiğinde çalıştır
    initMobileTable();

    // DataTables sayfa değişimi/sıralama vb. durumunda tekrar çalıştır
    $('.mobile-table').on('draw.dt', function () {
        initMobileTable();
    });

    // Mobil Aç/Kapa Tıklama Olayı
    $(document).on('click', '.mobile-table tbody tr', function (e) {
        if (window.innerWidth <= 991) {
            // Sadece bu satırı aç/kapat
            $(this).toggleClass('mobile-collapsed mobile-expanded');
        }
    });

    // Link, Buton ve Input tıklamalarında satır açılmasını engelle
    $(document).on('click', '.mobile-table tbody tr a, .mobile-table tbody tr button, .mobile-table tbody tr input, .mobile-table tbody tr select, .mobile-table tbody tr textarea, .mobile-table tbody tr label', function (e) {
        e.stopPropagation();
    });
});
