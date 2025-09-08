<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header'); ?>
            <div class="data-table">
            <div class="list-item table-header">
                <p class="name">Report #</p>
                <p class="primary-contact">Site</p>
                <p class="sites">Property Manager</p>
                <p class="status">Status</p>
            </div>
                <?= do_shortcode('[facetwp template="reports"]'); ?>
                <?= do_shortcode('[facetwp facet="pagination"]'); ?>
            </div>
		<?php
		}
		else{
			echo get_template_part('templates/partials/login-form');
		} ?>
	</main>
<?php get_footer(); ?>
