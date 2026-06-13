<?php
header("Content-Type: text/xml; charset=utf-8");
include 'xnull/controller/config.php';

// Use predefined SITE_URL for consistency
$base_url = SITE_URL;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 1. Homepage
echo "  <url>\n";
echo "    <loc>" . $base_url . "</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// 2. Products (urunler) - Rewrite rule: ^urun/([0-9a-zA-Z-_]+)$ index.php
$urunler = $db->prepare("SELECT urun_slug FROM urunler");
$urunler->execute();
while($urun = $urunler->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($urun['urun_slug'])) {
        echo "  <url>\n";
        echo "    <loc>" . $base_url . "urun/" . $urun['urun_slug'] . "</loc>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.9</priority>\n";
        echo "  </url>\n";
    }
}

// 3. Pages (sayfalar) - Rewrite rule: ^sayfa/([0-9a-zA-Z-_]+)$ sayfa.php?slug=$1
$sayfalar = $db->prepare("SELECT sayfa_slug FROM sayfalar WHERE sayfa_durum = 1");
$sayfalar->execute();
while($sayfa = $sayfalar->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($sayfa['sayfa_slug'])) {
        echo "  <url>\n";
        echo "    <loc>" . $base_url . "sayfa/" . $sayfa['sayfa_slug'] . "</loc>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.7</priority>\n";
        echo "  </url>\n";
    }
}

echo '</urlset>';
?>
