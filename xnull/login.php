<?php require_once ('controller/config.php');
$demoCont=$db->prepare("SELECT * from demo where id=1");
$demoCont->execute(array());
$demoControl=$demoCont->fetch(PDO::FETCH_ASSOC);
$DemCont = $demoControl['durum'];

$n1 = rand(1, 9);
$n2 = rand(1, 9);
$_SESSION['security_result'] = $n1 + $n2;

$login_flash = null;
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'exit':
            $login_flash = ['type' => 'success', 'title' => 'Çıkış', 'text' => 'Çıkış başarılı. Bir saniye içinde yönlendiriliyorsunuz.'];
            break;
        case 'locked':
            $login_flash = ['type' => 'error', 'title' => 'Erişim engellendi', 'text' => 'Çok fazla hatalı giriş denemesi. Lütfen 15 dakika bekleyin.'];
            break;
        case 'captcha':
            $login_flash = ['type' => 'warn', 'title' => 'Güvenlik', 'text' => 'Matematik işlemi yanlış. Lütfen tekrar deneyin.'];
            break;
        default:
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>phxcore0 — Yönetici Girişi</title>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'exit') { ?>
    <meta http-equiv="refresh" content="1; URL=login.php">
    <?php } ?>
    <style>
        :root {
            --bg: #0a0c12;
            --panel: #12161f;
            --border: rgba(0, 200, 220, 0.28);
            --accent: #00c8d4;
            --accent2: #7c5cff;
            --text: #e8eef8;
            --muted: rgba(200, 215, 235, 0.65);
            --font: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html {
            height: 100%;
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            min-height: 100%;
            min-height: 100dvh;
            min-height: -webkit-fill-available;
            font-family: var(--font);
            font-size: 16px;
            color: var(--text);
            background: var(--bg);
            /* Tek katman, animasyonsuz — GPU yükü düşük */
            background-image: radial-gradient(ellipse 100% 60% at 50% 0%, rgba(124, 92, 255, 0.12) 0%, transparent 55%);
            overflow-x: hidden;
        }

        .wrap {
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px 32px;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px 22px 24px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
        }

        .brand {
            text-align: center;
            font-weight: 800;
            font-size: clamp(1.35rem, 4.5vw, 1.65rem);
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #fff;
            margin-bottom: 4px;
        }

        .brand-sub {
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--accent);
            opacity: 0.85;
            margin-bottom: 20px;
        }

        .title-line {
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--muted);
            margin: 0 0 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 200, 220, 0.15);
        }

        .title-line span { color: var(--accent2); }

        .login-flash {
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-size: 0.9rem;
            line-height: 1.45;
        }
        .login-flash strong { display: block; margin-bottom: 4px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .login-flash--success { background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.35); color: #bbf7d0; }
        .login-flash--error { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #fecaca; }
        .login-flash--warn { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); color: #fde68a; }

        .form-group { margin-bottom: 16px; text-align: left; }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 200, 220, 0.2);
            border-radius: 8px;
            color: #fff !important;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 1rem;
        }

        .form-control::placeholder { color: rgba(200, 220, 255, 0.35); }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(0, 200, 220, 0.2);
        }

        .btn-login {
            width: 100%;
            margin-top: 8px;
            padding: 14px 18px;
            font-family: inherit;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #050508;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
        }

        .btn-login:active { opacity: 0.92; }

        .footer-text {
            text-align: center;
            margin-top: 22px;
            font-size: 0.72rem;
            color: rgba(180, 200, 230, 0.38);
        }

        .footer-text a { color: var(--accent); text-decoration: none; }

        @media (max-width: 480px) {
            .login-card { padding: 22px 18px 20px; }
            .form-control { font-size: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>

    <div class="wrap">
        <div class="login-card">
            <div class="brand">phxcore0</div>
            <div class="brand-sub">Yönetim paneli</div>
            <div class="title-line">YÖNETİCİ <span>//</span> GİRİŞ</div>

            <?php if ($login_flash) {
                $cls = $login_flash['type'] === 'warn' ? 'warn' : $login_flash['type'];
            ?>
            <div class="login-flash login-flash--<?php echo $cls; ?>" role="alert">
                <strong><?php echo htmlspecialchars($login_flash['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php echo htmlspecialchars($login_flash['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php } ?>

            <form action="controller/function.php" method="POST">
                <div class="form-group">
                    <label>Kullanıcı</label>
                    <input name="kullanici_adi" type="text" <?php if ($DemCont==1) {?> value="admin" readonly <?php } ?> placeholder="Kullanıcı adı" class="form-control" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Şifre</label>
                    <input name="kullanici_pass" type="password" <?php if ($DemCont==1) {?> value="admin" readonly <?php } ?> placeholder="••••••••" class="form-control" required autocomplete="current-password">
                </div>

                <div style="display:none" aria-hidden="true">
                    <label>Web Sitesi</label>
                    <input name="website_url" type="text" placeholder="" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Güvenlik: <?php echo (int)$n1; ?> + <?php echo (int)$n2; ?> = ?</label>
                    <input name="security_check" type="number" placeholder="Sonuç" class="form-control" required autocomplete="off" inputmode="numeric">
                </div>

                <button name="login" type="submit" class="btn-login">Giriş</button>
            </form>

            <div class="footer-text">
                &copy; <?php echo date('Y'); ?> phxcore0
            </div>
        </div>
    </div>

</body>
</html>
