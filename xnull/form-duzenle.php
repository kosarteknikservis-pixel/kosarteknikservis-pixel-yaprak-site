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
        <h2>Form Düzenle: <?php echo $formcek['form_baslik']; ?></h2>
    </div>
    
    <div class="row">
        <!-- Form Ayarları ve Yeni Alan Ekleme -->
        <div class="col-md-4">
            <!-- Form Ayarları -->
            <div class="card">
                <div class="card-heading card-default">
                    Form Ayarları
                </div>
                <div class="card-block">
                    <form action="controller/function.php" method="POST">
                        <input type="hidden" name="form_id" value="<?php echo $formcek['form_id']; ?>">
                        <div class="form-group">
                            <label>Form Başlığı</label>
                            <input type="text" name="form_baslik" value="<?php echo $formcek['form_baslik']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="form_slug" value="<?php echo $formcek['form_slug']; ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Açıklama</label>
                            <textarea name="form_aciklama" class="form-control" rows="3"><?php echo $formcek['form_aciklama']; ?></textarea>
                        </div>
                        <div class="form-group">
                            <select name="form_durum" class="form-control">
                                <option value="1" <?php echo $formcek['form_durum']==1 ? 'selected':''; ?>>Aktif</option>
                                <option value="0" <?php echo $formcek['form_durum']==0 ? 'selected':''; ?>>Pasif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Menüde Göster</label>
                            <select name="form_menu" class="form-control">
                                <option value="1" <?php echo $formcek['form_menu']==1 ? 'selected':''; ?>>Evet</option>
                                <option value="0" <?php echo $formcek['form_menu']==0 ? 'selected':''; ?>>Hayır</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Menü Sırası</label>
                            <input type="number" name="form_sira" value="<?php echo $formcek['form_sira']; ?>" class="form-control">
                        </div>
                        <button type="submit" name="formguncelle" class="btn btn-primary btn-block">Güncelle</button>
                    </form>
                </div>
            </div>

            <!-- Yeni Alan Ekleme -->
            <div class="card mt-10">
                <div class="card-heading card-default">
                    Yeni Alan Ekle
                </div>
                <div class="card-block">
                    <form action="controller/function.php" method="POST">
                        <input type="hidden" name="form_id" value="<?php echo $formcek['form_id']; ?>">
                        <div class="form-group">
                            <label>Başlık (Label)</label>
                            <input type="text" name="alan_baslik" class="form-control" required placeholder="Ad Soyad">
                        </div>
                        <div class="form-group">
                            <label>Veri Tipi</label>
                            <select name="alan_tip" class="form-control" id="alan_tip_select" onchange="checkType()">
                                <option value="text">Kısa Metin (Input)</option>
                                <option value="textarea">Uzun Metin (Textarea)</option>
                                <option value="email">E-mail</option>
                                <option value="tel">Telefon</option>
                                <option value="number">Sayı</option>
                                <option value="select">Seçim Kutusu (Select)</option>
                                <option value="radio">Radyo Buton</option>
                                <option value="checkbox">Onay Kutusu</option>
                                <option value="file">Dosya Yükleme</option>
                            </select>
                        </div>
                        <div class="form-group" id="secenekler_div" style="display:none;">
                            <label>Seçenekler (Virgül ile ayırın)</label>
                            <textarea name="alan_secenekler" class="form-control" placeholder="Örn: Seçenek 1, Seçenek 2, Seçenek 3"></textarea>
                            <span class="help-block">Select, Radio ve Checkbox için gereklidir.</span>
                        </div>
                        <div class="form-group">
                            <label>Zorunlu mu?</label>
                            <select name="alan_zorunlu" class="form-control">
                                <option value="0">Hayır</option>
                                <option value="1">Evet</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sıra No</label>
                            <input type="number" name="alan_sira" class="form-control" value="0">
                        </div>
                        <button type="submit" name="alankaydet" class="btn btn-success btn-block">Alan Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form Alanları Listesi ve Önizleme -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-heading card-default">
                    Mevcut Alanlar
                </div>
                <div class="card-block">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sıra</th>
                                    <th>Başlık</th>
                                    <th>Tip</th>
                                    <th>Zorunlu</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $alansor = $db->prepare("SELECT * FROM form_alanlari WHERE form_id=:id ORDER BY alan_sira ASC");
                                $alansor->execute(array('id' => $form_id));
                                while($alancek = $alansor->fetch(PDO::FETCH_ASSOC)) {
                                ?>
                                <tr>
                                    <td><?php echo $alancek['alan_sira']; ?></td>
                                    <td><?php echo $alancek['alan_baslik']; ?></td>
                                    <td><span class="label label-default"><?php echo $alancek['alan_tip']; ?></span></td>
                                    <td><?php echo $alancek['alan_zorunlu'] ? '<span class="label label-danger">Evet</span>' : 'Hayır'; ?></td>
                                    <td class="text-center">
                                        <a href="controller/function.php?alansil=ok&id=<?php echo $alancek['alan_id']; ?>&form_id=<?php echo $form_id; ?>" onclick="return confirm('Silmek istediğinize emin misiniz?')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-10">
                <div class="card-heading card-default">
                    Form Önizlemesi
                </div>
                <div class="card-block">
                    <div class="admin-form-preview">
                    <?php 
                    $alansor->execute(array('id' => $form_id));
                    while($alancek = $alansor->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="form-group">';
                        echo '<label>'.$alancek['alan_baslik'].($alancek['alan_zorunlu'] ? ' <span class="text-danger">*</span>':'').'</label>';
                        
                        if(in_array($alancek['alan_tip'], ['text','email','tel','number'])) {
                            echo '<input type="'.$alancek['alan_tip'].'" class="form-control" placeholder="Önizleme" disabled>';
                        } elseif($alancek['alan_tip'] == 'textarea') {
                            echo '<textarea class="form-control" rows="3" placeholder="Önizleme" disabled></textarea>';
                        } elseif($alancek['alan_tip'] == 'file') {
                            echo '<input type="file" class="form-control" disabled>';
                        } elseif($alancek['alan_tip'] == 'select') {
                            echo '<select class="form-control" disabled>';
                            $secenekler = explode(',', $alancek['alan_secenekler']);
                            foreach($secenekler as $sec) echo '<option>'.trim($sec).'</option>';
                            echo '</select>';
                        } elseif($alancek['alan_tip'] == 'radio') {
                            $secenekler = explode(',', $alancek['alan_secenekler']);
                            foreach($secenekler as $sec) echo '<div class="selection-mock type-radio"><div class="indicator-mock"></div> '.trim($sec).'</div>';
                        } elseif($alancek['alan_tip'] == 'checkbox') {
                            $secenekler = explode(',', $alancek['alan_secenekler']);
                            foreach($secenekler as $sec) echo '<div class="selection-mock type-checkbox"><div class="indicator-mock"></div> '.trim($sec).'</div>';
                        }
                        
                        echo '</div>';
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
    .admin-form-preview {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 20px;
        border-radius: 12px;
    }
    .admin-form-preview .form-group label {
        font-weight: 700;
        color: #334155;
    }
    .admin-form-preview .form-control {
        border-radius: 8px;
        background: #fff;
    }
    .selection-mock {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 5px;
    }
    .indicator-mock {
        width: 18px;
        height: 18px;
        border: 2px solid #cbd5e1;
        margin-right: 10px;
    }
    .type-radio .indicator-mock { border-radius: 50%; }
    .type-checkbox .indicator-mock { border-radius: 4px; }
</style>

<script>
function checkType() {
    var type = document.getElementById('alan_tip_select').value;
    var secenekDiv = document.getElementById('secenekler_div');
    if(type == 'select' || type == 'radio' || type == 'checkbox') {
        secenekDiv.style.display = 'block';
    } else {
        secenekDiv.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>
