<?php get_header(); ?>
	<main role="main">
		<section class="page-content">
			<?php get_template_part('assets/img/icons/404/404.svg');?>
			<h1>404 Error</h1>
			<p>This page is not found - It could have moved, or it never existed.</p>
			<div class="btns">
				<p class="btn"><a href="/home">Return Home</a></p>
				<p class="btn btn-second"><a href="/contact">Contact</a></p>
			</div>
		</section>
		<?php the_content(); ?>
	</main>
<?php get_footer(); ?>
