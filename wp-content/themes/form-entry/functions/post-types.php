<?php
function claba_post_types() {

////////////////////////////////////////////////////////////////////////////////
// Testimonials Post Type

	$labels = [
		"name" => __( "Testimonials", "clabacreative-starter" ),
		"singular_name" => __( "Testimonial", "clabacreative-starter" ),
		"menu_name" => __( "Testimonials", "clabacreative-starter" ),
		"all_items" => __( "All Testimonials", "clabacreative-starter" ),
		"add_new" => __( "Add new", "clabacreative-starter" ),
		"add_new_item" => __( "Add new Testimonial", "clabacreative-starter" ),
		"edit_item" => __( "Edit Testimonial", "clabacreative-starter" ),
		"new_item" => __( "New Testimonial", "clabacreative-starter" ),
		"view_item" => __( "View Testimonial", "clabacreative-starter" ),
		"view_items" => __( "View Testimonials", "clabacreative-starter" ),
		"search_items" => __( "Search Testimonials", "clabacreative-starter" ),
		"not_found" => __( "No Testimonials found", "clabacreative-starter" ),
		"not_found_in_trash" => __( "No Testimonials found in trash", "clabacreative-starter" ),
		"parent" => __( "Parent Testimonial:", "clabacreative-starter" ),
		"featured_image" => __( "Featured image for this Testimonial", "clabacreative-starter" ),
		"set_featured_image" => __( "Set featured image for this Testimonial", "clabacreative-starter" ),
		"remove_featured_image" => __( "Remove featured image for this Testimonial", "clabacreative-starter" ),
		"use_featured_image" => __( "Use as featured image for this Testimonial", "clabacreative-starter" ),
		"archives" => __( "Testimonial archives", "clabacreative-starter" ),
		"insert_into_item" => __( "Insert into Testimonial", "clabacreative-starter" ),
		"uploaded_to_this_item" => __( "Upload to this Testimonial", "clabacreative-starter" ),
		"filter_items_list" => __( "Filter Testimonials list", "clabacreative-starter" ),
		"items_list_navigation" => __( "Testimonials list navigation", "clabacreative-starter" ),
		"items_list" => __( "Testimonials list", "clabacreative-starter" ),
		"attributes" => __( "Testimonials attributes", "clabacreative-starter" ),
		"name_admin_bar" => __( "Testimonial", "clabacreative-starter" ),
		"item_published" => __( "Testimonial published", "clabacreative-starter" ),
		"item_published_privately" => __( "Testimonial published privately.", "clabacreative-starter" ),
		"item_reverted_to_draft" => __( "Testimonial reverted to draft.", "clabacreative-starter" ),
		"item_scheduled" => __( "Testimonial scheduled", "clabacreative-starter" ),
		"item_updated" => __( "Testimonial updated.", "clabacreative-starter" ),
		"parent_item_colon" => __( "Parent Testimonial:", "clabacreative-starter" ),
	];
	$args = [
		"label" => __( "Testimonials", "clabacreative-starter" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "testimonial", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-format-quote",
		"supports" => [ "title" ],
	];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  // Register Testimonials Post
	register_post_type( "testimonial", $args );

}

add_action( 'init', 'claba_post_types');
?>
