-- Fiyat birimi satırı: vitrinde göster + yazı büyüklüğü çarpanı
-- urun_fiyat_birim_metin / urun_fiyat_birim_renk zaten ekli olmalı

ALTER TABLE `urunler`
  ADD COLUMN `urun_fiyat_birim_goster` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1=vitrinde birim satırı göster'
    AFTER `urun_fiyat_birim_renk`,
  ADD COLUMN `urun_fiyat_birim_olcek` DECIMAL(4,2) NOT NULL DEFAULT 1.00
    COMMENT 'Birim yazısı çarpanı (örn: 1.20)'
    AFTER `urun_fiyat_birim_goster`;
