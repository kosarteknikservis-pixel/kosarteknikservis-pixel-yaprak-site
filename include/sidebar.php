<?php
/**
 * Vitrin yan sütun — içerikler sayfa (slug) üzerinden; blog modülü kaldırıldı.
 */
if (!isset($db)) {
    require_once dirname(__DIR__) . '/xnull/controller/config.php';
}
if (!function_exists('seo')) {
    require_once dirname(__DIR__) . '/xnull/controller/seo.php';
}
$pub_base = rtrim(defined('SITE_URL') ? SITE_URL : ($settingsprint['ayar_siteurl'] ?? ''), '/');
?>
<!-- Sidebar-->
<div class="sidebar col-md-3">


  <!-- Öne çıkan sayfalar (sayfa/slug) -->
  <div class="widget">
    <div id="tabs-01" class="tabs simple">
      <ul class="tabs-navigation">
        <li class="active"><a href="#tab-sayfalar">SAYFALAR</a></li>
      </ul>
      <div class="tabs-content">
        <div class="tab-pane active" id="tab-sayfalar">
          <div class="post-thumbnail-list">
          <?php
          $sayfalarlist = $db->prepare("SELECT sayfa_baslik, sayfa_slug FROM sayfalar WHERE sayfa_menu = 1 AND sayfa_durum = 1 AND sayfa_slug IS NOT NULL AND sayfa_slug != '' ORDER BY sayfa_sira ASC, id DESC LIMIT 8");
          $sayfalarlist->execute();
          while ($sp = $sayfalarlist->fetch(PDO::FETCH_ASSOC)) {
              $href = $pub_base . '/sayfa/' . rawurlencode($sp['sayfa_slug']);
          ?>
            <div class="post-thumbnail-entry">
              <div class="post-thumbnail-content">
                <a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(mb_substr($sp['sayfa_baslik'], 0, 40, 'UTF-8')); ?><?php echo mb_strlen($sp['sayfa_baslik'], 'UTF-8') > 40 ? '…' : ''; ?></a>
                <span class="post-category"><i class="fa fa-file-text-o"></i> Sayfa</span>
              </div>
            </div>
          <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!--End: Tabs with Posts-->
<div class="widget widget-shop">
  <h4 class="widget-title">Son Ürünler</h4>

  <?php 
  $urunsor=$db->prepare("SELECT * from urunler order by urun_id DESC Limit 4");
  $urunsor->execute();
  while($uruncek=$urunsor->fetch(PDO::FETCH_ASSOC)) { 

    $resimsor=$db->prepare("SELECT * from resim where resim_urun=:resim_urun Limit 1");
    $resimsor->execute(array(
     'resim_urun' => $uruncek['urun_id']
   ));

   ?>
   <div class="product">
    <div class="product-image">
      <a href="<?=seo('urunler-'.$uruncek["urun_baslik"]).'-'.$uruncek["urun_id"]?>">
        <?php while($resimcek=$resimsor->fetch(PDO::FETCH_ASSOC)) {  ?>
          <img src="xnull/<?php echo $resimcek['resim_link'] ?>" title="<?php echo $uruncek['urun_baslik']; ?>" alt="<?php echo $uruncek['urun_baslik']; ?>">
        <?php } ?>
      </a>
    </div>
    <div class="product-description">
      <div class="">
        <p><a title="<?php echo $uruncek['urun_baslik']; ?>" href="<?=seo('urunler-'.$uruncek["urun_baslik"]).'-'.$uruncek["urun_id"]?>"><?php 
        $urunkarakter = strlen( $uruncek['urun_baslik'] );
        if ( $urunkarakter > 60 )
        {
          echo mb_substr($uruncek['urun_baslik'], 0,60, 'UTF-8')."...";
        } else {
          echo $uruncek['urun_baslik']; 
        } ?></a></p>
      </div>

    </div>
</div>

<?php } ?>  

</div>
</div>
<!-- end: sidebar-->
