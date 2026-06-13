<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
include 'controller/seo.php';
$pageedit=$db->prepare("SELECT * from gizlilik where id=:sayfaid");
$pageedit->execute(array(
    'sayfaid' => 1
));
$pagewrite=$pageedit->fetch(PDO::FETCH_ASSOC);



if ( isset( $_POST[ 'duzenle' ] ) )
{
    if (!$_SESSION[ 'kullanici_adi' ]) {
        header("Location: index.php?status=no" );
        exit();
    }

    $ayarkaydet = $db->prepare(
        "UPDATE gizlilik SET
        ad=:ad,
        icerik=:icerik
        WHERE id={$_POST['id']}"
    );
    $update     = $ayarkaydet->execute(
        array(
            'ad'     => $_POST[ 'ad' ],
            'icerik'     => $_POST[ 'icerik' ]
        )
    );

    if ( $update )
    {

        Header( "Location:?status=ok" );
        exit;
    }
    else
    {

        Header( "Location:?status=no" ); exit;
    }
}
?>      
<!-- ============================================================== -->
<!--                        Content Start                           -->
<!-- ============================================================== -->
<section class="main-content container">
    <div class="page-header">
        <h2>Gizlilik Politikası İşlemleri</h2>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-heading card-default">
                    Gizlilik Politikası Düzenle
                </div>
                <div class="card-block">
                    <form method="POST" action="" class="form-horizontal">
                        <div class="form-group">
                            <input type="hidden" name="id" value="<?php echo $pagewrite['id']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Sayfa Başlık</label>
                            <input type="text" name="ad" value="<?php echo $pagewrite['ad']; ?>" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>İçerik</label>
                            <textarea class="summernote" name="icerik"><?php echo $pagewrite['icerik']; ?></textarea>
                        </div>
                        <button style="cursor: pointer;" type="submit" name="duzenle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
