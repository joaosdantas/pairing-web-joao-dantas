<?php
/**
 * Template padrao de pagina.
 *
 * @package infleet
 */

get_header();
?>

<main class="wrap page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<h1 class="page__title"><?php the_title(); ?></h1>
		<div class="entry-content"><?php the_content(); ?></div>
		<?php
	endwhile;
	?>
</main>

<?php
get_footer();
