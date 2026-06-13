<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>      
<section class="main-content container">
    <div class="page-header">
        <h2>Ürün İşlemleri</h2>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-heading card-default">
                    <div class="pull-right mt-10">
                        <a href="urunler.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
                    </div>
                    Ürün Ekle
                </div>
                <div class="card-block">

                    <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Ürün Başlık</label>
                            <textarea class="summernote" name="urun_baslik"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Ürün Alt Başlık (Yeni Tasarım İçin)</label> <small>Örn: Kargo Ücretsiz</small>
                            <input type="text" name="urun_alt_baslik" class="form-control" placeholder="Ürün adı altında görünecek kısa yazı">
                        </div>
                        <div class="form-group">
                            <label>Ürün Etiketi (Badge)</label> <small>Örn: AVANTAJLI PAKET, ÇOK SATAN</small>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="text" name="urun_etiket" class="form-control" placeholder="Etiket Metni">
                                </div>
                                <div class="col-md-3">
                                    <label><small>Arkaplan Rengi</small></label>
                                    <input type="color" name="urun_etiket_bg" class="form-control" value="#2c3e50" style="height: 38px; padding: 2px;">
                                </div>
                                <div class="col-md-3">
                                    <label><small>Yazı Rengi</small></label>
                                    <input type="color" name="urun_etiket_color" class="form-control" value="#ffffff" style="height: 38px; padding: 2px;">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>SEO Ayarları / Etiketler</h4>
                        <div class="form-group">
                            <label>SEO Başlık (Title)</label> <small>Google'da görünecek başlık (Boş bırakılırsa ürün adı kullanılır)</small>
                            <input type="text" name="urun_seo_baslik" class="form-control" placeholder="Örn: En Ucuz X Ürünü - Marka Adı">
                        </div>
                        <div class="form-group">
                            <label>SEO Açıklama (Description)</label> <small>Google arama sonuçlarında çıkan kısa açıklama</small>
                            <textarea name="urun_seo_aciklama" class="form-control" rows="2" placeholder="Ürün içeriği hakkında kısa bilgi..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Etiketler / Anahtar Kelimeler (Keywords)</label> <small>Virgül ile ayırın: etiket1, etiket2, etiket3</small>
                            <input type="text" name="urun_seo_anahtar" class="form-control" placeholder="tag1, tag2, tag3">
                        </div>
                        <hr>

                        <div class="form-group">
                            <label>Ürün Fiyat</label>
                            <div class="input-group col-md-4">
                                <span class="input-group-addon"><i class="fa fa-try"></i></span>
                                <input type="text" class="form-control" name="urun_fiyat" placeholder="Ürün Fiyatı Giriniz...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Eski Fiyat (Üstü Çizili)</label> <small>İndirimli görünüm için normal fiyattan yüksek girin.</small>
                            <div class="input-group col-md-4">
                                <span class="input-group-addon"><i class="fa fa-try"></i></span>
                                <input type="text" class="form-control" name="urun_eski_fiyat" placeholder="Örn: 1500">
                            </div>
                        </div>

                        <div class="card" style="margin-bottom: 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden;">
                            <div class="card-heading card-default" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; padding: 12px 16px; margin: 0;">
                                <strong style="color: #334155;"><i class="fa fa-tag" style="color: #0d9488;"></i> Fiyat birimi (vitrin)</strong>
                                <small class="text-muted" style="display: block; margin-top: 4px; font-weight: normal;">Kapalıyken sitede gösterilmez. Açıkken metin doluysa fiyat altında görünür.</small>
                            </div>
                            <div class="card-block" style="padding: 16px 18px; background: #fff;">
                                <div class="form-group" style="margin-bottom: 14px;">
                                    <label class="checkbox-inline" style="font-weight: 600; cursor: pointer; user-select: none;">
                                        <input type="hidden" name="urun_fiyat_birim_goster" value="0">
                                        <input type="checkbox" name="urun_fiyat_birim_goster" value="1" style="margin-top: 2px;">
                                        Vitrinde göster (açık / kapalı)
                                    </label>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Birim metni</label>
                                            <input type="text" name="urun_fiyat_birim_metin" class="form-control" maxlength="64" placeholder="örn: / 1 m²">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Yazı rengi</label>
                                            <input type="color" name="urun_fiyat_birim_renk" class="form-control" value="#64748b" style="height: 38px; padding: 2px;">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Yazı büyüklüğü</label>
                                            <input type="number" name="urun_fiyat_birim_olcek" class="form-control" step="0.05" min="0.5" max="2.5" value="1.00" title="0.5 – 2.5 arası çarpan">
                                            <small class="text-muted">1.0 = normal, 1.2 = %20 büyük</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Ürün Resmi</label>
                            <input class="form-control" type="file" name="urun_resim">
                        </div>
                        <div class="form-group">
                            <label>Ürün Seçildi Resmi</label>
                            <input class="form-control" type="file" name="urun_resimsec">
                        </div>
                        <div class="form-group">
                            <label>Sipariş kartı görseli (sipariş tamamlama sayfası)</label>
                            <small class="text-muted" style="display: block; margin-bottom: 6px;">İsteğe bağlı. Bu ürün için sipariş ekranında vitrin görseli yerine gösterilir. Boş bırakırsanız vitrin ürün resmi kullanılır.</small>
                            <input class="form-control" type="file" name="urun_siparis_kart" accept="image/jpeg,image/png,image/webp,image/gif">
                        </div>
                        <div class="form-group">
                            <label>Ürün Sırası</label>
                            <input type="number" name="urun_siralama" class="form-control" value="0">
                        </div>
                        <button style="cursor: pointer;" type="submit" name="urunekle" class="btn btn-success btn-icon"><i class="fa fa-floppy-o "></i>Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
