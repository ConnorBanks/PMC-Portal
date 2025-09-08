<?php

// Add all front-end styles to the back-end editor
add_action( 'after_setup_theme', 'gutenberg_css' );

function gutenberg_css(){
	add_theme_support( 'editor-styles' ); // if you don't add this line, your stylesheet won't be added
  add_editor_style( '/assets/bootstrap/bootstrap.css' ); // tries to include style-editor.css directly from your theme folder
	add_editor_style( '/assets/style/style.css' ); // tries to include style-editor.css directly from your theme folder
}

add_action( 'enqueue_block_editor_assets', function() {
  wp_enqueue_style( 'guten-fonts', 'https://use.typekit.net/smh0khq.css' );
  wp_enqueue_script( 'guten-script-load', get_template_directory_uri() . '/assets/js/scripts.js' );
  wp_enqueue_script( 'guten-slick-load', get_template_directory_uri() . '/assets/slick/slick.js' );
} );



// Hide Gutenberg Editor on certain pages.
//add_action( 'admin_init', 'hide_editor' );
function hide_editor() {
  // Get the Post ID.
  $post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;
  if( !isset( $post_id ) ) return;

  // Hide the editor on the page titled 'Homepage'
  $pagename = get_the_title($post_id);
  if($pagename == 'News' OR $pagename == 'Resources' OR $pagename == 'Case Studies' OR $pagename == 'Request Sent' OR $pagename == 'Vehicles' OR $pagename == 'Spares and Accessories'){
    remove_post_type_support('page', 'editor');
  }

  // Hide the editor on a page with a specific page template
  // Get the name of the Page Template file.
  $template_file = get_post_meta($post_id, '_wp_page_template', true);

  if($template_file == 'my-page-template.php'){ // the filename of the page template
    remove_post_type_support('page', 'editor');
  }
  remove_post_type_support('post', 'editor');
}

// Remove Comments From Menu Section
add_action( 'admin_init', 'my_remove_admin_menus' );
function my_remove_admin_menus() {
  remove_menu_page( 'edit-comments.php' );
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Gutenberg Block - ONLY USE THE ONES YOU WANT.

add_filter( 'allowed_block_types_all', 'misha_allowed_block_types', 25, 2 );

function misha_allowed_block_types( $allowed_blocks, $editor_context ) {

	return array(
		'core/image',
		'core/paragraph',
		'core/heading',
		'core/list',
		'core/list-item',
		'core/block',
		'acf/page-header',
		'acf/safety-devices-custom',
		'acf/sector-header',
		'acf/accordion',
		'acf/full-image-cta',
		'acf/related-content',
    	//'acf/text--image',
    	//'acf/text-image',
    	//'acf/text-then-image',
    	'acf/page-links',
    	'acf/contact-form',
    	'acf/trusted-by',
    	'acf/capabilities-overview',
    	'acf/location-map',
    	'acf/faqs',
    	'acf/large-text',
    	'acf/vehicle-brands-grid',
		'core/shortcode',
		'core/spacer',
		'core/separator',
		'core/row',
		'core/group',
		'core/columns',
		'core/image',
		'core/gallery',
		'core/table',
		'core/pullquote',
		'core/preformatted',
		'core/heading',
		'core/paragraph',
		'core/html',


	);

}

?>
