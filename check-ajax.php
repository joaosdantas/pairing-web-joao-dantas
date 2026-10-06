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
	if ( ! $json ) {
		return array( 'empty' => true );
	}
	return json_decode( $json, true );
}

$n = wp_create_nonce( 'infleet_form_field' );

$r = run_ajax_test( array(
	'nonce'   => $n,
	'form_id' => 31,
	'field'   => 'title',
	'value'   => '<b>Oferta</b> relâmpago <script>x</script>',
) );
echo 'title success: ' . ( isset( $r['success'] ) && $r['success'] ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";
echo 'title sanitizado: ' . $r['data']['form']['title'] . "\n";

$r = run_ajax_test( array(
	'nonce'   => $n,
	'form_id' => 31,
	'field'   => 'buttonColor',
	'value'   => '#00aa22',
) );
echo 'color success: ' . ( isset( $r['success'] ) && $r['success'] ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";
echo 'color: ' . $r['data']['form']['buttonColor'] . "\n";

$r = run_ajax_test( array(
	'nonce'   => $n,
	'form_id' => 31,
	'field'   => 'showPhone',
	'value'   => 'true',
) );
echo 'showPhone success: ' . ( isset( $r['success'] ) && $r['success'] ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";
echo 'showPhone: ' . var_export( $r['data']['form']['showPhone'], true ) . "\n";

$r = run_ajax_test( array(
	'nonce'   => $n,
	'form_id' => 31,
	'field'   => 'campaign',
	'value'   => 'Nova Campanha',
) );
echo 'campaign success: ' . ( isset( $r['success'] ) && $r['success'] ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";
echo 'campaign slug: ' . $r['data']['form']['campaign'] . "\n";

$r = run_ajax_test( array(
	'nonce'   => $n,
	'form_id' => 31,
	'field'   => 'hack',
	'value'   => 'x',
) );
echo 'campo invalido bloqueado: ' . ( isset( $r['success'] ) && ! $r['success'] ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";

$r = run_ajax_test( array(
	'nonce'   => 'nonce-errado',
	'form_id' => 31,
	'field'   => 'title',
	'value'   => 'x',
) );
echo 'nonce invalido bloqueado: ' . ( isset( $r['empty'] ) ? 'sim' : 'NAO (' . json_encode( $r ) . ')' ) . "\n";