$(document).ready(function () {
    var siteBase = (typeof window.PANEL_SITE_URL === 'string' && window.PANEL_SITE_URL) ? window.PANEL_SITE_URL : '';
    var ilceListXHR = null;
    $('#il').change(function () {
        var getIlID = $('#il option:selected').attr('data-id');
        
        if (!getIlID || getIlID === '') {
            if (ilceListXHR) {
                ilceListXHR.abort();
                ilceListXHR = null;
            }
            $('#getIlceForIl').html('<option value="">Önce şehrinizi seçiniz</option>');
            return;
        }

        if (ilceListXHR) {
            ilceListXHR.abort();
        }

        // disabled kullanma: devre dışı select POST'a gitmez, ilçe kaydı boş kalır
        $('#getIlceForIl').html('<option value="">Yükleniyor...</option>');

        ilceListXHR = $.ajax({
            url: siteBase + "js/ajax/getCountryForCity.php",
            type: "POST", 
            data: {city_id: getIlID}, 
            success: function (data, textStatus, jqXHR) {
                ilceListXHR = null;
                $('#getIlceForIl').html(data);
                syncSiparisIlceHidden();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus === 'abort') {
                    return;
                }
                ilceListXHR = null;
                console.error('İlçe yükleme hatası:', textStatus, errorThrown);
                $('#getIlceForIl').html('<option value="">Hata oluştu, lütfen tekrar deneyin</option>');
            }
        });
    });

    function syncSiparisIlceHidden() {
        var sel = document.getElementById('getIlceForIl');
        var hid = document.getElementById('siparis_ilce_hidden');
        if (sel && hid) {
            hid.value = sel.value || '';
        }
    }

    $('#getIlceForIl').change(function () {
        syncSiparisIlceHidden();
        var getIlID = $('#getIlceForIl option:selected').attr('data-id');

        $.ajax({
            url: siteBase + "js/ajax/getCountryForMah.php", type: "POST", data: {city_id: getIlID}, success: function (data, textStatus, jqXHR) {

                $('#getMahalle').html(data);
            }
        });
    });

    var orderForm = document.getElementById('myform');
    if (orderForm) {
        orderForm.addEventListener('submit', syncSiparisIlceHidden);
    }

    admin.plugins.init();
    admin.plugins.Gmap.init();

});


var admin = {
    plugins: {
        init: function () {
            if ($('*[data-update]').is("select")) {
                $('*[data-update]').change(function () {
                    if ($(this).data('update') == 'update_map') {
                        admin.plugins.Gmap.update_map($(this), $(this).val());
                    }
                });
            } else if ($('*[data-update]').is("input")) {
                $('*[data-update]').click(function () {
                    admin.plugins.Gmap.update_map($(this), $(this).attr('value'));
                });
            }
        },

        Gmap: {
            map: null,
            geocoder: null,
            markers: [],
            Lat: null,
            Lng: null,
            init: function () {
                if ($('#Gmap').length > 0) {
                    admin.plugins.Gmap.geocoder = new google.maps.Geocoder();
                    var mapOptions = {
                        zoom: 5,
                        center: new google.maps.LatLng(39, 34)
                    };
                    admin.plugins.Gmap.map = new google.maps.Map(document.getElementById('Gmap'), mapOptions);
                }
            },
            update_map: function (element, value) {

                if ($(element).is("select")) {
                    var text = $(element).find('option:selected').text();
                } else {
                    var text = $(element).val();
                }
                admin.plugins.Gmap.deleteMarkers();
                admin.plugins.Gmap.geocoder.geocode({'address': text}, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        admin.plugins.Gmap.Lat = results[0].geometry.location.lat();
                        admin.plugins.Gmap.Lng = results[0].geometry.location.lng();

                        if ((results && results[0] && results[0].formatted_address) && (results[0].formatted_address == "Antarctica")) {
                            admin.plugins.Gmap.SetLat = -75;
                            admin.plugins.Gmap.SetLng = 0;
                            admin.plugins.Gmap.SetZoom = 2;
                            admin.plugins.Gmap.map.setCenter(new google.maps.LatLng(admin.plugins.Gmap.Lat, admin.plugins.Gmap.Lng));
                            admin.plugins.Gmap.map.setZoom(admin.plugins.Gmap.SetZoom);
                        }
                        else if (results && results[0] && results[0].geometry && results[0].geometry.viewport) {
                            admin.plugins.Gmap.map.fitBounds(results[0].geometry.viewport);
                        }
                        else if (results && results[0] && results[0].geometry && results[0].geometry.bounds) {
                            admin.plugins.Gmap.map.fitBounds(results[0].geometry.bounds);
                        }

                        admin.plugins.Gmap.map.setCenter(results[0].geometry.location);
                        var marker = new google.maps.Marker({
                            map: admin.plugins.Gmap.map,
                            draggable: true,
                            animation: google.maps.Animation.DROP,
                            position: results[0].geometry.location
                        });
                        google.maps.event.addDomListener(marker, 'dragend', function (event) {
                            admin.plugins.Gmap.Lat = event.latLng.lat();
                            admin.plugins.Gmap.Lng = event.latLng.lng();
                            admin.plugins.Gmap.update_input();
                        });
                        admin.plugins.Gmap.markers.push(marker);
                        admin.plugins.Gmap.update_input();
                    }
                });
            },
            update_input: function () {
                if ($('#Lat').length > 0) {
                    $('#Lat').val(admin.plugins.Gmap.Lat);
                }
                if ($('#Lng').length > 0) {
                    $('#Lng').val(admin.plugins.Gmap.Lng);
                }
            },
            deleteMarkers: function () {
                for (var i = 0; i < admin.plugins.Gmap.markers.length; i++) {
                    admin.plugins.Gmap.markers[i].setMap(null);
                }
                admin.plugins.Gmap.markers = [];
            }
        },
    }
};
