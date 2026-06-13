<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
?>		
<!-- ============================================================== -->
<!-- 						Content Start	 						-->
<!-- ============================================================== -->
<section class="main-content container">
	<div class="page-header">
		<h2>Resim İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="gorseller.php" class="btn btn-warning btn-icon"><i class="fa fa-reply"></i>Geri Dön</a>
					</div>
					Resim Ekle <small>Resimleri görmek için galeriye gidin.<a href="gorseller.php" class="btn btn-warning">Galeri</a></small>
				</div>
				<div class="card-block">
					<form action="controller/galeri.php" class="dropzone" id="image-dropzone"></form>
					<script>
					Dropzone.options.imageDropzone = {
						paramName: "file",
						maxFilesize: 100,
						acceptedFiles: ".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.mov",
						addRemoveLinks: true,
						dictDefaultMessage: "Görselleri buraya sürükleyin veya tıklayın (JPG, PNG, GIF, WebP, MP4, WebM, MOV - Max 100MB)",
						dictRemoveFile: "Kaldır",
						dictCancelUpload: "İptal",
						dictUploadCanceled: "Yükleme iptal edildi",
						dictInvalidFileType: "Bu dosya tipi kabul edilmiyor",
						dictFileTooBig: "Dosya çok büyük (Max: {{maxFilesize}}MB)",
						init: function() {
							this.on("success", function(file, response) {
								var data = response;
								if (typeof response === 'string') {
									try { data = JSON.parse(response); } catch (e) { data = null; }
								}
								if (data && data.status === 'ok') {
									window.location.href = 'gorseller.php?status=ok';
									return;
								}
								var msg = (data && data.message) ? data.message : 'Yükleme tamamlanamadı.';
								file.previewElement && file.previewElement.classList.add('dz-error');
								alert(msg);
							});
							this.on("error", function(file, errorMessage, xhr) {
								var msg = errorMessage;
								if (xhr && xhr.responseText) {
									try {
										var j = JSON.parse(xhr.responseText);
										if (j.message) msg = j.message;
									} catch (e) {}
								}
								alert(typeof msg === 'string' ? msg : 'Yükleme hatası.');
							});
						}
					};
					</script>
				</div>
			</div>
		</div>
	</div>

	<?php include 'footer.php'; ?>
