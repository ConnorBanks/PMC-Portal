<section class="off-canvas-menu">
  <div class="off-canvas-menu-content">
    <?php //get_template_part('templates/partials/mobile-search-container'); ?>
    <div class="main-menu">
      <?php wp_nav_menu(array('theme_location'=>'mobile-menu')); ?>
    </div>
    <div class="call-to-action">
        <p class="btn btn"><a target="_blank" href="#">Shop<i class="fas fa-external-link"></i></a></p>
        <!-- <p class="btn btn-second"><a href="/contact">Contact</a></p> -->
    </div>
    <?php get_template_part('templates/partials/social-media'); ?>
    <?php wp_nav_menu(array('theme_location'=>'legal-menu')); ?>
    <p>Safety Devices Ltd &copy <?= date("Y"); ?></p>
  </div>
</section>
