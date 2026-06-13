<?php
if ( ! function_exists( 'ov_now_utc' ) ) {
	function ov_now_utc() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'ov_ensure_table' ) ) {
	function ov_ensure_table( PDO $db ) {
		static $ready = false;
		if ( $ready ) {
			return;
		}

		$db->exec(
			"CREATE TABLE IF NOT EXISTS siparis_dogrulama (
				id INT AUTO_INCREMENT PRIMARY KEY,
				siparis_id INT NOT NULL,
				siparis_tel VARCHAR(32) NOT NULL DEFAULT '',
				otp_code VARCHAR(8) NOT NULL DEFAULT '',
				token_hash VARCHAR(128) NOT NULL DEFAULT '',
				durum TINYINT NOT NULL DEFAULT 0,
				dogrulama_kanali VARCHAR(20) NOT NULL DEFAULT '',
				hata_sayisi INT NOT NULL DEFAULT 0,
				son_gonderim DATETIME NULL,
				son_kontrol DATETIME NULL,
				son_dogrulama DATETIME NULL,
				bitis_tarihi DATETIME NOT NULL,
				olusturma_tarihi DATETIME NOT NULL,
				UNIQUE KEY uq_siparis_id (siparis_id),
				KEY idx_token_hash (token_hash),
				KEY idx_durum (durum),
				KEY idx_bitis_tarihi (bitis_tarihi)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);
		$ready = true;
	}
}

if ( ! function_exists( 'ov_normalize_tel' ) ) {
	function ov_normalize_tel( $tel ) {
		$d = preg_replace( '/\D+/', '', (string) $tel );
		if ( strlen( $d ) === 12 && strncmp( $d, '90', 2 ) === 0 ) {
			$d = substr( $d, 2 );
		}
		if ( strlen( $d ) === 11 && $d[0] === '0' ) {
			$d = substr( $d, 1 );
		}
		return $d;
	}
}

if ( ! function_exists( 'ov_mask_tel' ) ) {
	function ov_mask_tel( $tel ) {
		$d = ov_normalize_tel( $tel );
		if ( strlen( $d ) !== 10 ) {
			return '***';
		}
		return substr( $d, 0, 3 ) . '***' . substr( $d, -3 );
	}
}

if ( ! function_exists( 'ov_generate_token' ) ) {
	function ov_generate_token() {
		return bin2hex( random_bytes( 24 ) );
	}
}

if ( ! function_exists( 'ov_generate_otp' ) ) {
	function ov_generate_otp() {
		return (string) random_int( 100000, 999999 );
	}
}

if ( ! function_exists( 'ov_create_or_refresh' ) ) {
	function ov_create_or_refresh( PDO $db, $siparisId, $siparisTel, $validMinutes = 20 ) {
		ov_ensure_table( $db );
		$siparisId = (int) $siparisId;
		$tel       = ov_normalize_tel( $siparisTel );
		if ( $siparisId < 1 || strlen( $tel ) !== 10 ) {
			return null;
		}

		$otp       = ov_generate_otp();
		$token     = ov_generate_token();
		$tokenHash = hash( 'sha256', $token );
		$now       = ov_now_utc();
		$exp       = gmdate( 'Y-m-d H:i:s', time() + ( max( 5, (int) $validMinutes ) * 60 ) );

		$q = $db->prepare( 'SELECT id FROM siparis_dogrulama WHERE siparis_id = :sid LIMIT 1' );
		$q->execute( array( 'sid' => $siparisId ) );
		$row = $q->fetch( PDO::FETCH_ASSOC );
		if ( $row ) {
			$u = $db->prepare(
				'UPDATE siparis_dogrulama
				 SET siparis_tel=:tel, otp_code=:otp, token_hash=:th, durum=0, dogrulama_kanali=\'\',
				     hata_sayisi=0, son_gonderim=:sg, son_kontrol=NULL, son_dogrulama=NULL, bitis_tarihi=:bt
				 WHERE siparis_id=:sid'
			);
			$u->execute(
				array(
					'tel' => $tel,
					'otp' => $otp,
					'th'  => $tokenHash,
					'sg'  => $now,
					'bt'  => $exp,
					'sid' => $siparisId,
				)
			);
		} else {
			$i = $db->prepare(
				'INSERT INTO siparis_dogrulama
				 SET siparis_id=:sid, siparis_tel=:tel, otp_code=:otp, token_hash=:th, durum=0,
				     dogrulama_kanali=\'\', hata_sayisi=0, son_gonderim=:sg, son_kontrol=NULL, son_dogrulama=NULL,
				     bitis_tarihi=:bt, olusturma_tarihi=:ot'
			);
			$i->execute(
				array(
					'sid' => $siparisId,
					'tel' => $tel,
					'otp' => $otp,
					'th'  => $tokenHash,
					'sg'  => $now,
					'bt'  => $exp,
					'ot'  => $now,
				)
			);
		}

		return array(
			'otp'        => $otp,
			'token'      => $token,
			'expires_at' => $exp,
		);
	}
}

if ( ! function_exists( 'ov_fetch_by_order' ) ) {
	function ov_fetch_by_order( PDO $db, $siparisId ) {
		ov_ensure_table( $db );
		$q = $db->prepare( 'SELECT * FROM siparis_dogrulama WHERE siparis_id = :sid LIMIT 1' );
		$q->execute( array( 'sid' => (int) $siparisId ) );
		return $q->fetch( PDO::FETCH_ASSOC ) ?: null;
	}
}

if ( ! function_exists( 'ov_is_expired' ) ) {
	function ov_is_expired( array $row ) {
		if ( empty( $row['bitis_tarihi'] ) ) {
			return true;
		}
		return strtotime( (string) $row['bitis_tarihi'] ) < time();
	}
}

if ( ! function_exists( 'ov_verify_by_token' ) ) {
	function ov_verify_by_token( PDO $db, $token, $channel = 'link' ) {
		ov_ensure_table( $db );
		$token = trim( (string) $token );
		if ( $token === '' ) {
			return array( 'ok' => false, 'reason' => 'empty' );
		}
		$th = hash( 'sha256', $token );
		$q  = $db->prepare( 'SELECT * FROM siparis_dogrulama WHERE token_hash=:th LIMIT 1' );
		$q->execute( array( 'th' => $th ) );
		$row = $q->fetch( PDO::FETCH_ASSOC );
		if ( ! $row ) {
			return array( 'ok' => false, 'reason' => 'not_found' );
		}
		if ( (int) $row['durum'] === 1 ) {
			return array( 'ok' => true, 'reason' => 'already', 'siparis_id' => (int) $row['siparis_id'] );
		}
		if ( ov_is_expired( $row ) ) {
			return array( 'ok' => false, 'reason' => 'expired', 'siparis_id' => (int) $row['siparis_id'] );
		}
		$u = $db->prepare(
			'UPDATE siparis_dogrulama
			 SET durum=1, dogrulama_kanali=:ch, son_kontrol=:sk, son_dogrulama=:sd
			 WHERE id=:id'
		);
		$now = ov_now_utc();
		$u->execute(
			array(
				'ch' => substr( (string) $channel, 0, 20 ),
				'sk' => $now,
				'sd' => $now,
				'id' => (int) $row['id'],
			)
		);
		return array( 'ok' => true, 'reason' => 'verified', 'siparis_id' => (int) $row['siparis_id'] );
	}
}

if ( ! function_exists( 'ov_verify_by_otp' ) ) {
	function ov_verify_by_otp( PDO $db, $siparisId, $otp ) {
		ov_ensure_table( $db );
		$otp = preg_replace( '/\D+/', '', (string) $otp );
		$q   = $db->prepare( 'SELECT * FROM siparis_dogrulama WHERE siparis_id=:sid LIMIT 1' );
		$q->execute( array( 'sid' => (int) $siparisId ) );
		$row = $q->fetch( PDO::FETCH_ASSOC );
		if ( ! $row ) {
			return array( 'ok' => false, 'reason' => 'not_found' );
		}
		if ( (int) $row['durum'] === 1 ) {
			return array( 'ok' => true, 'reason' => 'already' );
		}
		if ( ov_is_expired( $row ) ) {
			return array( 'ok' => false, 'reason' => 'expired' );
		}
		$now = ov_now_utc();
		if ( $otp !== (string) $row['otp_code'] ) {
			$u = $db->prepare( 'UPDATE siparis_dogrulama SET hata_sayisi=hata_sayisi+1, son_kontrol=:sk WHERE id=:id' );
			$u->execute( array( 'sk' => $now, 'id' => (int) $row['id'] ) );
			return array( 'ok' => false, 'reason' => 'invalid' );
		}
		$u = $db->prepare(
			'UPDATE siparis_dogrulama
			 SET durum=1, dogrulama_kanali=\'otp\', son_kontrol=:sk, son_dogrulama=:sd
			 WHERE id=:id'
		);
		$u->execute( array( 'sk' => $now, 'sd' => $now, 'id' => (int) $row['id'] ) );
		return array( 'ok' => true, 'reason' => 'verified' );
	}
}

if ( ! function_exists( 'ov_send_otp_sms' ) ) {
	function ov_send_otp_sms( $tel, $otp, $verifyLink ) {
		$masked = ov_mask_tel( $tel );
		$msg    = 'Siparisinizi dogrulamak icin kodunuz: ' . $otp
			. '. Link: ' . $verifyLink
			. ' (20 dk gecerli). Tel: ' . $masked;
		if ( function_exists( 'sendTransactionalSms' ) ) {
			return sendTransactionalSms( $tel, $msg );
		}
		if ( function_exists( 'netGsmSend' ) ) {
			return netGsmSend( $tel, $msg );
		}
		return false;
	}
}
