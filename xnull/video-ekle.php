<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Galeri İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="gorseller.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Video Ekle
				</div>
				<div class="card-block">
					<!-- YouTube Video Ekleme -->
					<div class="form-group" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #5cb85c;">
						<h4 style="margin-top: 0; color: #5cb85c;"><i class="fa fa-youtube"></i> YouTube Video Ekle</h4>
						<form method="POST" action="controller/function.php" class="form-horizontal">						
							<div class="form-group">
								<label>Youtube Video Kodu</label> 
								<input type="text" name="video_link" placeholder="Kod giriniz." class="form-control">
								<span class="help-block">Linkin sonunda bulunan örn. <code>Zbr3GwHg31I</code> yazan kodu kopyalayınız. <a href="assets/img/genel/youtube.jpg" target="_blank">(Örnek Resim)</a></span>
							</div>
							<button style="cursor: pointer;" type="submit" name="videoekle_youtube" class="btn btn-success btn-icon"><i class="fa fa-youtube"></i> YouTube Video Kaydet</button>
						</form>
					</div>
					
					<hr style="margin: 30px 0; border-color: #ddd;">
					
					<!-- MP4 Video Dosyası Yükleme -->
					<div class="form-group" style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #337ab7;">
						<h4 style="margin-top: 0; color: #337ab7;"><i class="fa fa-video-camera"></i> MP4 Video Dosyası Yükle</h4>
						<form action="controller/galeri.php" class="dropzone" id="video-dropzone">
							<input type="hidden" name="video_type" value="mp4">
						</form>
						<script>
						Dropzone.options.videoDropzone = {
							paramName: "file",
							maxFilesize: 100, // 100MB
							acceptedFiles: ".mp4,.webm,.mov",
							addRemoveLinks: true,
							dictDefaultMessage: "MP4, WebM veya MOV video dosyalarını buraya sürükleyin veya tıklayın (Max 100MB)",
							dictRemoveFile: "Kaldır",
							dictCancelUpload: "İptal",
							dictUploadCanceled: "Yükleme iptal edildi",
							dictInvalidFileType: "Bu dosya tipi kabul edilmiyor (Sadece MP4, WebM, MOV)",
							dictFileTooBig: "Dosya çok büyük (Max: {{maxFilesize}}MB)",
							init: function() {
								this.on("success", function(file, response) {
									
									setTimeout(function() {
										window.location.href = "gorseller.php?status=ok";
									}, 1000);
								});
								this.on("error", function(file, errorMessage) {
									console.error("Yükleme hatası: " + errorMessage);
									alert("Video yükleme hatası: " + errorMessage);
								});
							}
						};
						</script>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>
