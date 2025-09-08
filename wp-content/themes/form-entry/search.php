<?php get_header(); ?>
	<main role="main">
		<?php get_template_part('templates/page-header'); ?>
		<section class="page-content">
			<?php get_template_part('loop'); ?>
			<?php get_template_part('pagination'); ?>
		</section>
	</main>
	<?php // get_sidebar(); ?>
<?php get_footer(); ?>
