<?php 
include 'header.php'; 
include 'topbar.php'; 
include 'sidebar.php'; 

$form_id = $_GET['id'];
$formsor = $db->prepare("SELECT * FROM formlar WHERE form_id=:id");
$formsor->execute(array('id' => $form_id));
$formcek = $formsor->fetch(PDO::FETCH_ASSOC);

if (!$formcek) {
    header("Location: form-yonetimi.php");
    exit;
}
?>

<section class="main-content container">
    <div class="page-header">
        <h2>Başvurular: <?php echo $formcek['form_baslik']; ?></h2>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10" id="header_export_buttons">
                        <!-- Excel button will be appended here by DataTables -->
                    </div>
                    Gelen Başvurular: <?php echo $formcek['form_baslik']; ?>
                </div>
                <div class="card-block">
                    <form action="controller/function.php" method="POST">
                        <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                        <table id="datatable_form_basvurular" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:20px;">
                                        <div class="checkbox checkbox-primary">
                                            <input id="selectAll" type="checkbox">
                                            <label for="selectAll"> </label>
                                        </div>
                                    </th>
                                    <th>#ID</th>
                                    <th>Tarih</th>
                                    <th>IP Adresi</th>
                                    <th>Durum</th>
                                    <?php 
                                    // Dinamik alanları header olarak ekle
                                    $alansor = $db->prepare("SELECT alan_baslik FROM form_alanlari WHERE form_id=:id ORDER BY alan_sira ASC");
                                    $alansor->execute(array('id' => $form_id));
                                    $alanlar = $alansor->fetchAll(PDO::FETCH_ASSOC);
                                    foreach($alanlar as $alan) {
                                        echo '<th>' . $alan['alan_baslik'] . '</th>';
                                    }
                                    ?>
                                    <th class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables by Ajax -->
                            </tbody>
                        </table>

                        <div class="row mt-20">
                            <div class="col-md-3">
                                <select name="islem" class="form-control">
                                    <option value="">Toplu İşlem Seçin</option>
                                    <option value="0">Yeni Yap</option>
                                    <option value="1">İncelendi Yap</option>
                                    <option value="2">Onaylandı Yap</option>
                                    <option value="3">Reddedildi Yap</option>
                                    <option value="sil">Seçilenleri Sil</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="formbasvurutoplu" class="btn btn-teal">Uygula</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- DataTables & Excel Export Scripts -->
<script>
$(document).ready(function() {
    var form_id = <?php echo $form_id; ?>;
    var columnCount = <?php echo count($alanlar) + 6; ?>; // Checkbox, ID, Tarih, IP, Durum, [Dinamik Alanlar], İşlemler
    
    var table = $('#datatable_form_basvurular').DataTable({
        dom: 'Blfrtip',
        processing: true,
        serverSide: true,
        ajax: {
            url: 'controller/form_basvuru_data.php',
            type: 'POST',
            data: { form_id: form_id }
        },
        pageLength: 25,
        order: [[1, "desc"]], // ID'ye göre sırala
        columnDefs: [
            { orderable: false, targets: [0, columnCount - 1] } // Checkbox ve İşlemler sıralanamaz
        ],
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fa fa-file-excel-o"></i> Excel\'e Aktar',
                className: 'btn btn-success btn-sm',
                title: '<?php echo $formcek['form_slug']; ?>_basvurular',
                exportOptions: {
                    columns: ':not(:first-child):not(:last-child)' // İlk ve son sütun hariç
                }
            }
        ],
        language: {
            "sDecimal": ",",
            "sEmptyTable": "Tabloda herhangi bir veri mevcut değil",
            "sInfo": "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
            "sInfoEmpty": "Kayıt yok",
            "sInfoFiltered": "(_MAX_ kayıt içerisinden bulunan)",
            "sLengthMenu": "Sayfada _MENU_ kayıt göster",
            "sLoadingRecords": "Yükleniyor...",
            "sProcessing": "İşleniyor...",
            "sSearch": "Ara:",
            "sZeroRecords": "Eşleşen kayıt bulunamadı",
            "oPaginate": {
                "sFirst": "İlk", "sLast": "Son", "sNext": "Sonraki", "sPrevious": "Önceki"
            }
        }
    });

    // Butonu başlığa taşı
    table.buttons().container().appendTo('#header_export_buttons');

    // Select All
    $("#selectAll").click(function() {
        $(".sectum").prop("checked", $(this).prop("checked"));
    });
});
</script>

<?php include 'footer.php'; ?>
