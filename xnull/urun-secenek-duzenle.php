<?php 
include 'header.php';
include 'topbar.php';
include 'sidebar.php';
$urunedit=$db->prepare("SELECT * from urunler where urun_id=:urun_id");
$urunedit->execute(array(
	'urun_id' => $_GET['urun_id']
));
$urunwrite=$urunedit->fetch(PDO::FETCH_ASSOC);

$secenekler = json_decode($urunwrite['secenekler']);
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
					Ürün Düzenle
				</div>
				<div class="card-block">

					<form class="form-horizontal">
						<div class="form-group">
							<input type="hidden" name="urun_id" value="<?php echo $urunwrite['urun_id']; ?>">
						</div>

						<p class="text-muted" style="font-size:13px;margin-bottom:16px;line-height:1.5;">
							<strong>Renk:</strong> vitrinde renk kutusu için <code>#hex</code> girin.
							<strong>Beden:</strong> hex alanını boş bırakın; vitrinde seçenek adı düğme olarak görünür.
							<strong>Aktif</strong> kalkanı satır sipariş ekranında listelenmez. Ek SQL gerekmez; kayıt <code>urunler.secenekler</code> JSON içinde kalır.
						</p>
						<div class="variant-container">
							<div class="header">
								<div class="title">
									Seçenekler
								</div>
								<a href="#" class="btn btn-info add-to-variant-btn">
									Üst Seçenek Ekle
								</a>
							</div>
							<div class="variant-wrapper">
								<?php if(is_array($secenekler)){ foreach($secenekler as $secenek){ ?>
									<div class="top-variant single-variant">
										<div class="title">Üst Seçenek Başlığı</div>
										<div class="row form-group" style="margin: 0;">
											<div class="input-group col-md-4">
												<input type="text" name="variant_title" class="form-control" value="<?php echo $secenek->title; ?>" placeholder="Üst Seçenek Başlığı">
											</div>     	
											<div class="input-group col-md-4">
												<input type="checkbox" name="is_required" class="form-control" <?php echo $secenek->is_required ? 'checked': ''; ?>> Seçmek zorunlu olsun mu?
											</div>
											<div class="col-md-4">
												<a href="#" class="btn btn-danger delete-variant-btn">Üst Seçeneği Sil</a>
											</div>
										</div>
										<div class="row sub-variant" style="margin: 0; margin-top: 20px;">
											<div class="col-md-12">
												<div class="header">
													<div class="title">
														Alt Seçenekler
													</div>
													<a href="#" class="btn btn-info add-to-sub-variant-btn">
														Alt Seçenek Ekle
													</a>
												</div>
											</div>
											<div class="sub-variant-wrapper">
												<?php foreach($secenek->sub as $sub){
													if(isset($sub->title) && isset($sub->value)){
														$sub_hex = isset($sub->color_hex) ? htmlspecialchars((string)$sub->color_hex, ENT_QUOTES, 'UTF-8') : '';
														$sub_active = true;
														if (isset($sub->active)) {
															$sub_active = !($sub->active === false || $sub->active === 0 || $sub->active === '0');
														}
														?>
														<div class="col-md-12 bot-variant">
															<div class="single-variant" style="border: 0;">
																<div class="form-group row" style="margin: 0; align-items: center;">
																	<div class="input-group col-md-3">
																		<input type="text" name="sub_variant_title" class="form-control" value="<?php echo htmlspecialchars($sub->title, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Alt seçenek (örn. Pastel Mavi #5024)">
																	</div>
																	<div class="input-group col-md-2">
																		<input type="text" name="sub_variant_value" class="form-control" value="<?php echo htmlspecialchars((string)$sub->value, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Fiyat (+₺)">
																	</div>
																	<div class="input-group col-md-2">
																		<input type="text" name="sub_variant_color_hex" class="form-control" value="<?php echo $sub_hex; ?>" placeholder="#hex (beden için boş)" title="Renk kutusu için #rgb veya #rrggbb. Beden vb. için boş bırakın; ad metin düğmesi olur.">
																	</div>
																	<div class="input-group col-md-2" style="display:flex;align-items:center;padding:6px 10px;">
																		<label style="margin:0;font-weight:600;cursor:pointer;"><input type="checkbox" name="sub_variant_active" <?php echo $sub_active ? 'checked' : ''; ?>> Aktif</label>
																	</div>
																	<div class="col-md-3">
																		<a href="#" class="btn btn-danger delete-variant-btn">Alt Seçeneği Sil</a>
																	</div>
																</div>
															</div>
														</div>
													<?php }} ?>
												</div>
											</div>
										</div>
									<?php } } ?>
								</div>
							</div>




							<a style="cursor: pointer;" class="btn btn-success btn-icon btn-save"><i class="fa fa-floppy-o "></i>Güncelle</a>
						</form>
					</div>
				</div>
			</div>
		</div>

		<script>
			$(document).ready(function(){

				$('.btn-save').on('click', function(e){
					e.preventDefault();

					var queryData = {
						urun_id: $('input[name=urun_id]').val(),
						variants: [],
					};

					$('.variant-wrapper').find('.top-variant').each(function(){
						var variantTitle = $(this).find('input[name=variant_title]').val();
						var variantIsRequired = $(this).find('input[name=is_required]').prop('checked');

						var variant = {
							title: variantTitle,
							is_required: variantIsRequired,
						};

						var subs = [];

						$(this).find('.sub-variant-wrapper').find('.bot-variant').each(function(){
							var subVariantTitle = $(this).find('input[name=sub_variant_title]').val();
							var subVariantValue = $(this).find('input[name=sub_variant_value]').val();
							var subColorHex = $(this).find('input[name=sub_variant_color_hex]').val() || '';
							var subActive = $(this).find('input[name=sub_variant_active]').prop('checked');

							subs.push({
								title: subVariantTitle,
								value: subVariantValue,
								color_hex: subColorHex,
								active: subActive
							});

						});

						variant.sub = subs;

						queryData.variants.push(variant);
					});

					$.ajax({
						url: 'controller/urun-secenek-kaydet.php',
						type: 'POST',
						dataType: 'json',
						data: JSON.stringify(queryData),
						success: function (result) {
							if(result.status){
								window.location = result.url;
							} else {
								
								alert('Sistem demo moddadır! Düzenlemeye izin verilmez.');
							}
						},
						error: function (result) {
							alert('Güncellenirken bir hata oluştu');
						}
					});
				})

				$('.add-to-variant-btn').on('click', function(e){
					e.preventDefault();

					let variant = `
					<div class="top-variant single-variant">
					<div class="title">Üst Seçenek Başlığı</div>
					<div class="row form-group" style="margin: 0;">
					<div class="input-group col-md-4">
					<input type="text" name="variant_title" class="form-control" placeholder="Üst Seçenek Başlığı">
					</div>     	
					<div class="input-group col-md-4">
					<input type="checkbox" name="is_required" class="form-control"> Seçmek zorunlu olsun mu?
					</div>
					<div class="col-md-4">
					<a href="#" class="btn btn-danger delete-variant-btn">Üst Seçeneği Sil</a>
					</div>
					</div>
					<div class="row sub-variant" style="margin: 0; margin-top: 20px;">
					<div class="col-md-12">
					<div class="header">
					<div class="title">
					Alt Seçenekler
					</div>
					<a href="#" class="btn btn-info add-to-sub-variant-btn">
					Alt Seçenek Ekle
					</a>
					</div>
					</div>
					<div class="sub-variant-wrapper"></div>
					</div>
					</div>`;

					$('.variant-wrapper').append(variant);
				});

				$(document).on('click', '.add-to-sub-variant-btn', function(e){
					e.preventDefault();

					let subVariant = `
					<div class="col-md-12 bot-variant">
					<div class="single-variant" style="border: 0;">
					<div class="form-group row" style="margin: 0; align-items: center;">
					<div class="input-group col-md-3">
					<input type="text" name="sub_variant_title" class="form-control" placeholder="Alt seçenek">
					</div>
					<div class="input-group col-md-2">
					<input type="text" name="sub_variant_value" class="form-control" placeholder="Fiyat (+₺)">
					</div>
					<div class="input-group col-md-2">
					<input type="text" name="sub_variant_color_hex" class="form-control" placeholder="#hex (beden için boş)">
					</div>
					<div class="input-group col-md-2" style="display:flex;align-items:center;padding:6px 10px;">
					<label style="margin:0;font-weight:600;cursor:pointer;"><input type="checkbox" name="sub_variant_active" checked> Aktif</label>
					</div>
					<div class="col-md-3">
					<a href="#" class="btn btn-danger delete-variant-btn">Alt Seçeneği Sil</a>
					</div>
					</div>
					</div>
					</div>`;

					$(this).closest('.sub-variant').find('.sub-variant-wrapper').append(subVariant);
				})

				$(document).on('click', '.delete-variant-btn', function(e){
					e.preventDefault();

					$(this).closest('.single-variant').remove();
				})
			});
		</script>

		<style>
			.variant-container{
				margin: 20px 0;
			}

			.variant-container .header{
				display: flex;
				flex-direction: row;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}

			.single-variant{
				border: 1px solid black;
				padding: 10px;
			}

			.sub-variant{
				margin-top: 20px;
			}
		</style>

		<?php include 'footer.php'; ?>
