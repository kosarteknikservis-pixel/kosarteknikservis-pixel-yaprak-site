<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknoloji ve Yaşam Rehberi | Güncel İncelemeler</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; }
        .hero-section {
            background-color: #2c3e50;
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .icon-box {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: white;
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        .bg-tech { background: #3498db; }
        .bg-nature { background: #2ecc71; }
        .bg-health { background: #e74c3c; }
        .bg-travel { background: #f1c40f; }
        
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card-body { padding: 2rem; }
        .badge-custom { padding: 8px 12px; font-weight: normal; font-size: 0.85rem; }
        
        .sidebar-widget { background: white; padding: 25px; margin-bottom: 25px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .sidebar-title { font-size: 1.1rem; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; color: #2c3e50; }
        
        .footer { background: #2c3e50; color: #bdc3c7; padding: 50px 0; margin-top: 50px; }
        .footer a { color: #ecf0f1; text-decoration: none; }
        .footer a:hover { color: #3498db; }
        
        .author-box { display: flex; align-items: center; margin-bottom: 20px; }
        .author-avatar { width: 50px; height: 50px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 1.5rem; color: #555; }
        
        .comment-section { background: #fff; padding: 20px; margin-top: 20px; border-radius: 4px; }
        .comment-item { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .comment-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-rss-square mr-2"></i> TechGuide</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active"><a class="nav-link" href="#">Ana Sayfa</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">İncelemeler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Rehberler</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Hakkımızda</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero-section">
        <div class="container">
            <h1 class="font-weight-bold">Dijital Dünyayı Keşfedin</h1>
            <p class="lead mt-3">En son teknoloji haberleri, yazılım ipuçları ve dijital yaşam rehberi.</p>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Ana İçerik -->
            <div class="col-lg-8">
                
                <!-- Makale 1 -->
                <article class="card">
                    <div class="icon-box bg-tech">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div class="card-body">
                        <span class="badge badge-primary badge-custom mb-2">Yazılım</span>
                        <h2 class="card-title h4 mt-2">Modern Web Geliştirme Trendleri</h2>
                        <div class="author-box">
                            <div class="author-avatar"><i class="fas fa-user"></i></div>
                            <div>
                                <small class="d-block font-weight-bold">Ahmet Yılmaz</small>
                                <small class="text-muted">4 Şubat 2026 &bull; 5 dk okuma</small>
                            </div>
                        </div>
                        <p class="card-text text-justify">
                            Günümüzde web teknolojileri inanılmaz bir hızla gelişiyor. Artık web siteleri sadece bilgi veren statik sayfalar değil, kullanıcıyla etkileşime giren dinamik uygulamalar haline geldi. Özellikle React, Vue ve Angular gibi framework'lerin yükselişi, frontend geliştirme dünyasını tamamen değiştirdi.
                        </p>
                        <p class="card-text text-justify">
                            Bu makalede, modern bir web geliştiricinin çantasında bulunması gereken araçlardan bahsedeceğiz. Öncelikle, versiyon kontrol sistemleri (Git) artık bir lüks değil, zorunluluk. Takım çalışmasının temel taşı olan Git, kodunuzun tarihçesini güvenli bir şekilde tutmanızı sağlar.
                        </p>
                        <p class="card-text text-justify">
                            İkinci olarak, CSS pre-processor'ları (Sass, Less) ve modern CSS framework'lerini (Tailwind) öğrenmek, tasarım sürecinizi ciddi oranda hızlandıracaktır.
                        </p>
                        <a href="#" class="btn btn-primary mt-3">Devamını Oku &rarr;</a>
                    </div>
                </article>

                <!-- Makale 2 -->
                <article class="card">
                    <div class="icon-box bg-nature">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="card-body">
                        <span class="badge badge-success badge-custom mb-2">Yaşam</span>
                        <h2 class="card-title h4 mt-2">Ofis Çalışanları İçin Sağlıklı Yaşam İpuçları</h2>
                        <div class="author-box">
                            <div class="author-avatar"><i class="fas fa-user-circle"></i></div>
                            <div>
                                <small class="d-block font-weight-bold">Elif Demir</small>
                                <small class="text-muted">3 Şubat 2026 &bull; 7 dk okuma</small>
                            </div>
                        </div>
                        <p class="card-text text-justify">
                            Bütün gün bilgisayar başında oturmak, uzun vadede bel ve boyun ağrılarına, göz yorgunluğuna ve genel bir enerji düşüklüğüne neden olabilir. Ancak küçük değişikliklerle bu etkileri minimize etmek mümkün.
                        </p>
                        <p class="card-text text-justify">
                            Özellikle "20-20-20 Kuralı"nı hayatınıza entegre etmelisiniz. Her 20 dakikada bir, 20 saniye boyunca, 20 metre (veya daha uzak) bir mesafeye odaklanın. Bu, göz kaslarınızı dinlendirir ve baş ağrılarını önler.
                        </p>
                        <a href="#" class="btn btn-outline-success mt-3">Tüm İpuçlarını Gör &rarr;</a>
                    </div>
                </article>

                 <!-- Makale 3 -->
                 <article class="card">
                    <div class="icon-box bg-travel">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="card-body">
                        <span class="badge badge-warning text-white badge-custom mb-2">Fotoğrafçılık</span>
                        <h2 class="card-title h4 mt-2">Akıllı Telefonla Profesyonel Fotoğraf Çekmek</h2>
                        <div class="author-box">
                            <div class="author-avatar"><i class="fas fa-camera-retro"></i></div>
                            <div>
                                <small class="d-block font-weight-bold">Caner Erkin</small>
                                <small class="text-muted">1 Şubat 2026 &bull; 4 dk okuma</small>
                            </div>
                        </div>
                        <p class="card-text text-justify">
                            En iyi kamera, o an yanınızda olan kameradır. Günümüz akıllı telefonları, 5 yıl önceki DSLR makinelerle yarışacak kalitede sensörlere sahip. Peki bu potansiyeli tam olarak kullanıyor musunuz?
                        </p>
                        <p class="card-text text-justify">
                            Işığı doğru kullanmak, kompozisyon kurallarına (1/3 kuralı) dikkat etmek ve basit düzenleme uygulamaları (Snapseed, Lightroom Mobile) kullanmak, fotoğraflarınızı bir üst seviyeye taşıyacaktır.
                        </p>
                    </div>
                </article>

            </div>

            <!-- Sidebar -->
            <aside class="col-lg-4">
                
                <!-- About Widget -->
                <div class="sidebar-widget">
                    <div class="text-center">
                        <i class="fas fa-quote-left fa-2x text-primary mb-3"></i>
                        <p class="font-italic">"Teknoloji, insanlığın potansiyelini açığa çıkaran en güçlü araçtır. Biz de bu aracın en verimli nasıl kullanılacağını anlatıyoruz."</p>
                        <hr>
                        <h5 class="sidebar-title" style="border:none; margin:0; padding:0;">Editörün Seçimi</h5>
                    </div>
                </div>

                <!-- Popular Posts -->
                <div class="sidebar-widget">
                    <h5 class="sidebar-title">Popüler İçerikler</h5>
                    <div class="media mb-3">
                        <div class="mr-3 text-primary"><i class="fas fa-star fa-2x"></i></div>
                        <div class="media-body">
                            <h6 class="mt-0"><a href="#" class="text-dark">Python ile Veri Analizine Başlangıç</a></h6>
                            <small class="text-muted">12.5k Okuma</small>
                        </div>
                    </div>
                    <div class="media mb-3">
                        <div class="mr-3 text-success"><i class="fas fa-dollar-sign fa-2x"></i></div>
                        <div class="media-body">
                            <h6 class="mt-0"><a href="#" class="text-dark">Freelance Çalışarak Gelir Elde Etmek</a></h6>
                            <small class="text-muted">8.2k Okuma</small>
                        </div>
                    </div>
                    <div class="media">
                        <div class="mr-3 text-danger"><i class="fas fa-heartbeat fa-2x"></i></div>
                        <div class="media-body">
                            <h6 class="mt-0"><a href="#" class="text-dark">Mental Sağlığınızı Koruyun</a></h6>
                            <small class="text-muted">6.9k Okuma</small>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="sidebar-widget">
                    <h5 class="sidebar-title">Kategoriler</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Teknoloji
                            <span class="badge badge-primary badge-pill">14</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Sağlık & Yaşam
                            <span class="badge badge-success badge-pill">8</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Seyahat
                            <span class="badge badge-warning badge-pill">5</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Kariyer
                            <span class="badge badge-info badge-pill">3</span>
                        </li>
                    </ul>
                </div>

                <!-- Tags -->
                <div class="sidebar-widget">
                    <h5 class="sidebar-title">Etiketler</h5>
                    <span class="badge badge-secondary m-1 p-2">#yazılım</span>
                    <span class="badge badge-secondary m-1 p-2">#donanım</span>
                    <span class="badge badge-secondary m-1 p-2">#yapayzeka</span>
                    <span class="badge badge-secondary m-1 p-2">#sağlık</span>
                    <span class="badge badge-secondary m-1 p-2">#doğa</span>
                    <span class="badge badge-secondary m-1 p-2">#fotoğraf</span>
                    <span class="badge badge-secondary m-1 p-2">#kariyer</span>
                    <span class="badge badge-secondary m-1 p-2">#eğitim</span>
                </div>

            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>TechGuide Hakkında</h5>
                    <p class="small text-muted">TechGuide, teknolojiyi sade ve anlaşılır bir dille anlatan, kullanıcı odaklı bir dijital yayın platformudur. Amacımız, karmaşık teknik konuları herkesin anlayabileceği bir seviyeye indirmektir.</p>
                </div>
                <div class="col-md-3">
                    <h5>Kurumsal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Künye</a></li>
                        <li><a href="#">Reklam Verin</a></li>
                        <li><a href="#">İletişim</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Yasal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Gizlilik Politikası</a></li>
                        <li><a href="#">Çerez Politikası</a></li>
                        <li><a href="#">Kullanım Koşulları</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p class="small mb-0">&copy; 2026 TechGuide Blog. Tüm hakları saklıdır. İzinsiz kopyalanamaz.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
