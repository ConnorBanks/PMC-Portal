<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header'); ?>
            <?= get_template_part('templates/adds/client-add'); ?>
		<?php
		}
		else{
			echo get_template_part('templates/partials/login-form');
		} ?>
	</main>
<?php get_footer(); ?>
