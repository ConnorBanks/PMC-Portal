<?php get_header(); ?>
	<main role="main">
		<?php get_template_part('templates/page-header'); ?>
		<section class="page-content">
			<?php echo facetwp_display( 'template', 'news' ); ?>
			<?php echo facetwp_display( 'pager' ); ?>
		</section>
	</main>
	<?php // get_sidebar(); ?>
<?php get_footer(); ?>
