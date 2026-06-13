<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$tg=$db->prepare("SELECT * from telegram where id=1");
$tg->execute();
$tgprint=$tg->fetch(PDO::FETCH_ASSOC);

if (!$tgprint) {
    $db->query("INSERT INTO telegram (id, durum) VALUES (1, 0)");
    $tg->execute();
    $tgprint=$tg->fetch(PDO::FETCH_ASSOC);
}
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Telegram Bildirim Ayarları</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-heading card-default">
                    TELEGRAM AYARLARI
                </div>
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal">
                        <div class="form-group">
                            <label>Telegram Bildirim Durumu</label>
                            <select name="telegram_durum" class="form-control">
                                <option value="1" <?php echo $tgprint['durum']==1 ? 'selected' : ''; ?>>Aktif</option>
                                <option value="0" <?php echo $tgprint['durum']==0 ? 'selected' : ''; ?>>Pasif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bot Token</label>
                            <input type="text" name="telegram_token" value="<?php echo $tgprint['bot_token']; ?>" class="form-control" placeholder="Örn: 123456789:ABCDE...">
                        </div>
                        <div class="form-group">
                            <label>Chat ID</label>
                            <input type="text" name="telegram_chatid" value="<?php echo $tgprint['chat_id']; ?>" class="form-control" placeholder="Örn: -100123456789">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="telegram_guncelle" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                    <div class="alert alert-info">
                        <strong>Bilgi:</strong> Telegram bildirimleri için @BotFather üzerinden bir bot oluşturun ve Chat ID'nizi belirleyin.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
