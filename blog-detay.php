<?php
/**
 * Eski blog detay URL'leri — içerik artık Sayfa Yönetimi (sayfa/slug) ile yayınlanıyor.
 * İndeksteki veya yer imlerindeki linkler kırılmasın diye ana sayfaya yönlendirilir.
 */
require_once __DIR__ . '/xnull/controller/config.php';
header('Location: ' . rtrim(SITE_URL, '/') . '/', true, 301);
exit;
