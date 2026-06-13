<?php
// Set 404 Header
http_response_code(404);

// Theme Configuration
include_once __DIR__ . '/xnull/controller/config.php'; 

// Fetch Settings if not already global
if(!isset($settingsprint)) {
    $settings=$db->prepare("SELECT * from ayar where ayar_id=?");
    $settings->execute(array(0));
    $settingsprint=$settings->fetch(PDO::FETCH_ASSOC);
}
if (!is_array($settingsprint)) {
    $settingsprint = array();
}
$settingsprint = array_merge(
    array(
        'ayar_title' => 'Site',
        'ayar_fav' => '',
        'ayar_description' => '',
    ),
    $settingsprint
);

// Meta Data
$meta_title = "404 - Sayfa Bulunamadı | " . ($settingsprint['ayar_title'] ?? 'Site');
$meta_desc = "Aradığınız sayfa bulunamadı. Lütfen ana sayfaya dönün.";

$asset_base = defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/' : '';
$fav_raw = trim((string) ($settingsprint['ayar_fav'] ?? ''));
if ($fav_raw !== '' && preg_match('#^https?://#i', $fav_raw)) {
    $favicon_href = $fav_raw;
} elseif ($fav_raw !== '') {
    $favicon_href = $asset_base . 'xnull/' . ltrim($fav_raw, '/');
} else {
    $favicon_href = $asset_base;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($meta_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="noindex, follow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favicon_href, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Core CSS (mutlak yol: derin URL / ErrorDocument ile göreli kırılmaz) -->
    <link href="<?php echo htmlspecialchars($asset_base, ENT_QUOTES, 'UTF-8'); ?>css/plugins.css" rel="stylesheet">
    <link href="<?php echo htmlspecialchars($asset_base, ENT_QUOTES, 'UTF-8'); ?>css/style.css" rel="stylesheet">
    <link href="<?php echo htmlspecialchars($asset_base, ENT_QUOTES, 'UTF-8'); ?>css/responsive.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    
    <!-- Custom 404 Styles -->
    <style>
        body { 
            background-color: #f0f2f5; 
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }
        .error-page-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
        }
        /* Abstract Background Shapes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            z-index: -1;
        }
        .shape-1 {
            width: 300px;
            height: 300px;
            background: var(--renk1, #1abc9c);
            top: -50px;
            left: -50px;
            animation: float 6s ease-in-out infinite;
        }
        .shape-2 {
            width: 400px;
            height: 400px;
            background: #ff357a;
            bottom: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite reverse;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 60px 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 90%;
            transform: translateY(0);
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .error-code {
            font-size: 150px;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, var(--renk1, #1abc9c) 0%, #ff357a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .error-icon {
            font-size: 80px;
            color: #ff357a;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        .error-title {
            font-size: 32px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 15px;
        }

        .error-desc {
            font-family: 'Open Sans', sans-serif;
            font-size: 19px;
            color: #4a4a4a;
            margin-bottom: 40px;
            line-height: 1.6;
            font-weight: 700; /* Bold text */
        }

        /* Vibrant Button (Copied from form-goruntule.php style) */
        .btn-vibrant {
            display: inline-block;
            background: linear-gradient(45deg, #ff357a, #fff172);
            border: none;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            box-shadow: 0 10px 20px rgba(255, 53, 122, 0.3);
            transition: all 0.3s ease;
            color: #fff;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        .btn-vibrant:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 53, 122, 0.5);
            background: linear-gradient(45deg, #ff0f63, #ffeb3b);
            color: #fff;
        }
        
        /* Logo area removed */

        /* Animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .error-code { font-size: 100px; }
            .error-card { padding: 40px 20px; }
            .shape-1, .shape-2 { display: none; }
        }
    </style>
</head>
<body>

    <div class="error-page-wrapper">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>

        <div class="error-card">
            
            <!-- Choose one layout preference: Icon or Big Text -->
            <div class="error-code">404</div>
            
            <h2 class="error-title">Üzgünüz, Sayfa Kayıp!</h2>
            <p class="error-desc">
                Gitmek istediğiniz sayfa uzay boşluğunda kaybolmuş olabilir veya taşınmış. 
                Endişelenmeyin, sizi ana üsse geri götürebiliriz.
            </p>
            
            <a href="<?php echo htmlspecialchars($asset_base, ENT_QUOTES, 'UTF-8'); ?>" class="btn-vibrant">
                <i class="fa fa-shuttle-space"></i> Ana Sayfaya Dön
            </a>
        </div>
    </div>

</body>
</html>
