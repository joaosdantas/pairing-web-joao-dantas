<?php
if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}
add_filter( 'wp_die_ajax_handler', function () {
	return function ( $message = '', $title = '', $args = array() ) {
		throw new RuntimeException( 'ajax-die' );
	};
} );
wp_set_current_user( 1 );

function run_ajax_test( array $post ) {
	$_POST  = $post;
	$_REQUEST = $post;
	ob_start();
	try {
		infleet_cl_save_form_field_ajax();
		$json = ob_get_clean();
	} catch ( Exception $e ) {
		$json = ob_get_clean();
	}
	return $json ? json_decode( $json, true ) : array( 'empty' => true );
}

$n = wp_create_nonce( 'infleet_form_field' );

$cases = array(
	'campaign-empty'  => array( 'campaign', '' ),
	'campaign-br'     => array( 'campaign', 'Black Friday' ),
	'campaign-nova'   => array( 'campaign', 'Nova Campanha' ),
);

foreach ( $cases as $label => $case ) {
	$r = run_ajax_test( array( 'nonce' => $n, 'form_id' => 31, 'field' => $case[0], 'value' => $case[1] ) );
	echo $label . ' => ' . ( isset( $r['success'] ) && $r['success'] ? $r['data']['form']['campaign'] : 'FALHOU ' . wp_json_encode( $r ) ) . "\n";
}

$terms = wp_get_object_terms( 31, 'infleet_campaign', array( 'fields' => 'slugs' ) );
echo 'termos finais de 31: ' . implode( ',', is_wp_error( $terms ) ? array() : (array) $terms ) . "\n";

$all = get_terms( array( 'taxonomy' => 'infleet_campaign', 'hide_empty' => false ) );
$linha = array();
foreach ( $all as $t ) {
	$linha[] = $t->slug;
}
echo 'terms existentes: ' . implode( ',', $linha ) . "\n";