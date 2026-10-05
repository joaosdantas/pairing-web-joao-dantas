<?php
/**
 * Plugin Name: INFLEET ambiente local
 * Description: Checagens de sanidade do ambiente de desenvolvimento local.
 * Version:     1.0.0
 * Author:      INFLEET
 *
 * @package infleet
 */

/**
 * Paginas que o seed cria.
 *
 * @return string[]
 */
function infleet_ambiente_paginas_esperadas() {
	return array( 'home', 'frota-conectada', 'gestao-de-combustivel' );
}

/**
 * Avisa no admin quando o ambiente esta sem o conteudo de demonstracao.
 *
 * Sem isso, e possivel rodar 'npm start', esquecer o 'npm run seed' e achar
 * que o ambiente esta pronto quando na verdade o site esta vazio.
 */
function infleet_ambiente_aviso_seed() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$faltando = array();

	foreach ( infleet_ambiente_paginas_esperadas() as $slug ) {
		if ( ! get_page_by_path( $slug ) ) {
			$faltando[] = $slug;
		}
	}

	$tema_ativo = ( 'infleet' === get_stylesheet() );

	if ( empty( $faltando ) && $tema_ativo ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>Ambiente incompleto.</strong> ';
	echo 'Rode <code>npm run seed</code> na raiz do repositório para semear o conteúdo de demonstração.';

	if ( ! $tema_ativo ) {
		echo ' O tema INFLEET também ainda não está ativo.';
	}

	echo '</p></div>';
}
add_action( 'admin_notices', 'infleet_ambiente_aviso_seed' );
