<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>
<section class="main-content container">
    <div class="page-header">
        <h2>Yeni Sayfa Ekle</h2>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-block">
                    <form method="POST" action="controller/function.php" class="form-horizontal" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Üst Sayfa</label>
                            <select name="sayfa_id" class="form-control">
                                <option value="0">Ana Sayfa (Üst Sayfa Yok)</option>
                                <?php 
                                $sayfasor=$db->prepare("SELECT * from sayfalar WHERE sayfa_id=0 order by sayfa_sira ASC");
                                $sayfasor->execute();
                                while($sayfacek=$sayfasor->fetch(PDO::FETCH_ASSOC)) { ?>
                                    <option value="<?php echo $sayfacek['id']; ?>"><?php echo $sayfacek['sayfa_baslik']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Sayfa Başlığı</label>
                            <input type="text" name="sayfa_baslik" id="sayfa_baslik" class="form-control" required placeholder="Örn: Hakkımızda">
                        </div>
                        <div class="form-group">
                            <label>SEO URL (Slug) <small>Boş bırakırsanız başlıktan otomatik oluşturulur.</small></label>
                            <input type="text" name="sayfa_slug" id="sayfa_slug" class="form-control" placeholder="Örn: hakkimizda">
                        </div>
                        <div class="form-group">
                            <label>Sayfa Görseli</label>
                            <input type="file" name="ayar_altgorsel" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sayfa İçeriği</label>
                            <textarea name="sayfa_icerik" class="summernote"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Menüde Göster</label>
                                    <select name="sayfa_menu" class="form-control">
                                        <option value="1">Evet</option>
                                        <option value="0">Hayır</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sıralama</label>
                                    <input type="number" name="sayfa_sira" value="0" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Durum</label>
                                    <select name="sayfa_durum" class="form-control">
                                        <option value="1">Aktif</option>
                                        <option value="0">Pasif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label>SEO Title</label>
                            <input type="text" name="sayfa_title" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Description</label>
                            <input type="text" name="sayfa_descr" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SEO Keywords</label>
                            <input type="text" name="sayfa_keyword" class="form-control">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Canonical URL <small>(İsteğe Bağlı)</small></label>
                                    <input type="text" name="sayfa_canonical" class="form-control" placeholder="Örn: https://site.com/orijinal-sayfa">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Robots Meta Tag</label>
                                    <select name="sayfa_robots" class="form-control">
                                        <option value="index, follow">Index, Follow (Varsayılan)</option>
                                        <option value="noindex, follow">Noindex, Follow</option>
                                        <option value="index, nofollow">Index, Nofollow</option>
                                        <option value="noindex, nofollow">Noindex, Nofollow</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Yazar (Author)</label>
                                    <input type="text" name="sayfa_author" class="form-control" placeholder="Yazar adı">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>Sosyal Medya ve Schema Ayarları (Opsiyonel)</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>OG Title (Sosyal Medya Başlığı)</label>
                                    <input type="text" name="sayfa_og_title" class="form-control" placeholder="Boş bırakırsanız SEO Title kullanılır">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>OG Description (Sosyal Medya Açıklaması)</label>
                                    <input type="text" name="sayfa_og_description" class="form-control" placeholder="Boş bırakırsanız SEO Description kullanılır">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Schema Type (Yapısal Veri Türü)</label>
                                    <select name="sayfa_schema_type" class="form-control">
                                        <option value="Article">Makale (Article)</option>
                                        <option value="WebPage">Web Sayfası (WebPage)</option>
                                        <option value="AboutPage">Hakkımızda Sayfası (AboutPage)</option>
                                        <option value="ContactPage">İletişim Sayfası (ContactPage)</option>
                                        <option value="Product">Ürün (Product)</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label>OG Görseli (URL)</label>
                                     <input type="text" name="sayfa_og_image" class="form-control" placeholder="Özel bir görsel URL'si (Boşsa sayfa resmi kullanılır)">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="sayfaekle" class="btn btn-primary btn-block"><i class="fa fa-save"></i> Sayfayı Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
// Gelişmiş Slug Oluşturma Fonksiyonu - Türkçe Karakter Desteği
(function() {
    'use strict';
    
    // Türkçe karakter haritası
    const turkishMap = {
        'ş': 's', 'Ş': 's',
        'ğ': 'g', 'Ğ': 'g',
        'ü': 'u', 'Ü': 'u',
        'ö': 'o', 'Ö': 'o',
        'ç': 'c', 'Ç': 'c',
        'ı': 'i', 'İ': 'i'
    };
    
    function createSlug(text) {
        if (!text) return '';
        
        // Türkçe karakterleri dönüştür
        let slug = text.split('').map(char => turkishMap[char] || char).join('');
        
        // Küçük harfe çevir ve temizle
        slug = slug.toLowerCase()
            .trim()
            .replace(/['"]/g, '')           // Tırnak işaretlerini kaldır
            .replace(/[^a-z0-9\s-]/g, '')   // Sadece harf, rakam, boşluk ve tire
            .replace(/\s+/g, '-')           // Boşlukları tire yap
            .replace(/-+/g, '-')            // Çoklu tireleri tek tire yap
            .replace(/^-+|-+$/g, '');       // Baş ve sondaki tireleri kaldır
        
        return slug;
    }
    
    // Element kontrolü ile güvenli event listener
    const baslikInput = document.getElementById('sayfa_baslik');
    const slugInput = document.getElementById('sayfa_slug');
    
    if (baslikInput && slugInput) {
        baslikInput.addEventListener('blur', function() {
            if (slugInput.value.trim() === '') {
                slugInput.value = createSlug(this.value);
            }
        });
        
        // Gerçek zamanlı önizleme (opsiyonel)
        baslikInput.addEventListener('input', function() {
            if (slugInput.value.trim() === '') {
                slugInput.setAttribute('placeholder', createSlug(this.value));
            }
        });
    }
})();
</script>

<?php include 'footer.php'; ?>
