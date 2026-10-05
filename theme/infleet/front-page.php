<?php
/**
 * Home do site.
 *
 * @package infleet
 */

get_header();
?>

<main class="home">
	<section class="home-hero">
		<div class="wrap">
			<p class="home-hero__eyebrow">Gestão de frotas</p>
			<h1 class="home-hero__title"><?php bloginfo( 'description' ); ?></h1>
			<p class="home-hero__lead">
				Telemetria, checklists e indicadores de frota num lugar só, para o gestor
				decidir com dado em vez de achismo.
			</p>
		</div>
	</section>

	<section class="wrap entry-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</section>
</main>

<?php
get_footer();
