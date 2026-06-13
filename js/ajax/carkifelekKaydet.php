<?php
include '../../xnull/controller/config.php';

/** Aynı IP ile en fazla 24 saatte bir çevirme (sunucu kaydı tek otorite) */
define('CARKIFELEK_COOLDOWN_SECONDS', 86400);

function GetIP() {
    if ( getenv( 'HTTP_CF_CONNECTING_IP' ) ) {
        $ip = getenv( 'HTTP_CF_CONNECTING_IP' );
    } elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
        $ip = getenv( 'HTTP_CLIENT_IP' );
    } elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
        $ip = getenv( 'HTTP_X_FORWARDED_FOR' );
        if ( strstr( $ip, ',' ) ) {
            $tmp = explode( ',', $ip );
            $ip  = trim( $tmp[0] );
        }
    } else {
        $ip = getenv( 'REMOTE_ADDR' );
    }
    return $ip;
}

/**
 * Son 24 saat içinde bu IP ile çark çevrilmiş mi?
 */
function carkifelek_ip_blocked_last_window( PDO $db, $ip ) {
    $since = time() - CARKIFELEK_COOLDOWN_SECONDS;
    $st    = $db->prepare( 'SELECT 1 FROM carkifelek_log WHERE ip = :ip AND tarih >= :since LIMIT 1' );
    $st->execute( array( 'ip' => $ip, 'since' => $since ) );
    return (bool) $st->fetchColumn();
}

/**
 * Limit / “çeviremezsiniz” metni: paneldeki çarkıfelek açıklaması (ayar_carkifelek_aciklama).
 */
function carkifelek_limit_message( PDO $db ) {
    static $cached = null;
    if ( $cached !== null ) {
        return $cached;
    }
    $default = 'Günde 1 kez çarkıfelek çevirerek indirim kazanabilirsiniz!';
    try {
        $st = $db->prepare( 'SELECT ayar_carkifelek_aciklama FROM ayar WHERE ayar_id = 0 LIMIT 1' );
        $st->execute();
        $row = $st->fetch( PDO::FETCH_ASSOC );
        $txt = isset( $row['ayar_carkifelek_aciklama'] ) ? trim( (string) $row['ayar_carkifelek_aciklama'] ) : '';
        $cached = ( $txt !== '' ) ? $txt : $default;
    } catch ( Throwable $e ) {
        $cached = $default;
    }
    return $cached;
}

/**
 * Ödül listesinde ücretsiz kargo (veya kargo bedava / free shipping) niteliğinde ilk satırı bulur.
 * Orijinal yazımı döner (çark / sipariş notu ile uyum için).
 */
function carkifelek_odul_ucretsiz_kargo_bul( array $oduller ) {
    foreach ( $oduller as $label ) {
        $raw = trim( (string) $label );
        if ( $raw === '' ) {
            continue;
        }
        $l = function_exists( 'mb_strtolower' ) ? mb_strtolower( $raw, 'UTF-8' ) : strtolower( $raw );
        $ascii = strtr(
            $l,
            array(
                'ü' => 'u',
                'ğ' => 'g',
                'ı' => 'i',
                'ş' => 's',
                'ö' => 'o',
                'ç' => 'c',
                'İ' => 'i',
            )
        );
        $has_kargo = ( strpos( $l, 'kargo' ) !== false || strpos( $ascii, 'kargo' ) !== false );
        if ( ! $has_kargo ) {
            continue;
        }
        $ucretsiz = ( strpos( $l, 'ücretsiz' ) !== false || strpos( $ascii, 'ucretsiz' ) !== false );
        $bedava   = ( strpos( $l, 'bedava' ) !== false );
        $free     = ( strpos( $l, 'free' ) !== false && strpos( $l, 'ship' ) !== false );
        if ( $ucretsiz || $bedava || $free ) {
            return $raw;
        }
    }
    return null;
}

$limit_msg = carkifelek_limit_message( $db );

if ( isset( $_POST['check_limit_only'] ) ) {
    $ip = GetIP();
    if ( carkifelek_ip_blocked_last_window( $db, $ip ) ) {
        echo json_encode( array( 'status' => 'error', 'message' => $limit_msg ) );
    } else {
        echo json_encode( array( 'status' => 'success' ) );
    }
    exit;
}

if ( isset( $_POST['spin'] ) ) {
    $ip    = GetIP();
    $tarih = time();

    if ( carkifelek_ip_blocked_last_window( $db, $ip ) ) {
        echo json_encode( array( 'status' => 'error', 'message' => $limit_msg ) );
        exit;
    }

    $ekle = $db->prepare( 'INSERT INTO carkifelek_log SET ip=:ip, tarih=:tarih' );
    $ekle->execute(
        array(
            'ip'    => $ip,
            'tarih' => $tarih,
        )
    );

    $ayar_cek    = null;
    $zorla_kargo = 0;
    try {
        $ayar_sor = $db->prepare( 'SELECT ayar_carkifelek_oduller, ayar_carkifelek_zorla_ucretsiz_kargo FROM ayar WHERE ayar_id=0 LIMIT 1' );
        $ayar_sor->execute();
        $ayar_cek = $ayar_sor->fetch( PDO::FETCH_ASSOC );
    } catch ( Throwable $e ) {
        $ayar_sor = $db->prepare( 'SELECT ayar_carkifelek_oduller FROM ayar WHERE ayar_id=0 LIMIT 1' );
        $ayar_sor->execute();
        $ayar_cek = $ayar_sor->fetch( PDO::FETCH_ASSOC );
    }
    if ( is_array( $ayar_cek ) && isset( $ayar_cek['ayar_carkifelek_zorla_ucretsiz_kargo'] ) ) {
        $zorla_kargo = (int) $ayar_cek['ayar_carkifelek_zorla_ucretsiz_kargo'];
    }

    $oduller = array( '%10 İndirim', 'Kargo Bedava', '%5 İndirim', 'Sürpriz', '%20 İndirim', 'Pas', '%15 İndirim', 'Kargo Bedava' );

    if ( is_array( $ayar_cek ) && ! empty( $ayar_cek['ayar_carkifelek_oduller'] ) ) {
        $oduller_json = json_decode( $ayar_cek['ayar_carkifelek_oduller'], true );
        if ( is_array( $oduller_json ) && count( $oduller_json ) > 0 ) {
            $oduller = $oduller_json;
        }
    }
    $kargo_odul  = carkifelek_odul_ucretsiz_kargo_bul( $oduller );

    if ( $zorla_kargo === 1 && $kargo_odul !== null ) {
        $kazanilan_text = $kargo_odul;
    } else {
        $kazanilan_text = $oduller[ array_rand( $oduller ) ];
    }

    $indirim = 0;
    if ( preg_match( '/(\d+)\s*%/', $kazanilan_text, $matches ) ) {
        $indirim = (int) $matches[1];
    }

    echo json_encode(
        array(
            'status'  => 'success',
            'odul'    => $kazanilan_text,
            'indirim' => $indirim,
        )
    );
} else {
    echo json_encode( array( 'status' => 'error', 'message' => 'Geçersiz istek' ) );
}
