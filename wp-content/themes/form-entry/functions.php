<?php
/*
 *  Author: Connor Banks | https://www.connorbanks.co.uk
 *  URL: https://www.connorbanks.co.uk | @connorbanks94
 *  Custom functions, support, custom post types and more.
 */

 function is_scs(){
  global $current_user;
  if($current_user->user_email == 'connor@blacksheep-creative.co.uk'){
   return true;
 }
}

/**
 * Forward wp-login.php to home for everyone unless a secret key is present.
 * Also keeps wp-admin locked to Connor only (by email).
 * File: wp-content/mu-plugins/lockdown-login-and-admin.php
 */

defined('ABSPATH') || exit;

// === Set a long, random secret and keep it private ===
// Tip: move this define to wp-config.php so it's not in version control.
if ( ! defined('BSC_LOGIN_KEY') ) {
    define('BSC_LOGIN_KEY', 'change-this-to-a-long-random-string');
}

/** Helper: is the current user allowed in wp-admin? */
function bsc_is_allowed_admin_user(): bool {
    if ( ! is_user_logged_in() ) {
        return false;
    }
    $user = wp_get_current_user();
    if ( ! $user || empty($user->user_email) ) {
        return false;
    }
    $allowed_emails = ['connor@blacksheep-creative.co.uk'];
    return in_array(strtolower($user->user_email), array_map('strtolower', $allowed_emails), true);
}

/** Keep wp-admin blocked for non-allowed users (still allow AJAX/admin-post/async-upload). */
add_action('admin_init', function () {
    if ( bsc_is_allowed_admin_user() ) {
        return;
    }
    global $pagenow;
    if ( in_array($pagenow ?? '', ['admin-ajax.php', 'admin-post.php', 'async-upload.php'], true) ) {
        return;
    }
    if ( is_admin() ) {
        wp_safe_redirect(home_url('/?no-admin=1'));
        exit;
    }
}, 0);

/** Hide admin bar for non-allowed users. */
add_filter('show_admin_bar', function () {
    return bsc_is_allowed_admin_user();
}, PHP_INT_MAX);

/** After login, send non-allowed users to the front-end. */
add_filter('login_redirect', function ($redirect_to, $request, $user) {
    if ($user instanceof WP_User) {
        $email = strtolower($user->user_email ?? '');
        if ($email !== 'connor@blacksheep-creative.co.uk') {
            return home_url('/');
        }
    }
    return $redirect_to;
}, 10, 3);

/**
 * Forward wp-login.php to home unless the secret key is present.
 * Works for GET requests (viewing the form, lostpassword, etc.).
 * Connor can visit /wp-login.php?key=YOUR_SECRET to access the form.
 */
add_action('login_init', function () {
    // Allow if key matches or already logged in as allowed admin user.
    $has_key = isset($_GET['key']) && hash_equals(BSC_LOGIN_KEY, (string) $_GET['key']);
    if ( $has_key || bsc_is_allowed_admin_user() ) {
        return;
    }
    if ( strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
});

/** Append the secret key to core-generated auth URLs for convenience. */
add_filter('login_url', function ($url) {
    return add_query_arg('key', rawurlencode(BSC_LOGIN_KEY), $url);
}, 10, 1);

add_filter('logout_url', function ($url) {
    return add_query_arg('key', rawurlencode(BSC_LOGIN_KEY), $url);
}, 10, 1);

add_filter('lostpassword_url', function ($url) {
    return add_query_arg('key', rawurlencode(BSC_LOGIN_KEY), $url);
}, 10, 1);



// disable for posts
add_filter('use_block_editor_for_post', '__return_false', 10);

// disable for post types
add_filter('use_block_editor_for_post_type', '__return_false', 10);





///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Standard Theme Support
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (function_exists('add_theme_support')){
    // Add Menu Support
    add_theme_support('menus');

    // Add Thumbnail Theme Support
    add_theme_support('post-thumbnails');
    add_image_size('large', 700, '', true); // Large Thumbnail
    add_image_size('medium', 250, '', true); // Medium Thumbnail
    add_image_size('small', 120, '', true); // Small Thumbnail
    add_image_size('custom-size', 700, 200, true); // Custom Thumbnail Size call using the_post_thumbnail('custom-size');

    // Enables post and comment RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Localisation Support
    load_theme_textdomain('html5blank', get_template_directory() . '/languages');
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Loaded Header Scripts
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function header_scripts() {
if ($GLOBALS['pagenow'] != 'wp-login.php' && !is_admin()) {
    wp_register_script('conditionizr', get_template_directory_uri() . '/assets/js/lib/conditionizr-4.3.0.min.js', array(), '4.3.0'); // Conditionizr
    wp_enqueue_script('conditionizr');

    // Modernizr JS - Always Needed.
    wp_register_script('modernizr', get_template_directory_uri() . '/assets/js/lib/modernizr-2.7.1.min.js', array(), '2.7.1'); // Modernizr
    wp_enqueue_script('modernizr');

    // Developer created JS - Always Needed.
    wp_register_script('scripts', get_template_directory_uri() . '/assets/js/scripts.js', array('jquery'), '1.0.0'); // Custom scripts
    wp_enqueue_script('scripts');

    // Bootstrap JS - Comment out if not needed.
    wp_register_script('bootstrap', get_template_directory_uri() . '/assets/bootstrap/bootstrap.js', array('jquery'), '1.0.0'); // Custom scripts
    wp_enqueue_script('bootstrap');

    // Slick JS - Comment out if not needed.
    wp_register_script('slick', get_template_directory_uri() . '/assets/slick/slick.js', array('jquery'), '1.0.0'); // Custom scripts
    wp_enqueue_script('slick');
  }
}
add_action('init', 'header_scripts');


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Loaded Stylesheets
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function styles(){
    // Theme Standard Styling
    wp_register_style('style', get_template_directory_uri() . '/assets/style/style.css', array(), '1.0', 'all');
    wp_enqueue_style('style');

    // Bootstrap CSS - Comment out if not needed.
    wp_register_style('bootstrap', get_template_directory_uri() . '/assets/bootstrap/bootstrap.css', array(), '1.0', 'all');
    wp_enqueue_style('bootstrap');
}
add_action('wp_enqueue_scripts', 'styles');


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// WP Menu Navigations
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Register HTML5 Blank Navigation
function register_menu() {
  register_nav_menus(array( // Using array to specify more menus if needed
    'header-menu' => __('Header Menu', 'html5blank'), // Main Navigation
    'account-menu' => __('Account Menu', 'html5blank'), // Sidebar Navigation
    'footer-menu' => __('Footer Menu', 'html5blank'), // Extra Navigation if needed (duplicate as many as you need!)
    'mobile-menu' => __('Mobile Menu', 'html5blank'), // Extra Navigation if needed (duplicate as many as you need!)
    'legal-menu' => __('Legal Menu', 'html5blank') // Extra Navigation if needed (duplicate as many as you need!)
  ));
}
add_action('init', 'register_menu'); // Add Menus


include_once( get_template_directory() . '/functions/navigation/header-nav.php' );
include_once( get_template_directory() . '/functions/navigation/footer-nav.php' );
include_once( get_template_directory() . '/functions/navigation/mobile-nav.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Misc Functions
include_once( get_template_directory() . '/functions/misc.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Advanced Custom Fields Functions
include_once( get_template_directory() . '/functions/gutenberg.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Advanced Custom Fields Functions
include_once( get_template_directory() . '/functions/acf-options.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Maintenance / Holding Mode Feature
include_once( get_template_directory() . '/functions/maintenance.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Share Buttons
include_once( get_template_directory() . '/functions/share-buttons.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// User Permissions
include_once( get_template_directory() . '/functions/permissions.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Login Styling
include_once( get_template_directory() . '/functions/login-style.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FacetWP
include_once( get_template_directory() . '/functions/facetwp.php' );

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FacetWP
if (is_plugin_active('woocommerce/woocommerce.php')) {
  include_once( get_template_directory() . '/functions/woocommerce.php' );
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Custom Post Types
//include_once( get_template_directory() . '/functions/post-types.php' );

# http://jeffreysambells.com/2012/10/25/human-readable-filesize-php
function human_filesize($bytes, $decimals = 2) {
  $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

?>
