<?php

function my_acf_init_block_types() {

    // Check function exists.
    if( function_exists('acf_register_block_type') ) {
        acf_register_block_type(array(
            'name'              => 'Sector Header',
            'title'             => __('Sector Header'),
            'description'       => __('Sector Header Custom Block'),
            'render_template'   => 'templates/blocks/sector-header.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'header', 'title', 'sector' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Page Header',
            'title'             => __('Page Header'),
            'description'       => __('Page Header Custom Block'),
            'render_template'   => 'templates/blocks/page-header.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'header', 'title' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Hero Slider',
            'title'             => __('Hero Slider'),
            'description'       => __('Hero Slider Custom Block'),
            'render_template'   => 'templates/blocks/hero-slider.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'hero', 'slider', 'gallery' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Vehicle Brands Grid',
            'title'             => __('Vehicle Brands Grid'),
            'description'       => __('Vehicle Brands Grid Custom Block'),
            'render_template'   => 'templates/blocks/brands-grid.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'content', 'grid', 'brands' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Text then Image',
            'title'             => __('Text then Image'),
            'description'       => __('Text then Image Custom Block'),
            'render_template'   => 'templates/blocks/text-image.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'text'),
        ));
        acf_register_block_type(array(
          'name'              => 'Text & Image',
          'title'             => __('Text & Image'),
          'description'       => __('Text & Image Custom Block'),
          'render_template'   => 'templates/blocks/text-image.php',
          'category'          => 'custom-layout',
          'icon'              => 'screenoptions',
          'keywords'          => array( 'text'),
      ));
        acf_register_block_type(array(
            'name'              => 'Trusted By',
            'title'             => __('Trusted By'),
            'description'       => __('Trusted By Custom Block'),
            'render_template'   => 'templates/blocks/trusted-by.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'text', 'image', 'content' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Contact Form',
            'title'             => __('Contact Form'),
            'description'       => __('Contact Form Custom Block'),
            'render_template'   => 'templates/blocks/contact-form.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'contact', 'form', 'content' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Capabilities Overview',
            'title'             => __('Capabilities Overview'),
            'description'       => __('Capabilities Overview Custom Block'),
            'render_template'   => 'templates/blocks/capabilities-overview.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'capabilities', 'sectors', 'content' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Related Content',
            'title'             => __('Related Content'),
            'description'       => __('Related Content Custom Block'),
            'render_template'   => 'templates/blocks/related-content.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'links', 'slider' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Page Links',
            'title'             => __('Page Links'),
            'description'       => __('Page Links Custom Block'),
            'render_template'   => 'templates/blocks/page-links.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'links', 'pages' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Full Image Call to Action',
            'title'             => __('Full Image Call to Action'),
            'description'       => __('Full Image Custom Block with options to add title, description & button link'),
            'render_template'   => 'templates/blocks/full-image-cta.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'image', 'content', 'cta' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Featured News',
            'title'             => __('Featured News'),
            'description'       => __('Featured News Custom Block'),
            'render_template'   => 'templates/blocks/featured-news.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'featured', 'news' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Featured Case Study',
            'title'             => __('Featured Case Study'),
            'description'       => __('Featured Case Study Custom Block'),
            'render_template'   => 'templates/blocks/featured-case-study.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'featured', 'case study' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Location Map',
            'title'             => __('Location Map'),
            'description'       => __('Location Map Custom Block'),
            'render_template'   => 'templates/blocks/location-map.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'location', 'map' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Large Text',
            'title'             => __('Large Text'),
            'description'       => __('Large Text Custom Block'),
            'render_template'   => 'templates/blocks/large-text.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'large', 'text' ),
        ));
        acf_register_block_type(array(
            'name'              => 'Accordion',
            'title'             => __('Accordion'),
            'description'       => __('Accordion Custom Block'),
            'render_template'   => 'templates/blocks/accordion.php',
            'category'          => 'custom-layout',
            'icon'              => 'screenoptions',
            'keywords'          => array( 'accordion' ),
        ));
        acf_register_block_type(array(
          'name'              => 'FAQs',
          'title'             => __('FAQs'),
          'description'       => __('FAQs Custom Block'),
          'render_template'   => 'templates/blocks/faqs.php',
          'category'          => 'custom-layout',
          'icon'              => 'screenoptions',
          'keywords'          => array( 'accordion', 'questions' ),
      ));  

      acf_register_block_type(array(
          'name'              => 'Safety Devices Custom',
          'title'             => __('Safety Devices Custom'),
          'description'       => __('Safety Devices Custom Block'),
          'render_template'   => 'templates/blocks/sd_custom.php',
          'category'          => 'custom-layout',
          'icon'              => 'dashicons-image-flip-horizontal',
          'keywords'          => array( 'safety devices', 'custom' ),
      )); 
    }
}
add_action('acf/init', 'my_acf_init_block_types');

// Options Page - Have to be after above statement as function won't exist.
if( function_exists('acf_add_options_page') ) {
  acf_add_options_page();
  if(get_field('maintenance_mode', 'options')){
    $args = array(
      'page_title' => 'Maintenance / Holding Mode',
      'menu_title' => 'Maintenance / Holding Mode',
      'menu_slug' => 'maintenance',
      'capability' => 'edit_posts',
      'position' => false,
      'parent_slug' => '',
    	'icon_url' => false,
      'redirect' => true,
      'post_id' => 'maintenance-mode',
      'autoload' => false,
    	'update_button'		=> __('Update', 'acf'),
      'updated_message'	=> __("Options Updated", 'acf'),
    );
    acf_add_options_page( $args );
  }
  if(is_scs()){
    $args = array(
      'page_title' => 'Admin Settings',
      'menu_title' => 'Admin Settings',
      'menu_slug' => 'admin-settings',
      'capability' => 'edit_posts',
      'position' => false,
      'parent_slug' => '',
    	'icon_url' => 'dashicons-lock',
      'redirect' => true,
      'post_id' => 'admin-settings',
      'autoload' => false,
    	'update_button'		=> __('Update', 'acf'),
      'updated_message'	=> __("Options Updated", 'acf'),
    );
    acf_add_options_page( $args );
  }
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// CONTENT BLOCKS - ONLY USE THE ONES YOU WANT.


add_filter('frm_setup_new_fields_vars',  'bsc_populate_selects', 20, 2);
add_filter('frm_setup_edit_fields_vars', 'bsc_populate_selects', 20, 2);

function bsc_populate_selects($values, $field){
    // Map field IDs to post types and placeholders
    $map = array(
        72 => array('post_type' => 'client', 'placeholder' => 'Select Client'),
        60 => array('post_type' => 'client', 'placeholder' => 'Select Client'),
        73 => array('post_type' => 'site',   'placeholder' => 'Select Site'),
    );

    $fid = (int) $field->id;
    if ( ! isset($map[$fid]) ) {
        return $values;
    }

    // Build options as [ post_id => title ] so we can save IDs
    $opts = array( '' => $map[$fid]['placeholder'] );

    $q = new WP_Query(array(
        'post_type'      => $map[$fid]['post_type'],
        'post_status'    => array('publish','private'),
        'posts_per_page' => -1,
        'orderby'        => array('menu_order' => 'ASC', 'title' => 'ASC'),
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ));

    if ( $q->have_posts() ) {
        foreach ( $q->posts as $pid ) {
            $opts[$pid] = get_the_title($pid);
        }
    }
    wp_reset_postdata();

    // Apply options and ensure keys (IDs) are saved
    $values['options'] = $opts;
    $values['use_key'] = true;

    // Back-compat: if an entry previously saved a title, map it to an ID
    if ( ! empty($values['value']) && ! is_numeric($values['value']) ) {
        $maybe = array_search($values['value'], $opts, true);
        if ($maybe !== false) {
            $values['value'] = $maybe;
        }
    }

    return $values;
}

/**
 * Assign a plain numeric report number on first real save of a Report,
 * and set the post's title + slug to that number.
 *
 * - Triggers on draft/pending/publish (skips auto-draft, autosave, revisions)
 * - Stores number in post meta "_report_number"
 * - Counter option: "custom_report_number" (default start at 1000)
 * - Sets post_title and post_name (slug) to the number on first assignment
 * - Keeps ACF field "report_number" in sync if present
 */

add_action('save_post_report', 'pmc_assign_report_number_and_title_on_any_save', 10, 3);
function pmc_assign_report_number_and_title_on_any_save($post_id, $post, $update) {
    // Safety: skip revisions, autosaves, bad objects
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!($post instanceof WP_Post)) return;

    // Only act on our CPT
    if ($post->post_type !== 'report') return;

    // Skip "auto-draft" (WP creates these before user saves)
    if ($post->post_status === 'auto-draft') return;

    // If already numbered, do nothing further (we only set title/slug once)
    $existing = get_post_meta($post_id, '_report_number', true);
    if ($existing) return;

    // Get and assign the next number
    $current = (int) get_option('custom_report_number', 1000);
    $num     = $current;

    update_post_meta($post_id, '_report_number', $num);
    update_option('custom_report_number', $current + 1, false);

    // Optional: keep ACF "report_number" field in sync
    if (function_exists('update_field')) {
        @update_field('report_number', $num, $post_id);
    }

    // Set title + slug to the number
    $title = (string) $num;

    // Generate a unique slug for this post using the numeric title
    $unique_slug = wp_unique_post_slug(
        sanitize_title($title),
        $post_id,
        $post->post_status,
        $post->post_type,
        $post->post_parent
    );

    // Avoid recursion while we update the post
    remove_action('save_post_report', 'pmc_assign_report_number_and_title_on_any_save', 10);

    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $title,
        'post_name'  => $unique_slug,
    ]);

    // Reattach hook
    add_action('save_post_report', 'pmc_assign_report_number_and_title_on_any_save', 10, 3);
}

/**
 * Helper: fetch the report number anywhere you need it.
 *
 * $num = get_post_meta($report_id, '_report_number', true);
 * // Or, if using ACF too:
 * $num = function_exists('get_field') ? get_field('report_number', $report_id) : get_post_meta($report_id, '_report_number', true);
 */




register_post_type('site', [
  'label'               => 'Sites',
  'public'              => true,
  'publicly_queryable'  => true,
  'show_ui'             => true,
  'has_archive'         => true,              // or 'sites'
  'rewrite'             => ['slug' => 'site', 'with_front' => false],
]);
 ?>
