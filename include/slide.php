


<!-- Inspiro Slider -->
<div id="slider" class="inspiro-slider slider-halfscreen arrows-large arrows-creative dots-creative"  data-height-xs="360" data-autoplay-timeout="2600" data-animate-in="fadeIn" data-animate-out="fadeOut" data-items="1" data-loop="true" data-autoplay="true">
 <?php 
 $slider=$db->prepare("SELECT * from slayt order by slayt_sira DESC");
 $slider->execute();
 while($sliderprint=$slider->fetch(PDO::FETCH_ASSOC)) { 
   ?>
   <!-- Slide 1 -->
   <div class="slide background-overlay-one background-image" style="background-image:url('xnull/<?php echo $sliderprint['slayt_resim']; ?>');">
    <div class="container">
      <div class="slide-captions text-center">
        <!-- Captions -->
        <h2 style="color: <?php echo $sliderprint['slayt_renk']; ?>;" class="text-uppercase text-medium"><?php echo $sliderprint['slayt_baslik']; ?></h2>
        <p><b style="color: <?php echo $sliderprint['slayt_renk']; ?>; font-weight: normal;"><p><?php echo $sliderprint['slayt_aciklama']; ?></p></b></p>
        <?php
        $kontrol=strlen($sliderprint['slayt_butonad']);
        if ($kontrol>0) { ?>
        <a class="btn btn-default" href="<?php echo $sliderprint['slayt_butonlink']; ?>"><?php echo $sliderprint['slayt_butonad']; ?></a>
        <?php } ?>
        <!-- end: Captions -->
      </div>
    </div>
  </div>
  <!-- end: Slide 1 -->

  <?php } ?>
</div>
<!--end: Inspiro Slider -->
