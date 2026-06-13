<?php
include '../controller/config.php';

if (!isset($_SESSION['kullanici_adi'])) {
    die("Yetkisiz erişim.");
}

/**
 * IP listesini güncelle
 * - Google (gstatic goog.json)
 * - AWS (resmi ip-ranges.json)
 * - Meta (Facebook): RIPE Stat — AS32934 duyurulan önekler (Meta dokümantasyonundaki ASN)
 * - TikTok / ByteDance: RIPE Stat — AS396986, AS138699 (resmi duyurular; yalnızca IPv4)
 *
 * Not: Cloudflare tüm müşteri proxy çıkışlarını içerdiği için listeden çıkarıldı.
 * Not: IPv6 önekler atlanır (cloaker IP kontrolü IPv4 odaklı).
 */

/**
 * @param string $asn Örn: AS32934
 * @return string[]
 */
function cloaker_fetch_ripe_announced_ipv4( $asn ) {
	$url = 'https://stat.ripe.net/data/announced-prefixes/data.json?resource=' . rawurlencode( $asn );
	$ctx = stream_context_create(
		array(
			'http' => array(
				'timeout' => 25,
				'header'  => "User-Agent: Mozilla/5.0 (compatible; CloakerPanel/1.0; +https://ripe.net)\r\nAccept: application/json\r\n",
			),
		)
	);
	$raw = @file_get_contents( $url, false, $ctx );
	if ( $raw === false || $raw === '' ) {
		return array();
	}
	$json = json_decode( $raw, true );
	if ( ! is_array( $json ) || empty( $json['data']['prefixes'] ) || ! is_array( $json['data']['prefixes'] ) ) {
		return array();
	}
	$out = array();
	foreach ( $json['data']['prefixes'] as $row ) {
		if ( ! is_array( $row ) || empty( $row['prefix'] ) ) {
			continue;
		}
		$pref = trim( (string) $row['prefix'] );
		if ( $pref === '' || strpos( $pref, ':' ) !== false ) {
			continue;
		}
		$first = explode( '/', $pref, 2 )[0];
		if ( ! filter_var( $first, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			continue;
		}
		$out[] = $pref;
	}
	return $out;
}

$sources = array(
	'https://www.gstatic.com/ipranges/goog.json',
	'https://ip-ranges.amazonaws.com/ip-ranges.json',
);

$all_ips = array();

$http_ctx = stream_context_create(
	array(
		'http' => array(
			'timeout' => 25,
			'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
		),
	)
);

foreach ( $sources as $source ) {
	try {
		$content = @file_get_contents( $source, false, $http_ctx );
		if ( ! $content ) {
			continue;
		}
		$json = json_decode( $content, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $json ) && isset( $json['prefixes'] ) ) {
			foreach ( $json['prefixes'] as $prefix ) {
				if ( ! is_array( $prefix ) ) {
					continue;
				}
				if ( isset( $prefix['ipv4Prefix'] ) ) {
					$all_ips[] = $prefix['ipv4Prefix'];
				} elseif ( isset( $prefix['ip_prefix'] ) ) {
					$all_ips[] = $prefix['ip_prefix'];
				}
			}
		}
	} catch ( Exception $e ) {
	}
}

/* Meta (AS32934) — developers.facebook.com “Crawler IPs” bölümünde belirtilen origin ASN */
$all_ips = array_merge( $all_ips, cloaker_fetch_ripe_announced_ipv4( 'AS32934' ) );

/* TikTok / ByteDance — RIPE’te duyurulan önekler (crawler = UA ile ayrıştırılır; IP listesi geniş olabilir) */
$all_ips = array_merge( $all_ips, cloaker_fetch_ripe_announced_ipv4( 'AS396986' ) );
$all_ips = array_merge( $all_ips, cloaker_fetch_ripe_announced_ipv4( 'AS138699' ) );

if ( ! empty( $all_ips ) ) {
	$all_ips = array_unique( $all_ips );
	sort( $all_ips, SORT_STRING );
	$header  = '# CLOAKER BLOCKED IPS - UPDATED: ' . date( 'Y-m-d H:i:s' ) . "\n";
	$header .= '# Google (gstatic), AWS ip-ranges, Meta AS32934, ByteDance/TikTok AS396986+AS138699 (RIPE Stat, IPv4 only)' . "\n";
	$header .= '# Cloudflare kaldırıldı (müşteri trafiği ile çakışma riski)' . "\n\n";

	$file_path = __DIR__ . '/blocked_ips.txt';
	$result    = file_put_contents( $file_path, $header . implode( "\n", $all_ips ) );

	if ( $result ) {
		Header( 'Location: ../cloaker.php?status=ok&msg=updated_count_' . count( $all_ips ) );
		exit;
	} else {
		Header( 'Location: ../cloaker.php?status=no&msg=write_error' );
		exit;
	}
}

Header( 'Location: ../cloaker.php?status=no&msg=fetch_error' );
exit;
