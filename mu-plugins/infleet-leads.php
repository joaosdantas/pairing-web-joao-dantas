<?php
/**
 * Plugin Name: INFLEET Leads
 * Description: Registra o tipo de conteudo Lead, onde ficam os contatos capturados pelo site.
 * Version:     1.0.0
 * Author:      INFLEET
 *
 * @package infleet
 */

/**
 * Registra o tipo de conteudo Lead.
 *
 * Lead nao e conteudo publico: nao gera URL no site, nao entra na busca
 * e nao e exposto pela REST API. So existe dentro do admin.
 */
function infleet_register_lead_post_type() {
	register_post_type(
		'lead',
		array(
			'labels'              => array(
				'name'          => 'Leads',
				'singular_name' => 'Lead',
				'menu_name'     => 'Leads',
				'all_items'     => 'Todos os leads',
				'edit_item'     => 'Editar lead',
				'view_item'     => 'Ver lead',
				'search_items'  => 'Buscar leads',
				'not_found'     => 'Nenhum lead por aqui ainda.',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'custom-fields' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'infleet_register_lead_post_type' );
