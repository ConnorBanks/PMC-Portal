<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header'); ?>
            <?= get_template_part('templates/edits/site-edit'); ?>
		<?php
		}
		else{
			echo get_template_part('templates/partials/login-form');
		} ?>
	</main>
<?php get_footer(); ?>
