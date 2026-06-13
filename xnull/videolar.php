<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$vidsor=$db->prepare("SELECT * from videogaleri order by video_id DESC");
$vidsor->execute();
?>		
<section class="main-content container">
	<div class="page-header">
		<h2>Video İşlemleri</h2>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-heading card-default">
					<div class="pull-right mt-10">
						<a href="video-ekle.php" class="btn btn-success btn-icon"><i class="fa fa-download"></i>Video Yükle</a>
					</div>
					Video Galerisi (Eklenen Videolar 1. görselden sonra sıralanır.)
				</div>
				<div class="card-block">
					<div class="lightboxGallery">
						<?php while ($vidprint=$vidsor->fetch(PDO::FETCH_ASSOC)) { ?>
							<a href="#" data-toggle="modal" data-target="#textModal<?php echo $vidprint['video_id']; ?>"><img style="max-width: 200px; max-height: 200px; border: 1px solid #f1f1f1;" src="https://i.ytimg.com/vi/<?php echo $vidprint['video_link']; ?>/hqdefault.jpg" alt=""></a>
							<div class="modal fade bs-example-modal-lg" id="textModal<?php echo $vidprint['video_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="textModal">
								<div class="modal-dialog modal-lg" role="document">
									<div class="modal-content">
										<div class="modal-body">
											<div class="embed-responsive embed-responsive-16by9">
												<iframe width="100%" src="https://www.youtube.com/embed/<?php echo $vidprint['video_link']; ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
											</div>
										</div>
										<a href="controller/function.php?videosil=ok&video_id=<?php echo $vidprint['video_id']; ?>&video_album=<?php echo htmlspecialchars($_GET['album'] ?? ""); ?>" class="btn btn-danger btn-icon"><i class="fa fa-trash-o"></i> Video Sil</a>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div> 
	<?php include 'footer.php'; ?>
