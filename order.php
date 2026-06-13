<?php
define('VITRIN_HEAD_MINIMAL', true);
include 'include/head.php';
require_once __DIR__ . '/include/front-order-flow.php';

$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
if ($product_id <= 0) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/index.php');
    exit;
}

$urun = null;
try {
    $q = $db->prepare('SELECT * FROM urunler WHERE urun_id = :id LIMIT 1');
    $q->execute(['id' => $product_id]);
    $urun = $q->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $urun = null;
}
if (!$urun) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/index.php');
    exit;
}

$urun_adi = trim((string)$urun['urun_baslik']);
if ($urun_adi !== '' && function_exists('mb_detect_encoding')) {
  $detected = @mb_detect_encoding($urun_adi, ['UTF-8', 'ISO-8859-9', 'Windows-1254', 'ISO-8859-1'], true);
  if ($detected !== false && $detected !== 'UTF-8') {
    $urun_adi = mb_convert_encoding($urun_adi, 'UTF-8', $detected);
  }
}
// Panelde bazen başlık WYSIWYG ile <p>…</p> olarak kayıtlı; sipariş özetinde düz metin göster
$_udec = ENT_QUOTES;
if (defined('ENT_HTML5')) {
  $_udec |= ENT_HTML5;
}
$urun_adi = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($urun_adi, $_udec, 'UTF-8'))));
unset($_udec);
$urun_fiyat = (int) floatval($urun['urun_fiyat']);
// Sipariş kartı: ürünün sipariş görseli → yoksa vitrin görseli → yoksa varsayılan (her ürün ürün yönetiminden)
$order_resolve_product_img = function ($rel) {
    $urun_resim = trim((string) $rel);
    if ($urun_resim === '') {
        return '';
    }
    if (strpos($urun_resim, 'xnull/') !== false || strpos($urun_resim, 'http') === 0) {
        return $urun_resim;
    }
    if (strpos($urun_resim, 'assets/img/urunler') !== false) {
        return 'xnull/' . $urun_resim;
    }
    if (strpos($urun_resim, 'upload/') === 0) {
        return 'xnull/assets/img/urunler/' . str_replace('upload/', '', $urun_resim);
    }
    return 'xnull/assets/img/urunler/' . ltrim($urun_resim, '/');
};
$urun_resim_yol = '';
$kart_src = trim((string) ($urun['urun_siparis_kart'] ?? ''));
if ($kart_src !== '') {
    $urun_resim_yol = $order_resolve_product_img($kart_src);
} elseif (!empty($urun['urun_resim'])) {
    $urun_resim_yol = $order_resolve_product_img((string) $urun['urun_resim']);
}
if ($urun_resim_yol === '') {
    $order_default_rel = 'assets/img/genel/order-checkout-card-default.svg';
    $order_default_fs = __DIR__ . '/' . $order_default_rel;
    if (is_file($order_default_fs)) {
        $urun_resim_yol = 'xnull/' . $order_default_rel;
    }
}
unset($order_resolve_product_img);
$order_card_img_ver = '1';
if ($urun_resim_yol !== '' && strpos($urun_resim_yol, 'http') !== 0) {
    $order_card_fs = __DIR__ . '/' . str_replace('\\', '/', $urun_resim_yol);
    $order_card_img_ver = (string) (@filemtime($order_card_fs) ?: time());
}
$order_form_action = rtrim(SITE_URL, '/') . '/order.php';
$order_pd = isset($front_order_post_data) && is_array($front_order_post_data) ? $front_order_post_data : array();
$order_index_url = rtrim(SITE_URL, '/') . '/index.php';
$order_index_products_hash = $order_index_url . '#urun-kartlari';
?>
<meta charset="utf-8">
<!-- iOS zoom fix: bu sayfada odaklanınca otomatik yakınlaştırmayı sınırlamak için viewport'u daraltıyoruz -->
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<title>Sipariş Tamamlama - <?php echo htmlspecialchars($settingsprint['ayar_title'] ?? 'Site', ENT_QUOTES, 'UTF-8'); ?></title>
<style>
  /*
   * Açık tema: yan boşluklar ve tam sayfa zemini (#f8fafc).
   * html + body birlikte: bazı tarayıcılarda sadece body boyanınca kenarlarda koyu çizgi kalabiliyor.
   */
  html {
    background-color: #f8fafc !important;
  }
  body.order-checkout-page.no-page-loader.boxed {
    padding-top: 86px;
    background-color: #f8fafc !important;
    background-image: none !important;
    color: #0f172a;
    min-height: 100vh;
  }
  /* iOS Safari: input font-size <16px ise odakta otomatik zoom yapıyor.
     Sadece bu sayfada ve sadece form kontrollerinde 16px garanti ediyoruz. */
  @media (max-width: 768px) {
    body.order-checkout-page input,
    body.order-checkout-page select,
    body.order-checkout-page textarea {
      font-size: 16px !important;
    }
  }
  /*
   * Tema (ör. apple.css): section { background-color: var(--renk1); } tüm bölümlere koyu zemin veriyor.
   * Sipariş bloğu <section> olduğu için merkez sütun koyu kalıp yazılar kayboluyordu — vitrin rengine override.
   */
  body.order-checkout-page section.order-checkout-wrap {
    max-width: 720px;
    margin: 0 auto;
    padding: 24px 16px 56px !important;
    background-color: #ffffff !important;
    background-image: none !important;
    color: #0f172a !important;
    -webkit-font-smoothing: antialiased;
  }
  /* Üst menü (#1e293b) ile uyumlu, yumuşak gölgeli yüzeyler — sert 1px çerçeve yerine */
  body.order-checkout-page {
    --order-header: #1e293b;
    --order-ink: #1e293b;
    --order-ink-soft: #334155;
    --order-muted: #475569;
    --order-line: rgba(30, 41, 59, 0.08);
    --order-line-strong: rgba(30, 41, 59, 0.14);
    --order-shadow-sm: 0 1px 2px rgba(30, 41, 59, 0.04), 0 4px 16px rgba(30, 41, 59, 0.06);
    --order-shadow-md: 0 2px 8px rgba(30, 41, 59, 0.05), 0 12px 32px rgba(30, 41, 59, 0.08);
  }
  .order-checkout-hero {
    text-align: center;
    margin-bottom: 24px;
  }
  .order-checkout-hero h1 {
    margin: 0;
    font-size: clamp(1.5rem, 4vw, 1.85rem);
    font-weight: 800;
    color: var(--order-ink);
    letter-spacing: -0.02em;
    line-height: 1.2;
  }
  .order-checkout-hero p {
    margin: 12px 0 0;
    color: var(--order-muted);
    font-size: 0.95rem;
    line-height: 1.55;
    max-width: 36em;
    margin-left: auto;
    margin-right: auto;
  }
  .order-nav-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 22px;
    align-items: center;
  }
  .order-btn-nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 14px;
    font-weight: 800;
    font-size: 0.92rem;
    text-decoration: none;
    transition: transform 0.15s ease, box-shadow 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    border: 2px solid transparent;
    min-height: 48px;
    box-sizing: border-box;
    cursor: pointer;
    line-height: 1.2;
  }
  .order-btn-nav--home {
    background: linear-gradient(180deg, #334155 0%, var(--order-header) 100%);
    color: #fff;
    box-shadow: 0 6px 20px rgba(30, 41, 59, 0.28);
  }
  .order-btn-nav--home:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgba(30, 41, 59, 0.32);
  }
  .order-btn-nav--change {
    background: #fff;
    color: var(--order-header);
    border-color: var(--order-line-strong);
    box-shadow: var(--order-shadow-sm);
  }
  .order-btn-nav--change:hover {
    border-color: rgba(34, 197, 94, 0.55);
    color: #15803d;
    transform: translateY(-1px);
  }
  @media (max-width: 480px) {
    body.order-checkout-page section.order-checkout-wrap {
      padding-left: 14px !important;
      padding-right: 14px !important;
    }
    .order-nav-actions {
      flex-direction: column;
    }
    .order-nav-actions .order-btn-nav {
      width: 100%;
      justify-content: center;
    }
  }
  .order-alert {
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
    font-weight: 600;
    font-size: 0.92rem;
    line-height: 1.45;
    border: none;
    box-shadow: var(--order-shadow-sm);
  }
  .order-alert--ok {
    background: linear-gradient(180deg, #ecfdf5 0%, #d1fae5 100%);
    color: #14532d;
    box-shadow: var(--order-shadow-sm), inset 0 1px 0 rgba(255, 255, 255, 0.6);
  }
  .order-alert--err {
    background: linear-gradient(180deg, #fef2f2 0%, #fee2e2 100%);
    color: #991b1b;
    box-shadow: var(--order-shadow-sm), inset 0 1px 0 rgba(255, 255, 255, 0.5);
  }
  .order-product-card {
    background: #fff;
    border: 1px solid var(--order-line);
    border-radius: 20px;
    box-shadow: var(--order-shadow-md);
    margin-bottom: 22px;
    overflow: hidden;
  }
  .order-product-card__head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--order-line);
  }
  .order-product-card__head-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid var(--order-line);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--order-header);
    font-size: 1.2rem;
    box-shadow: var(--order-shadow-sm);
  }
  .order-product-card__kicker {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--order-muted);
    margin-bottom: 3px;
  }
  .order-product-card__head-title {
    display: block;
    font-size: 1.02rem;
    font-weight: 900;
    color: var(--order-ink);
    letter-spacing: -0.02em;
    line-height: 1.25;
  }
  .order-product-card__body {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 22px;
    padding: 22px 20px 26px;
  }
  .order-product-card__media {
    width: 100%;
    margin: 0 auto;
    max-width: min(100%, 400px);
  }
  .order-product-card__media-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 1;
    min-height: clamp(232px, 58vw, 300px);
    max-height: min(400px, 58vh);
    padding: clamp(12px, 3.5vw, 22px);
    background: #fff;
    border-radius: 18px;
    border: 1px solid var(--order-line);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), var(--order-shadow-sm);
    box-sizing: border-box;
    overflow: hidden;
  }
  .order-product-card__media-inner img {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
  }
  .order-product-card__info {
    min-width: 0;
    flex: 1;
    text-align: center;
  }
  .order-product-card__label {
    display: inline-block;
    font-size: 0.72rem;
    color: var(--order-muted);
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .order-product-card__name {
    font-size: clamp(1.06rem, 3.3vw, 1.28rem);
    font-weight: 800;
    color: var(--order-ink);
    line-height: 1.38;
    margin: 0 0 16px;
    letter-spacing: -0.02em;
    word-break: break-word;
  }
  .order-product-card__price {
    display: inline-flex;
    align-items: baseline;
    gap: 6px;
    padding: 11px 20px;
    background: linear-gradient(180deg, #fef2f2 0%, #fff5f5 100%);
    border: 1px solid rgba(220, 38, 38, 0.2);
    border-radius: 14px;
    font-size: clamp(1.28rem, 4vw, 1.52rem);
    font-weight: 900;
    color: #dc2626;
    letter-spacing: -0.02em;
    box-shadow: var(--order-shadow-sm);
  }
  .order-product-card__price small {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--order-muted);
  }
  .order-qty-control {
    margin-top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid rgba(148, 163, 184, 0.5);
    min-width: 124px;
    justify-content: space-between;
    white-space: nowrap;
  }
  .order-qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: none;
    background: #0f172a;
    color: #fff;
    font-weight: 800;
    font-size: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 0;
  }
  .order-qty-btn:disabled {
    opacity: 0.4;
    cursor: default;
  }
  .order-qty-input {
    width: 44px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 700;
    color: #0f172a;
    font-size: 0.95rem;
    padding: 0;
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
  }
  .order-qty-input::-webkit-outer-spin-button,
  .order-qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  .order-qty-input:focus {
    outline: none;
  }
  @media (min-width: 640px) {
    .order-product-card__body {
      flex-direction: row;
      align-items: center;
      gap: 28px;
      padding: 26px 24px 30px;
    }
    .order-product-card__media {
      flex: 0 0 clamp(268px, 36vw, 360px);
      width: clamp(268px, 36vw, 360px);
      max-width: 360px;
      margin: 0;
    }
    .order-product-card__media-inner {
      min-height: 268px;
      max-height: 360px;
    }
    .order-product-card__info {
      text-align: left;
    }
  }
  @media (max-width: 639px) {
    .order-product-card__media-inner {
      aspect-ratio: auto;
      min-height: 0;
      max-height: none;
      padding: 10px;
    }
    .order-product-card__media-inner img {
      width: 100%;
      height: auto;
      max-height: none;
      object-fit: contain;
    }
    .order-qty-control {
      min-width: 132px;
    }
    .order-qty-btn {
      width: 30px;
      height: 30px;
    }
    .order-qty-input {
      width: 48px;
      font-size: 1rem;
    }
  }
  .order-panel {
    background: #fff;
    border: 1px solid var(--order-line);
    border-radius: 20px;
    padding: 22px 20px;
    margin-bottom: 18px;
    box-shadow: var(--order-shadow-sm);
  }
  .order-panel h2 {
    margin: 0 0 16px;
    font-size: 1.02rem;
    font-weight: 800;
    color: var(--order-ink);
    letter-spacing: -0.01em;
  }
  .order-panel label {
    color: var(--order-ink-soft);
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1.4;
  }
  .order-panel .form-control {
    background: #fafbfc;
    border: 1px solid var(--order-line-strong);
    color: var(--order-ink);
    border-radius: 12px;
    margin-top: 8px;
    padding: 11px 14px;
    /* iOS Safari: input font-size 16px altı olunca odakta otomatik zoom yapabiliyor */
    font-size: 1rem !important; /* 16px */
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  }
  .order-panel .form-control:hover {
    background: #fff;
    border-color: rgba(30, 41, 59, 0.18);
  }
  .order-panel .form-control:focus {
    background: #fff;
    border-color: var(--renk2, #22c55e);
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.14);
    outline: none;
  }
  .order-pay-option {
    background: #fafbfc;
    border: 1px solid var(--order-line);
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 10px;
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.8) inset;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  }
  .order-pay-option:hover {
    background: #fff;
    border-color: var(--order-line-strong);
  }
  .order-pay-option input:checked + label { color: var(--order-ink); font-weight: 600; }
  .order-pay-option:has(input:checked) {
    border-color: rgba(34, 197, 94, 0.45);
    background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%);
    box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.2), var(--order-shadow-sm);
  }
  .order-btn-primary {
    width: 100%;
    border: none;
    border-radius: 14px;
    padding: 14px 18px;
    font-weight: 800;
    font-size: 1rem;
    background: linear-gradient(135deg, var(--renk2, #22c55e) 0%, #16a34a 100%);
    color: #fff;
    box-shadow: 0 10px 28px rgba(34, 197, 94, 0.22);
    transition: transform 0.15s, box-shadow 0.15s;
  }
  .order-btn-primary:hover { transform: translateY(-1px); color: #fff; }
  .order-btn-secondary {
    width: 100%;
    border-radius: 14px;
    padding: 12px 16px;
    font-weight: 700;
    background: #fff;
    border: 1px solid var(--order-line-strong);
    color: var(--order-ink-soft);
    margin-top: 8px;
    box-shadow: var(--order-shadow-sm);
    transition: background 0.2s, border-color 0.2s;
  }
  .order-btn-secondary:hover {
    background: #f8fafc;
    border-color: rgba(30, 41, 59, 0.22);
    color: var(--order-ink);
  }
  .order-consent-vitrin {
    margin: 16px 0;
    background: #f8fafc;
    padding: 18px 20px;
    border-radius: 16px;
    border: 1px solid var(--order-line);
    box-shadow: var(--order-shadow-sm);
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
  }
  .order-consent-vitrin:hover {
    box-shadow: var(--order-shadow-md);
    border-color: var(--order-line-strong);
  }
  .order-consent-vitrin label[for="sozlesme_onay"] { color: var(--order-ink-soft); font-weight: 600; line-height: 1.45; }
  @media (max-width: 576px) {
    .responsive-policy-btn {
      width: 100% !important;
      min-width: 100% !important;
    }
  }
  .modal-agreement {
    display: none;
    position: fixed;
    z-index: 2147483647 !important;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(2, 6, 23, 0.78);
    backdrop-filter: blur(6px);
    overflow: hidden;
  }
  .modal-content-agreement {
    background-color: #fff;
    margin: 5vh auto;
    padding: 28px;
    width: 92%;
    max-width: 820px;
    border-radius: 16px;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
    color: #334155;
  }
  .close-modal {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 32px;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    line-height: 1;
  }
  .order-otp-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(5px);
    z-index: 2147483646;
  }
  .order-otp-modal {
    display: none;
    position: fixed;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: min(92vw, 420px);
    max-height: min(88vh, 640px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 22px;
    border: 1px solid var(--order-line);
    box-shadow: 0 28px 64px rgba(30, 41, 59, 0.22);
    z-index: 2147483647;
    box-sizing: border-box;
  }
  .order-otp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
  }
  .order-otp-modal-header strong {
    display: block;
    font-size: 0.72rem;
    font-weight: 800;
    color: #0ea5e9;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .order-otp-modal-header h3 {
    margin: 0;
    font-size: 1.28rem;
    font-weight: 800;
    color: var(--order-ink);
    line-height: 1.2;
  }
  .order-otp-close {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border: none;
    background: #f1f5f9;
    border-radius: 12px;
    font-size: 1.35rem;
    line-height: 1;
    color: var(--order-muted);
    cursor: pointer;
  }
  .order-otp-hint {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #ecfeff;
    border: 1px solid rgba(14, 165, 233, 0.28);
    border-radius: 14px;
    padding: 12px 14px;
    margin-bottom: 14px;
    font-size: 0.88rem;
    color: var(--order-ink-soft);
    line-height: 1.45;
  }
  .order-otp-modal input[name="otp_code_front"] {
    letter-spacing: 0.35em;
    font-weight: 800;
    text-align: center;
    font-size: 1.15rem;
  }
  .sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }
  @media (max-width: 576px) {
    body.order-checkout-page.no-page-loader.boxed { padding-top: 76px; }
  }
  .order-trust-wrap {
    margin-top: 28px;
    padding-top: 22px;
    border-top: 1px solid var(--order-line);
  }
  .order-trust-head {
    margin: 0 0 14px;
    text-align: center;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--order-muted);
  }
  .order-trust-strip {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    margin-bottom: 14px;
  }
  @media (min-width: 720px) {
    .order-trust-strip {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  .order-trust-item {
    margin: 0;
    padding: 16px 18px;
    background: #fff;
    border: 1px solid var(--order-line);
    border-radius: 20px;
    box-shadow: var(--order-shadow-sm);
    box-sizing: border-box;
  }
  .order-trust-item__row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
  }
  .order-trust-item__mark {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: #fafbfc;
    border: 1px solid var(--order-line);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--order-header);
    font-size: 1.05rem;
    line-height: 1;
  }
  .order-trust-item__mark .fa {
    opacity: 0.92;
  }
  .order-trust-item__text {
    min-width: 0;
    flex: 1;
  }
  .order-trust-item__text strong {
    display: block;
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--order-ink);
    line-height: 1.3;
    letter-spacing: -0.01em;
    margin-bottom: 4px;
  }
  .order-trust-item__text span {
    display: block;
    font-size: 0.8rem;
    color: var(--order-muted);
    line-height: 1.45;
  }
  .order-trust-footnote {
    margin: 0;
    padding: 14px 18px;
    font-size: 0.82rem;
    color: var(--order-muted);
    text-align: center;
    line-height: 1.5;
    background: #fafbfc;
    border-radius: 20px;
    border: 1px solid var(--order-line);
    box-shadow: var(--order-shadow-sm);
  }
  .order-trust-footnote .fa {
    color: var(--order-header);
    opacity: 0.85;
    margin-right: 8px;
  }
</style>
</head>
<body class="order-checkout-page wide no-page-loader boxed">
<?php include 'include/menu.php'; ?>

<section class="order-checkout-wrap">
  <div class="order-nav-actions">
    <a class="order-btn-nav order-btn-nav--home" href="<?php echo htmlspecialchars($order_index_url, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="fa fa-home" aria-hidden="true"></i>
      Ana sayfaya dön
    </a>
    <a class="order-btn-nav order-btn-nav--change" href="<?php echo htmlspecialchars($order_index_products_hash, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="fa fa-exchange" aria-hidden="true"></i>
      Ürünü değiştir
    </a>
  </div>

  <div class="order-checkout-hero">
    <h1>Siparişi tamamlayın</h1>
    <p>Seçtiğiniz ürün sepetinizde. Ödeme yöntemini ve teslimat bilgilerinizi girerek siparişinizi güvenle oluşturun.</p>
  </div>

  <?php if ($front_verify_notice !== '') { ?>
    <div class="order-alert <?php echo $front_verify_notice_type === 'ok' ? 'order-alert--ok' : 'order-alert--err'; ?>" role="status">
      <?php echo htmlspecialchars($front_verify_notice, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php } ?>
  <?php if (!empty($order_flow_alert) && is_array($order_flow_alert) && !empty($order_flow_alert['text'])) { ?>
    <div class="order-alert order-alert--err" role="alert">
      <?php echo htmlspecialchars((string)$order_flow_alert['text'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php } ?>

  <div class="order-product-card" aria-live="polite">
    <div class="order-product-card__head">
      <span class="order-product-card__head-icon" aria-hidden="true"><i class="fa fa-shopping-bag"></i></span>
      <div>
        <span class="order-product-card__kicker">Sepetinizdeki ürün</span>
        <span class="order-product-card__head-title">Bu ürünle siparişinizi tamamlayın</span>
      </div>
    </div>
    <div class="order-product-card__body">
      <?php if ($urun_resim_yol !== '') { ?>
      <div class="order-product-card__media">
        <div class="order-product-card__media-inner">
          <img src="<?php echo htmlspecialchars($urun_resim_yol, ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo rawurlencode($order_card_img_ver); ?>" alt="<?php echo htmlspecialchars($urun_adi, ENT_QUOTES, 'UTF-8'); ?>" loading="eager" decoding="async" fetchpriority="high" sizes="(min-width: 640px) 360px, min(100vw, 400px)">
        </div>
      </div>
      <?php } ?>
      <div class="order-product-card__info">
        <span class="order-product-card__label">Ürün adı</span>
        <h2 class="order-product-card__name"><?php echo htmlspecialchars($urun_adi, ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php
          $order_unit_price = $urun_fiyat;
          $order_qty_preview = isset($order_pd['urun_adet']) ? (int)$order_pd['urun_adet'] : 1;
          if ($order_qty_preview < 1) $order_qty_preview = 1;
          if ($order_qty_preview > 999) $order_qty_preview = 999;
          $order_total_price = $order_unit_price * $order_qty_preview;
        ?>
        <div class="order-product-card__price" id="order_price_display" data-unit-price="<?php echo (int)$order_unit_price; ?>">
          <span id="order_price_total"><?php echo number_format($order_total_price, 0, ',', '.'); ?></span> <small>TL</small>
        </div>
        <div class="order-qty-control" aria-label="Ürün adedi">
          <button type="button" class="order-qty-btn" id="order_qty_minus" aria-label="Adedi azalt">−</button>
          <input type="text" inputmode="numeric" pattern="[0-9]*" class="order-qty-input" id="order_qty_display" value="<?php echo (int)$order_qty_preview; ?>" aria-label="Adet" autocomplete="off">
          <button type="button" class="order-qty-btn" id="order_qty_plus" aria-label="Adedi artır">+</button>
        </div>
        <p style="margin:14px 0 0;font-size:0.86rem;color:var(--order-muted);line-height:1.5;">Farklı bir ürün seçmek için yukarıdaki <strong>Ürünü değiştir</strong> düğmesini kullanabilirsiniz.</p>
      </div>
    </div>
  </div>

  <form action="<?php echo htmlspecialchars($order_form_action, ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="myform">
    <input type="hidden" name="siparisver" value="1">
    <?php
    $urun_hidden = (isset($order_pd['urun']) && (string)$order_pd['urun'] !== '') ? (string)$order_pd['urun'] : ($urun_adi . '|' . $urun_fiyat);
    $cark_hidden = isset($order_pd['carkifelek_odul']) ? (string)$order_pd['carkifelek_odul'] : '';
    $urun_adet_default = isset($order_pd['urun_adet']) ? (int)$order_pd['urun_adet'] : 1;
    if ($urun_adet_default < 1) $urun_adet_default = 1;
    if ($urun_adet_default > 999) $urun_adet_default = 999;
    ?>
    <input type="hidden" name="urun" value="<?php echo htmlspecialchars($urun_hidden, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="urun_adet" id="order_qty_input" value="<?php echo (int)$urun_adet_default; ?>">
    <input type="hidden" name="order_origin" value="1">
    <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
    <input type="hidden" name="carkifelek_odul" id="carkifelek_odul" value="<?php echo htmlspecialchars($cark_hidden, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="order-panel">
      <h2>Ödeme yönteminiz</h2>
      <?php
      $odeme = $db->prepare("SELECT * from odeme where odeme_durum=1 order by odeme_id ASC");
      $odeme->execute();
      $saved_odeme = isset($order_pd['odeme']) ? (string)$order_pd['odeme'] : '';
      $odeme_checked = false;
      foreach ($odeme as $odemecek) {
        $raw_odeme_val = $odemecek['odeme_adi'] . '-' . $odemecek['odeme_id'];
        $is_checked = ($saved_odeme !== '' && $saved_odeme === $raw_odeme_val) || ($saved_odeme === '' && !$odeme_checked);
        if ($is_checked) {
          $odeme_checked = true;
        }
      ?>
        <div class="order-pay-option">
          <input type="radio" <?php echo $is_checked ? 'checked' : ''; ?> id="odeme<?php echo (int)$odemecek['odeme_id']; ?>" name="odeme" value="<?php echo htmlspecialchars($raw_odeme_val, ENT_QUOTES, 'UTF-8'); ?>">
          <label for="odeme<?php echo (int)$odemecek['odeme_id']; ?>" style="margin-bottom:0;cursor:pointer;display:inline;">
            <?php echo htmlspecialchars($odemecek['odeme_adi'], ENT_QUOTES, 'UTF-8'); ?>
            <span style="color:#666;font-weight:500;"> <?php echo htmlspecialchars((string)$odemecek['odeme_not'], ENT_QUOTES, 'UTF-8'); ?></span>
          </label>
        </div>
      <?php } ?>
    </div>

    <div class="order-panel">
      <h2>Teslimat bilgileriniz</h2>
      <div style="margin-bottom:12px;">
        <label for="siparis_ad_input">Adınız soyadınız</label>
        <input type="text" class="form-control" name="siparis_ad" id="siparis_ad_input" value="<?php echo htmlspecialchars((string)($order_pd['siparis_ad'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$front_verify_pending ? 'required minlength="2"' : ''; ?> maxlength="100" autocomplete="name" autocapitalize="words" title="En az 2 harf; sadece harf, boşluk ve tire">
      </div>
      <div style="margin-bottom:12px;">
        <label for="tel_input">Telefon numaranız</label>
        <input type="tel" class="form-control" id="tel_input" name="siparis_tel" value="<?php echo htmlspecialchars((string)($order_pd['siparis_tel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$front_verify_pending ? 'required minlength="10" maxlength="12" pattern="[0-9]{10,12}"' : ''; ?> inputmode="numeric" autocomplete="tel" placeholder="05xx…" title="10–12 hane, sadece rakam">
      </div>
      <div style="margin-bottom:12px;">
        <label for="il">Şehriniz</label>
        <select class="form-control" <?php echo !$front_verify_pending ? 'required' : ''; ?> name="siparis_il" id="il" data-select-search="true">
          <option value="">Şehrinizi seçiniz</option>
          <?php
          $pd_il = isset($order_pd['siparis_il']) ? (string)$order_pd['siparis_il'] : '';
          $il = $db->prepare("SELECT * from il order by il_adi ASC");
          $il->execute();
          while ($ilcek = $il->fetch(PDO::FETCH_ASSOC)) {
            $il_adi_row = (string)$ilcek['il_adi'];
            $il_sel = ($pd_il !== '' && $pd_il === $il_adi_row) ? ' selected' : '';
          ?>
            <option data-id="<?php echo (int)$ilcek['id']; ?>" value="<?php echo htmlspecialchars($il_adi_row, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $il_sel; ?>><?php echo htmlspecialchars($il_adi_row, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php } ?>
        </select>
      </div>
      <div style="margin-bottom:12px;">
        <label for="getIlceForIl">İlçeniz</label>
        <input type="hidden" name="siparis_ilce" id="siparis_ilce_hidden" value="<?php echo htmlspecialchars((string)($order_pd['siparis_ilce'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <select id="getIlceForIl" <?php echo !$front_verify_pending ? 'required' : ''; ?> class="form-control">
          <option value="">Önce şehrinizi seçiniz</option>
        </select>
      </div>
      <div style="margin-bottom:12px;">
        <label for="siparis_adres_input">Açık adresiniz</label>
        <textarea class="form-control" <?php echo !$front_verify_pending ? 'required' : ''; ?> name="siparis_adres" id="siparis_adres_input" rows="3" minlength="8" maxlength="700" autocomplete="street-address" placeholder="Mahalle, sokak, bina no, daire…"><?php echo htmlspecialchars((string)($order_pd['siparis_adres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <div style="margin-bottom:12px;">
        <label for="siparis_not_input">Sipariş notu (isteğe bağlı)</label>
        <textarea class="form-control" name="siparis_not" id="siparis_not_input" rows="2" maxlength="1500"><?php echo htmlspecialchars((string)($order_pd['siparis_not'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <?php if (!empty($settingsprint['ayar_kurumsal_fatura_on'])) { ?>
      <div style="margin-bottom:12px;padding:16px;border-radius:16px;border:1px solid rgba(30,41,59,0.08);background:#f8fafc;box-shadow:0 1px 2px rgba(30,41,59,0.04),0 4px 16px rgba(30,41,59,0.06);">
        <div style="font-weight:800;margin-bottom:10px;color:#0f172a;">Kurumsal fatura (isteğe bağlı)</div>
        <input type="text" class="form-control" id="siparis_fatura_vn" name="siparis_fatura_vn" value="<?php echo htmlspecialchars((string)($order_pd['siparis_fatura_vn'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="11" inputmode="numeric" pattern="[0-9]*" placeholder="Vergi numarası" style="margin-bottom:8px;">
        <input type="text" class="form-control" id="siparis_fatura_vd" name="siparis_fatura_vd" value="<?php echo htmlspecialchars((string)($order_pd['siparis_fatura_vd'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="128" placeholder="Vergi dairesi" style="margin-bottom:8px;">
        <input type="text" class="form-control" id="siparis_fatura_unvan" name="siparis_fatura_unvan" value="<?php echo htmlspecialchars((string)($order_pd['siparis_fatura_unvan'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="255" placeholder="Firma ünvanı" style="margin-bottom:8px;">
        <textarea class="form-control" id="siparis_fatura_adres" name="siparis_fatura_adres" rows="2" maxlength="3000" placeholder="Fatura adresi"><?php echo htmlspecialchars((string)($order_pd['siparis_fatura_adres'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
      <?php } ?>

      <?php if (!$front_verify_pending) { ?>
        <?php if ((isset($settingsprint['ayar_sozlesme_on']) && (int)$settingsprint['ayar_sozlesme_on'] === 1) || (isset($settingsprint['ayar_gizlilik_on']) && (int)$settingsprint['ayar_gizlilik_on'] === 1)) { ?>
        <div class="order-consent-vitrin">
          <div style="display: flex; align-items: flex-start; gap: 15px; flex-wrap: wrap;">
            <input type="checkbox" id="sozlesme_onay" required checked style="margin: 4px 0 0; transform: scale(1.15); cursor: pointer; accent-color: #22c55e; width: 18px; height: 18px; flex-shrink: 0;">
            <div style="display: flex; flex-direction: column; gap: 12px; flex: 1; min-width: 200px;">
              <label for="sozlesme_onay" style="margin: 0; cursor: pointer;">Sözleşme ve gizlilik koşullarını onaylıyorum.</label>
              <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <?php if (isset($settingsprint['ayar_sozlesme_on']) && (int)$settingsprint['ayar_sozlesme_on'] === 1) { ?>
              <a href="javascript:void(0);" onclick="openModal(); return false;" style="color: #6366f1; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; padding: 8px 15px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-radius: 8px; border: 1px solid #c7d2fe; min-width: 200px; justify-content: center;" onmouseover="this.style.background='linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)'; this.style.color='#4f46e5'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%)'; this.style.color='#6366f1'; this.style.transform='translateY(0)';" class="responsive-policy-btn">
                <i class="fa fa-file-text-o" style="font-size: 14px;"></i>
                <span>Mesafeli Satış Sözleşmesi</span>
                <i class="fa fa-external-link" style="font-size: 10px; opacity: 0.8;"></i>
              </a>
              <?php } ?>
              <?php if (isset($settingsprint['ayar_gizlilik_on']) && (int)$settingsprint['ayar_gizlilik_on'] === 1) { ?>
              <a href="javascript:void(0);" onclick="openPrivacyModal(); return false;" style="color: #6366f1; text-decoration: none; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; padding: 8px 15px; background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-radius: 8px; border: 1px solid #c7d2fe; min-width: 200px; justify-content: center;" onmouseover="this.style.background='linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)'; this.style.color='#4f46e5'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%)'; this.style.color='#6366f1'; this.style.transform='translateY(0)';" class="responsive-policy-btn">
                <i class="fa fa-shield" style="font-size: 14px;"></i>
                <span>Gizlilik Politikası</span>
                <i class="fa fa-external-link" style="font-size: 10px; opacity: 0.8;"></i>
              </a>
              <?php } ?>
              </div>
            </div>
          </div>
        </div>
        <?php } else { ?>
        <div class="order-consent-vitrin">
          <div style="display: flex; align-items: center; gap: 12px;">
            <input type="checkbox" id="sozlesme_onay" required checked style="margin: 0; transform: scale(1.15); cursor: pointer; accent-color: #22c55e; width: 18px; height: 18px; flex-shrink: 0;">
            <label for="sozlesme_onay" style="margin: 0; cursor: pointer;">Sözleşme ve gizlilik koşullarını onaylıyorum.</label>
          </div>
        </div>
        <?php } ?>
        <button id="siparisButon" type="submit" class="order-btn-primary btn order-btn" name="siparisver" onclick="if(this.form.checkValidity()){ this.innerHTML='İşlem yapılıyor…'; this.style.opacity='0.9'; }">Siparişi tamamla</button>
      <?php } else { ?>
        <p style="margin:0 0 14px;font-size:0.92rem;color:var(--order-muted);line-height:1.5;">Sipariş bilgileriniz kayıtlı. Aşağıdaki düğmeyle SMS doğrulama penceresini açıp kodu girin.</p>
        <button id="openOtpModalBtn" type="button" class="order-btn-primary btn order-btn">SMS doğrulama penceresini aç</button>
      <?php } ?>
    </div>

    <?php if ($front_verify_pending) { ?>
    <div id="order-otp-backdrop" class="order-otp-backdrop" aria-hidden="true"></div>
    <div id="order-otp-modal" class="order-otp-modal" role="dialog" aria-modal="true" aria-labelledby="order-otp-title">
      <div class="order-otp-modal-header">
        <div>
          <strong>Güvenli doğrulama</strong>
          <h3 id="order-otp-title">Telefon doğrulama</h3>
        </div>
        <button type="button" class="order-otp-close" id="orderOtpModalClose" aria-label="Kapat">&times;</button>
      </div>
      <div class="order-otp-hint">
        <span style="font-size:1.15rem;line-height:1;" aria-hidden="true">&#128241;</span>
        <span>Kod <strong><?php echo htmlspecialchars($front_verify_tel_mask, ENT_QUOTES, 'UTF-8'); ?></strong> numarasına gönderildi. Kod 5 dakika geçerlidir.</span>
      </div>
      <label for="order_otp_code_input" class="sr-only">6 haneli SMS kodu</label>
      <input type="text" class="form-control" id="order_otp_code_input" name="otp_code_front" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" autocomplete="one-time-code">
      <button type="submit" class="order-btn-primary btn order-btn" name="otp_stage_verify_submit">Kodu doğrula ve siparişi tamamla</button>
      <button type="submit" class="order-btn-secondary btn order-btn" name="otp_stage_resend_submit" formnovalidate>Kodu tekrar gönder</button>
    </div>
    <?php } ?>
  </form>

  <?php
  $order_sms_verify_on = !isset($settingsprint['ayar_siparis_dogrulama_on']) || (int)$settingsprint['ayar_siparis_dogrulama_on'] === 1;
  ?>
  <div class="order-trust-wrap">
    <p class="order-trust-head">Güvenli alışveriş</p>
    <div class="order-trust-strip" role="region" aria-label="Güvenli alışveriş bilgileri">
      <div class="order-trust-item">
        <div class="order-trust-item__row">
          <span class="order-trust-item__mark" aria-hidden="true"><i class="fa fa-credit-card"></i></span>
          <div class="order-trust-item__text">
            <strong>Kapıda güvenle ödeyin</strong>
            <span>Ödemenizi ürünü teslim alırken nakit veya kredi kartıyla yapabilirsiniz; önceden ödeme gerekmez.</span>
          </div>
        </div>
      </div>
      <div class="order-trust-item">
        <div class="order-trust-item__row">
          <span class="order-trust-item__mark" aria-hidden="true"><i class="fa fa-lock"></i></span>
          <div class="order-trust-item__text">
            <strong>SSL ile korunan sayfa</strong>
            <span>Bilgileriniz şifreli bağlantı üzerinden iletilir; kart ve kişisel verileriniz güvenle işlenir.</span>
          </div>
        </div>
      </div>
      <div class="order-trust-item">
        <div class="order-trust-item__row">
          <span class="order-trust-item__mark" aria-hidden="true"><i class="fa <?php echo $order_sms_verify_on ? 'fa-mobile' : 'fa-check-circle'; ?>"></i></span>
          <div class="order-trust-item__text">
            <?php if ($order_sms_verify_on) { ?>
            <strong>SMS ile sipariş doğrulama</strong>
            <span>Telefon numaranıza tek kullanımlık kod gönderilir; yetkisiz işlemlerin önüne geçilir.</span>
            <?php } else { ?>
            <strong>Şeffaf sipariş süreci</strong>
            <span>Siparişiniz kayda alınır; gerekirse ekibimiz sizinle iletişime geçerek süreci netleştirir.</span>
            <?php } ?>
          </div>
        </div>
      </div>
      <div class="order-trust-item">
        <div class="order-trust-item__row">
          <span class="order-trust-item__mark" aria-hidden="true"><i class="fa fa-truck"></i></span>
          <div class="order-trust-item__text">
            <strong>Hızlı teslimat</strong>
            <span>Siparişiniz onaylandıktan sonra kısa sürede kargoya verilir; adresinize güvenle ulaştırılır.</span>
          </div>
        </div>
      </div>
    </div>
    <p class="order-trust-footnote">
      <i class="fa fa-shield" aria-hidden="true"></i>
      Kişisel verileriniz yalnızca siparişinizi tamamlamak için kullanılır; üçüncü taraflarla paylaşılmaz.
    </p>
  </div>
</section>

<?php if (isset($settingsprint['ayar_sozlesme_on']) && (int)$settingsprint['ayar_sozlesme_on'] === 1) { ?>
<div id="agreementModal" class="modal-agreement">
  <div class="modal-content-agreement">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <h2 style="font-weight: 800; margin: 0 0 18px;">Mesafeli Satış Sözleşmesi</h2>
    <div style="line-height: 1.8;">
      <?php
      $sozlesmesor = $db->prepare("SELECT * FROM sozlesme WHERE id=1");
      $sozlesmesor->execute();
      $sozlesmecek = $sozlesmesor->fetch(PDO::FETCH_ASSOC);
      echo $sozlesmecek['icerik'];
      ?>
    </div>
  </div>
</div>
<?php } ?>

<?php if (isset($settingsprint['ayar_gizlilik_on']) && (int)$settingsprint['ayar_gizlilik_on'] === 1) { ?>
<div id="privacyModal" class="modal-agreement">
  <div class="modal-content-agreement">
    <span class="close-modal" onclick="closePrivacyModal()">&times;</span>
    <h2 style="font-weight: 800; margin: 0 0 18px;">Gizlilik Politikası</h2>
    <div style="line-height: 1.8;">
      <?php
      $gizliliksor = $db->prepare("SELECT * FROM gizlilik WHERE id=1");
      $gizliliksor->execute();
      $gizlilikcek = $gizliliksor->fetch(PDO::FETCH_ASSOC);
      echo $gizlilikcek['icerik'];
      ?>
    </div>
  </div>
</div>
<?php } ?>

<?php include 'include/footer-form.php'; ?>
<script>window.ORDER_FORM_PREFILL=<?php echo json_encode(array(
  'siparis_il' => (string)($order_pd['siparis_il'] ?? ''),
  'siparis_ilce' => (string)($order_pd['siparis_ilce'] ?? ''),
), JSON_UNESCAPED_UNICODE); ?>;</script>
<script>
(function() {
  // Ürün adedi + toplam fiyat hesaplama
  var qtyInputHidden = document.getElementById('order_qty_input');
  var qtyDisplay = document.getElementById('order_qty_display');
  var btnMinus = document.getElementById('order_qty_minus');
  var btnPlus = document.getElementById('order_qty_plus');
  var priceWrap = document.getElementById('order_price_display');
  var priceText = document.getElementById('order_price_total');
  function parseIntSafe(v) {
    var n = parseInt(String(v).replace(/[^0-9]/g, ''), 10);
    return isNaN(n) ? 1 : n;
  }
  function syncQty(delta, directValue) {
    if (!qtyInputHidden || !qtyDisplay || !priceWrap || !priceText) return;
    var unit = parseIntSafe(priceWrap.getAttribute('data-unit-price'));
    if (unit < 0) unit = 0;
    var current = parseIntSafe(qtyInputHidden.value || qtyDisplay.value || 1);
    var next = typeof directValue === 'number' ? directValue : (current + (delta || 0));
    if (next < 1) next = 1;
    if (next > 999) next = 999;
    qtyInputHidden.value = String(next);
    qtyDisplay.value = String(next);
    if (btnMinus) btnMinus.disabled = (next <= 1);
    var total = unit * next;
    try {
      priceText.textContent = new Intl.NumberFormat('tr-TR').format(total);
    } catch(e) {
      priceText.textContent = String(total);
    }
  }
  if (btnMinus) {
    btnMinus.addEventListener('click', function(e) {
      e.preventDefault();
      syncQty(-1);
    });
  }
  if (btnPlus) {
    btnPlus.addEventListener('click', function(e) {
      e.preventDefault();
      syncQty(1);
    });
  }
  if (qtyDisplay) {
    qtyDisplay.addEventListener('input', function() {
      syncQty(0, parseIntSafe(qtyDisplay.value));
    });
    qtyDisplay.addEventListener('blur', function() {
      syncQty(0, parseIntSafe(qtyDisplay.value));
    });
  }
  // İlk yüklemede varsayılan değeri senkronize et
  syncQty(0, parseIntSafe(qtyInputHidden ? qtyInputHidden.value : (qtyDisplay ? qtyDisplay.value : 1)));

  var siteBase = (typeof window.PANEL_SITE_URL === 'string' && window.PANEL_SITE_URL) ? window.PANEL_SITE_URL : <?php echo json_encode(rtrim(SITE_URL, '/') . '/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

  // Yarım kalan sipariş kaydı: order sayfasında ad/tel girilip ayrılınca da kayıt düşsün.
  var yarimKalanTimer = null;
  var siparisAdInput = document.querySelector("input[name='siparis_ad']");
  var siparisTelInput = document.querySelector("input[name='siparis_tel']");
  var urunInput = document.querySelector("input[name='urun']");
  var yarimKalanUrl = siteBase + 'js/ajax/yarimKalanKaydet.php';
  function getUrunVeFiyat() {
    var raw = urunInput ? String(urunInput.value || '') : '';
    if (!raw) return { urun: '', fiyat: '' };
    var parts = raw.split('|');
    return {
      urun: (parts[0] || '').trim(),
      fiyat: (parts[1] || '').trim()
    };
  }
  function buildYarimKalanPayload() {
    var ad = siparisAdInput ? String(siparisAdInput.value || '').trim() : '';
    var tel = siparisTelInput ? String(siparisTelInput.value || '').trim() : '';
    var urunMeta = getUrunVeFiyat();
    return {
      ad: ad,
      tel: tel,
      urun: urunMeta.urun,
      fiyat: urunMeta.fiyat
    };
  }
  function shouldSendYarimKalan(payload) {
    return payload && (payload.ad.length > 2 || payload.tel.replace(/[^0-9]/g, '').length > 3);
  }
  function sendYarimKalan(payload, useBeacon) {
    if (!shouldSendYarimKalan(payload)) return;
    if (useBeacon && navigator.sendBeacon) {
      var fd = new FormData();
      fd.append('ad', payload.ad);
      fd.append('tel', payload.tel);
      fd.append('urun', payload.urun);
      fd.append('fiyat', payload.fiyat);
      navigator.sendBeacon(yarimKalanUrl, fd);
      return;
    }
    if (typeof jQuery !== 'undefined' && jQuery.ajax) {
      jQuery.ajax({
        type: 'POST',
        url: yarimKalanUrl,
        data: payload
      });
    }
  }
  function scheduleYarimKalanSave() {
    clearTimeout(yarimKalanTimer);
    yarimKalanTimer = setTimeout(function() {
      sendYarimKalan(buildYarimKalanPayload(), false);
    }, 1500);
  }
  if (siparisAdInput || siparisTelInput) {
    ['input', 'blur', 'change'].forEach(function(evtName) {
      if (siparisAdInput) siparisAdInput.addEventListener(evtName, scheduleYarimKalanSave);
      if (siparisTelInput) siparisTelInput.addEventListener(evtName, scheduleYarimKalanSave);
    });
    window.addEventListener('beforeunload', function() {
      sendYarimKalan(buildYarimKalanPayload(), true);
    });
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'hidden') {
        sendYarimKalan(buildYarimKalanPayload(), true);
      }
    });
  }

  function syncSiparisIlceHidden() {
    var sel = document.getElementById('getIlceForIl');
    var hid = document.getElementById('siparis_ilce_hidden');
    if (sel && hid) {
      hid.value = sel.value || '';
    }
  }
  var orderFormEl = document.getElementById('myform');
  if (orderFormEl) {
    orderFormEl.addEventListener('submit', syncSiparisIlceHidden);
  }

  var ilceXhr = null;
  var il = document.getElementById('il');
  var ilce = document.getElementById('getIlceForIl');
  if (!il || !ilce || typeof jQuery === 'undefined') return;
  ilce.addEventListener('change', syncSiparisIlceHidden);
  il.addEventListener('change', function() {
    var opt = il.options[il.selectedIndex];
    var cityId = opt ? opt.getAttribute('data-id') : '';
    if (!cityId) {
      ilce.innerHTML = '<option value="">Önce şehrinizi seçiniz</option>';
      return;
    }
    if (ilceXhr && ilceXhr.abort) ilceXhr.abort();
    ilce.innerHTML = '<option value="">Yükleniyor...</option>';
    ilceXhr = jQuery.ajax({
      url: siteBase + 'js/ajax/getCountryForCity.php',
      type: 'POST',
      data: { city_id: cityId },
      success: function(data) {
        ilce.innerHTML = data;
        var pre = window.ORDER_FORM_PREFILL || {};
        if (pre.siparis_ilce) {
          for (var i = 0; i < ilce.options.length; i++) {
            if (ilce.options[i].value === pre.siparis_ilce) {
              ilce.selectedIndex = i;
              break;
            }
          }
        }
        syncSiparisIlceHidden();
      },
      error: function() { ilce.innerHTML = '<option value="">İlçe yüklenemedi</option>'; }
    });
  });
  jQuery(function($) {
    var pre = window.ORDER_FORM_PREFILL || {};
    if (!pre.siparis_il) return;
    var ilEl = document.getElementById('il');
    if (!ilEl) return;
    for (var j = 0; j < ilEl.options.length; j++) {
      if (ilEl.options[j].value === pre.siparis_il) {
        ilEl.selectedIndex = j;
        $(ilEl).trigger('change');
        break;
      }
    }
  });
})();
<?php if (!empty($front_verify_pending)) { ?>
(function() {
  var form = document.getElementById('myform');
  if (!form) return;
  form.querySelectorAll('[required]').forEach(function(el) {
    if (el.name !== 'otp_code_front') el.removeAttribute('required');
  });
  var modal = document.getElementById('order-otp-modal');
  var backdrop = document.getElementById('order-otp-backdrop');
  var closeBtn = document.getElementById('orderOtpModalClose');
  var otpInput = document.getElementById('order_otp_code_input');
  function closeOtp() {
    if (modal) modal.style.display = 'none';
    if (backdrop) backdrop.style.display = 'none';
    document.body.style.overflow = '';
    if (otpInput) otpInput.removeAttribute('required');
  }
  function openOtp() {
    if (modal) modal.style.display = 'block';
    if (backdrop) backdrop.style.display = 'block';
    document.body.style.overflow = 'hidden';
    if (otpInput) {
      otpInput.setAttribute('required', 'required');
      otpInput.focus();
    }
  }
  if (modal && backdrop) openOtp();
  if (closeBtn) closeBtn.addEventListener('click', closeOtp);
  if (backdrop) backdrop.addEventListener('click', closeOtp);
  var openBtn = document.getElementById('openOtpModalBtn');
  if (openBtn) openBtn.addEventListener('click', openOtp);
})();
<?php } ?>

function openModal() {
  var modal = document.getElementById('agreementModal');
  if (!modal) return;
  modal.style.display = 'block';
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  var modal = document.getElementById('agreementModal');
  if (!modal) return;
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
}
function openPrivacyModal() {
  var modal = document.getElementById('privacyModal');
  if (!modal) return;
  modal.style.display = 'block';
  document.body.style.overflow = 'hidden';
}
function closePrivacyModal() {
  var modal = document.getElementById('privacyModal');
  if (!modal) return;
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
}
window.addEventListener('click', function(event) {
  var agreementModal = document.getElementById('agreementModal');
  var privacyModal = document.getElementById('privacyModal');
  if (agreementModal && event.target === agreementModal) closeModal();
  if (privacyModal && event.target === privacyModal) closePrivacyModal();
});
</script>
</body>
</html>
