<?php
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Maintenance / Holding Mode Feature
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function maintenance() {
  // we show this message if
  // 1. you're not logged in
  // 2. you're not on /wp-admin/ (index.php is redirecting you to wp-login.php)
  // 3. you're not on login page
  if( !is_user_logged_in() && !is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php'))) {
    $period = 3 * HOUR_IN_SECONDS; // 3 hours, but you can change if you need
      header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable', true, 503);
      header('Retry-After: ' . $period);?>
      <link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri() . '/assets/style/style.css'; ?>">
      <link href="https://fonts.googleapis.com/css?family=Cinzel|Indie+Flower|Playfair+Display|Roboto+Condensed|Shadows+Into+Light" rel="stylesheet">
      <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  		<meta name="viewport" content="width=device-width, initial-scale=1.0"><?php
      $mode = get_field('mode', 'maintenance-mode');
      if($mode = 'coming-soon'){
        get_template_part('templates/maintenance/holding');
      }
      else if($mode = 'maintenance'){
        get_template_part('templates/maintenance/maintenance');
      }
    exit;
  }
}

if( function_exists('acf_add_options_page') ) {
  if(get_field('maintenance_mode', 'options')){
    maintenance();
  }
} ?>
