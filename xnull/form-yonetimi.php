<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>      
<section class="main-content container">
    <div class="page-header">
        <h2>Form Yönetimi</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <button type="button" class="btn btn-primary btn-icon" data-toggle="modal" data-target="#modal_form_ekle"><i class="fa fa-plus"></i>Yeni Form Oluştur</button>
                    </div>
                    Form Listesi
                </div>
                <div class="card-block">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Form Başlığı</th>
                                <th>Slug (Url)</th>
                                <th>Durum</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $formsor = $db->prepare("SELECT * FROM formlar ORDER BY form_id DESC");
                            $formsor->execute();
                            while($formcek = $formsor->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <tr>
                                <td><?php echo $formcek['form_id']; ?></td>
                                <td><?php echo $formcek['form_baslik']; ?></td>
                                <td><?php echo $formcek['form_slug']; ?></td>
                                <td>
                                    <?php if($formcek['form_durum']==1){ ?>
                                        <span class="label label-success">Aktif</span>
                                    <?php } else { ?>
                                        <span class="label label-danger">Pasif</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <a href="form-duzenle.php?id=<?php echo $formcek['form_id']; ?>" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i> Düzenle</a>
                                    <a href="form-basvurular.php?id=<?php echo $formcek['form_id']; ?>" class="btn btn-info btn-sm"><i class="fa fa-list"></i> Başvurular</a>
                                    <a href="controller/function.php?formsil=ok&id=<?php echo $formcek['form_id']; ?>" onclick="return confirm('Bu formu silmek istediğinize emin misiniz?')" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Sil</a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Yeni Form Ekle Modal -->
<div id="modal_form_ekle" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="controller/function.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Yeni Form Oluştur</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Form Başlığı</label>
                        <input type="text" name="form_baslik" class="form-control" placeholder="Örn: Bayilik Başvurusu" required>
                    </div>
                    <div class="form-group">
                        <label>Form Slug (URL Linki)</label>
                        <input type="text" name="form_slug" class="form-control" placeholder="Örn: bayilik-basvurusu">
                        <span class="help-block">Türkçe karakter kullanmayınız. Boş bırakırsanız otomatik oluşturulur.</span>
                    </div>
                    <div class="form-group">
                        <label>Açıklama</label>
                        <textarea name="form_aciklama" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Kapat</button>
                    <button type="submit" name="formkaydet" class="btn btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
