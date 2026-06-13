-- Panel1 `siparis` tablosunda kurumsal fatura kolonları yoksa (genelde index.php ilk siparişte ALTER dener).
-- Kolonlar zaten varsa "Duplicate column" hatası alırsınız; o zaman bu satırları çalıştırmayın.

ALTER TABLE `siparis` ADD COLUMN `siparis_fatura_vn` VARCHAR(32) NOT NULL DEFAULT '' AFTER `siparis_not`;
ALTER TABLE `siparis` ADD COLUMN `siparis_fatura_vd` VARCHAR(128) NOT NULL DEFAULT '' AFTER `siparis_fatura_vn`;
ALTER TABLE `siparis` ADD COLUMN `siparis_fatura_unvan` VARCHAR(255) NOT NULL DEFAULT '' AFTER `siparis_fatura_vd`;
ALTER TABLE `siparis` ADD COLUMN `siparis_fatura_adres` TEXT NULL AFTER `siparis_fatura_unvan`;

-- Ayar: vitrinde kurumsal fatura formu (panel1 xnull genel ayarlar ile aynı)
-- ALTER TABLE `ayar` ADD COLUMN `ayar_kurumsal_fatura_on` INT(1) NOT NULL DEFAULT 0;
