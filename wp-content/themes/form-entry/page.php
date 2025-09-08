<?php get_header(); ?>
	<main role="main">
		<?php if(is_user_logged_in()){ 
			echo get_template_part('templates/blocks/page-header');
		} ?>
		
		<?php the_content(); ?>
	</main>
<?php get_footer(); ?>
