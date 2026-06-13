<?php
header('Content-Type: text/plain; charset=utf-8');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ? 'https' : 'http';
$domain = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : 'localhost';

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/robots.php'));
$base = rtrim(dirname($script), '/');
if ($base === '' || $base === '.' || $base === '/') {
    $path_prefix = '';
} else {
    $path_prefix = $base;
}

$sitemap_url = $protocol . '://' . $domain . $path_prefix . '/sitemap.xml';

echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /xnull/\n";
echo "Disallow: /cgi-bin/\n";
echo "Disallow: /includes/\n";
echo "Disallow: /admin/\n";
echo "\n";
echo 'Sitemap: ' . $sitemap_url . "\n";
