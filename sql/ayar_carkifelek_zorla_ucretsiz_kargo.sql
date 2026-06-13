-- Çarkıfelek: "Ücretsiz kargo zorunlu" anahtarı (ödül listesinde kargo ödülü varsa her zaman onu verir).
-- Kolon zaten varsa hata verir; o zaman bu satırı çalıştırmayın.

ALTER TABLE `ayar`
  ADD COLUMN `ayar_carkifelek_zorla_ucretsiz_kargo` INT(1) NOT NULL DEFAULT 0
  COMMENT '1=listede ucretsiz kargo satiri varsa cekilis her zaman bu odul'
  AFTER `ayar_carkifelek_auto_sn`;
