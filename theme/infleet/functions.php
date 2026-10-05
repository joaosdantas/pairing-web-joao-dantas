<?php
/**
 * Tema INFLEET.
 *
 * @package infleet
 */

/**
 * Suportes e registros do tema.
 */
function infleet_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'primary' => 'Menu principal',
		)
	);
}
add_action( 'after_setup_theme', 'infleet_setup' );

/**
 * Assets do tema.
 *
 * Padrao da casa: tudo passa por wp_enqueue_*, versionado pela versao do tema,
 * para que o cache seja invalidado a cada deploy.
 */
function infleet_enqueue_assets() {
	$versao = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'infleet-theme',
		get_template_directory_uri() . '/assets/css/theme.css',
		array(),
		$versao
	);

	wp_enqueue_script(
		'infleet-theme',
		get_template_directory_uri() . '/assets/js/theme.js',
		array(),
		$versao,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'infleet_enqueue_assets' );

/**
 * Largura de conteudo usada pelo editor.
 */
function infleet_content_width() {
	$GLOBALS['content_width'] = 1120;
}
add_action( 'after_setup_theme', 'infleet_content_width', 0 );
