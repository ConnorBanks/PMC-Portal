<?php 
if(is_user_logged_in()){ ?>
<header class="site-header">
    <?php $img = get_field('site_logo', 'options'); ?>
    <div class="logo-nav-toggle">
      <a href="<?php echo get_home_url(); ?>">
        <img class="logo" src="<?php echo $img["url"]; ?>" alt="site-logo"/>
      </a>
      <div class="off-canvas-menu-trigger">
        <i class="fa-solid fa-bars"></i>
      </div>
    </div>
   
    <nav class="main-nav" role="navigation">
      <h5>Navigation</h5>
      <?php blacksheep_custom_menu($location = 'header-menu')?>
    </nav>

    <nav class="footer-nav" role="navigation">
    <?php blacksheep_custom_menu($location = 'footer-menu')?>
    </nav>

    <div class="user-account">
      <?php
      $current_user = wp_get_current_user();
      $user_id = get_current_user_id();
    // ACF stores user fields with the 'user_' prefix
      $job_title = get_field('job_title', 'user_' . $user_id);
      ?>
      <h4><?= $current_user->user_firstname . ' ' . $current_user->user_lastname; ?> <i class="fa-solid fa-chevron-down"></i></h4>
      <p><?= $job_title; ?></h4>
      <div class="account-menu">
        <p><a href="<?= get_site_url(); ?>/user-account"><i class="fas fa-edit"></i> Edit Profile</a></p>
        <p><a href="<?= wp_logout_url( home_url()); ?>"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></p>
      </div>
      
    </div>
</header>
<?php }
else{ ?>
  <header class="site-header logged-out">
    <div class="logo-container">
      <?php $img = get_field('site_logo', 'options'); ?>
      <img class="logo" src="<?php echo $img["url"]; ?>" alt="site-logo"/>
    </div>
    <?php $img = get_field('background_image', 'options'); ?>
    <div class="company-information" style="background-image: url('<?= $img['url']; ?>');">
      <h3><?= get_bloginfo( 'name' ); ?></h3>
      <p class="company-address"><?= get_field('company_address', 'options'); ?></p>
      <p class="company-meta"><?= get_field('company_meta', 'options'); ?></p>
    </div>
  </header>
<?php
} ?>
