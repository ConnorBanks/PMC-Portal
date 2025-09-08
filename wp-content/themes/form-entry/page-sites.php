<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header'); ?>
            <div class="data-table">
            <div class="list-item table-header">
                <p class="name">Site Name</p>
                <p class="primary-contact">Address</p>
                <p class="sites">Property Manager</p>
                <p class="edit"></p>
            </div>
                <?= do_shortcode('[facetwp template="sites"]'); ?>
                <?= do_shortcode('[facetwp facet="pagination"]'); ?>
            </div>
		<?php
		}
		else{
			echo get_template_part('templates/partials/login-form');
		} ?>
	</main>
<?php get_footer(); ?>
