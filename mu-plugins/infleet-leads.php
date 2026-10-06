<?php
/**
 * Plugin Name: INFLEET Campaign Leads
 * Description: Bloco de captura de leads para páginas de campanha. Os contatos são salvos no tipo de conteúdo "Lead" (registrado por este plugin) e exibidos na tela Leads do admin.
 * Version:     1.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      INFLEET
 * Text Domain: infleet-campaign-leads
 *
 * @package infleet-campaign-leads
 */

defined( 'ABSPATH' ) || exit;

define( 'INFLEET_CL_VERSION', '1.3.0' );
define( 'INFLEET_CL_DIR', trailingslashit( __DIR__ ) );
define( 'INFLEET_CL_URL', plugin_dir_url( __FILE__ ) );

/* =========================================================================
 * 1. CPT "lead"
 * ====================================================================== */

/**
 * Registra o CPT de leads.
 *
 * Capabilities e suporte a editor são aplicados pelo filtro da seção 2.
 */
function infleet_cl_register_cpt() {
	register_post_type(
		'lead',
		array(
			'labels'       => array(
				'name'               => 'Leads',
				'singular_name'      => 'Lead',
				'menu_name'          => 'Leads',
				'add_new'            => 'Adicionar novo',
				'add_new_item'       => 'Adicionar novo lead',
				'edit_item'          => 'Editar lead',
				'view_item'          => 'Ver lead',
				'search_items'       => 'Buscar leads',
				'not_found'          => 'Nenhum lead encontrado',
				'not_found_in_trash' => 'Nenhum lead na lixeira',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => false,
			'supports'     => array( 'title' ),
		)
	);
}
add_action( 'init', 'infleet_cl_register_cpt' );

/* =========================================================================
 * 2. Ajustes no CPT "lead"
 * ====================================================================== */

/**
 * Restringe o CPT por filtro, mantendo o registro e as travas no mesmo lugar.
 *
 * - Remove custom-fields: dados do lead não são editáveis à mão.
 * - Capabilities próprias: acesso por papel, não por "quem edita posts".
 * - Bloqueia "Add New": lead só nasce pelo formulário.
 *
 * @param array  $args      Argumentos do post type.
 * @param string $post_type Slug do post type.
 * @return array
 */
function infleet_cl_lock_cpt_args( $args, $post_type ) {
	if ( 'lead' !== $post_type ) {
		return $args;
	}

	$args['supports']        = array( 'title' );
	$args['capability_type'] = array( 'infleet_lead', 'infleet_leads' );
	$args['map_meta_cap']    = true;
	$args['capabilities']    = array(
		'create_posts' => 'do_not_allow',
	);

	return $args;
}
add_filter( 'register_post_type_args', 'infleet_cl_lock_cpt_args', 10, 2 );

/**
 * Aviso no admin caso o mu-plugin do CPT não esteja presente.
 */
function infleet_cl_missing_cpt_notice() {
	if ( post_type_exists( 'lead' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>INFLEET Campaign Leads:</strong> o tipo de conteúdo "lead" não foi encontrado. Verifique o registro em <code>infleet-leads.php</code>.</p></div>';
}
add_action( 'admin_notices', 'infleet_cl_missing_cpt_notice' );

/* =========================================================================
 * 3. Papel do Marketing e capabilities
 * ====================================================================== */

/**
 * Capabilities do CPT lead.
 *
 * @return string[]
 */
function infleet_cl_lead_caps() {
	return array(
		'edit_infleet_leads',
		'edit_others_infleet_leads',
		'edit_private_infleet_leads',
		'edit_published_infleet_leads',
		'read_private_infleet_leads',
		'delete_infleet_leads',
		'delete_others_infleet_leads',
		'delete_private_infleet_leads',
		'delete_published_infleet_leads',
	);
}

/**
 * Capabilities de página do papel Marketing.
 *
 * Sem elas o marketing não abre a página para editar os textos do bloco,
 * e o objetivo é que ele não dependa de TI para isso.
 *
 * @return string[]
 */
function infleet_cl_page_caps() {
	return array(
		'edit_pages',
		'edit_others_pages',
		'edit_published_pages',
		'edit_private_pages',
		'read_private_pages',
	);
}

/**
 * Cria o papel "Marketing INFLEET" e concede as capabilities.
 *
 * Mu-plugin não passa por ativação, então o activation hook nunca dispara:
 * a rotina roda em init e só grava no banco quando a capability falta.
 *
 * O papel enxerga Dashboard, Páginas e Leads: edita os textos do bloco
 * nas páginas de campanha e acompanha os contatos capturados.
 */
function infleet_cl_ensure_caps() {
	if ( ! get_role( 'infleet_marketing' ) ) {
		add_role( 'infleet_marketing', 'Marketing INFLEET', array( 'read' => true ) );
	}

	$caps = array_merge( infleet_cl_lead_caps(), infleet_cl_page_caps(), infleet_cl_form_caps() );

	foreach ( array( 'infleet_marketing', 'administrator' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}
		foreach ( $caps as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}
add_action( 'init', 'infleet_cl_ensure_caps' );

/* =========================================================================
 * 4. Bloco "Formulário de Leads"
 * ====================================================================== */

/**
 * Registra scripts, estilos e o bloco.
 */
function infleet_cl_register_block() {
	wp_register_script(
		'infleet-lead-form-editor',
		INFLEET_CL_URL . 'edit.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
		INFLEET_CL_VERSION,
		true
	);

	wp_register_style(
		'infleet-lead-form',
		INFLEET_CL_URL . 'lead-form.css',
		array(),
		INFLEET_CL_VERSION
	);

	register_block_type(
		INFLEET_CL_DIR,
		array(
			'render_callback' => 'infleet_cl_render_block',
		)
	);

	// Lista de formulários + config de salvamento pelo editor (só o admin precisa).
	if ( is_admin() ) {
		wp_add_inline_script(
			'infleet-lead-form-editor',
			'window.infleetForms = ' . wp_json_encode( infleet_cl_forms_payload() ) . ';'
			. 'window.infleetEditor = ' . wp_json_encode( array(
				'nonce'   => wp_create_nonce( 'infleet_form_field' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			) ) . ';',
			'before'
		);
	}
}
add_action( 'init', 'infleet_cl_register_block' );

/**
 * Normaliza o identificador de campanha: slug seguro e tamanho limitado.
 *
 * Usado no render e no submit para as duas pontas validarem igual.
 *
 * @param string $value Valor bruto.
 * @return string
 */
function infleet_cl_clean_campaign( $value ) {
	$slug = sanitize_title( (string) $value );
	$slug = trim( substr( $slug, 0, 60 ), '-' );

	return $slug ? $slug : 'geral';
}

/**
 * Renderiza o formulário no front (tudo escapado na saída).
 *
 * @param array $a Atributos do bloco.
 * @return string
 */
function infleet_cl_render_block( $a ) {
	// Com formulário selecionado, os campos vêm dele; sem formulário,
	// continuam nos atributos do bloco (bloco autônomo).
	$form          = infleet_cl_form_settings( isset( $a['formId'] ) ? $a['formId'] : 0 );
	$title         = '' !== $form['title'] ? $form['title'] : ( isset( $a['title'] ) ? $a['title'] : '' );
	$subtitle      = '' !== $form['subtitle'] ? $form['subtitle'] : ( isset( $a['subtitle'] ) ? $a['subtitle'] : '' );
	$button_text   = '' !== $form['buttonText'] ? $form['buttonText'] : ( isset( $a['buttonText'] ) && '' !== $a['buttonText'] ? $a['buttonText'] : 'Enviar' );
	$button_color  = $form['buttonColor'];
	$success       = '' !== $form['successMessage'] ? $form['successMessage'] : ( isset( $a['successMessage'] ) ? $a['successMessage'] : '' );
	$consent       = '' !== $form['consentText'] ? $form['consentText'] : ( isset( $a['consentText'] ) ? $a['consentText'] : '' );
	$show_phone    = $form['active'] ? $form['showPhone'] : ! empty( $a['showPhone'] );
	$campaign      = $form['active'] ? $form['campaign'] : infleet_cl_clean_campaign( isset( $a['campaign'] ) ? $a['campaign'] : '' );
	$status        = '';

	// Parâmetros usados só para exibir o estado do envio; sempre sanitizados.
	// phpcs:disable WordPress.Security.NonceVerification
	if ( isset( $_GET['infleet'], $_GET['infleet_c'] )
		&& infleet_cl_clean_campaign( wp_unslash( $_GET['infleet_c'] ) ) === $campaign ) {
		$status = sanitize_key( wp_unslash( $_GET['infleet'] ) );
	}
	// phpcs:enable

	$mensagens = array(
		'erro'   => 'Confira os campos e tente novamente.',
		'limite' => 'Muitas tentativas. Aguarde alguns segundos.',
	);

	// Só o que o editor permite (negrito/itálico/quebra de linha): nenhum
	// script ou markup extra sobrevive à saída, mesmo vindo do banco.
	$permitidos = array(
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);

	$btn_style = $button_color ? ' style="background-color:' . esc_attr( $button_color ) . '"' : '';

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( array( 'id' => 'infleet-' . $campaign ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php if ( 'ok' === $status ) : ?>
			<p class="infleet-msg infleet-msg--ok" role="status"><?php echo esc_html( $success ); ?></p>
		<?php else : ?>
			<?php if ( isset( $mensagens[ $status ] ) ) : ?>
				<p class="infleet-msg infleet-msg--erro" role="alert"><?php echo esc_html( $mensagens[ $status ] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $title ) ) : ?>
				<h3 class="infleet-title"><?php echo wp_kses( $title, $permitidos ); ?></h3>
			<?php endif; ?>
			<?php if ( ! empty( $subtitle ) ) : ?>
				<p class="infleet-subtitle"><?php echo wp_kses( $subtitle, $permitidos ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="infleet_submit_lead">
				<input type="hidden" name="infleet_campaign" value="<?php echo esc_attr( $campaign ); ?>">
				<?php wp_nonce_field( 'infleet_submit_lead', 'infleet_nonce' ); ?>

				<div class="infleet-hp" aria-hidden="true">
					<label>Website<input type="text" name="infleet_website" tabindex="-1" autocomplete="off"></label>
				</div>

				<label>Nome
					<input type="text" name="infleet_name" required maxlength="120" autocomplete="name">
				</label>
				<label>E-mail
					<input type="email" name="infleet_email" required maxlength="190" autocomplete="email">
				</label>
				<?php if ( $show_phone ) : ?>
					<input type="hidden" name="infleet_show_phone" value="1">
					<label>Telefone
						<input type="tel" name="infleet_phone" required maxlength="30" autocomplete="tel"
							pattern="\+?[0-9\s().\-]{8,20}"
							title="Somente números com DDD, ex.: (11) 99999-9999 ou +55 11 999999999">
					</label>
				<?php endif; ?>

				<?php if ( ! empty( $consent ) ) : ?>
					<label class="infleet-consent">
						<input type="checkbox" name="infleet_consent" value="1" required>
						<?php echo esc_html( $consent ); ?>
					</label>
				<?php endif; ?>

				<button type="submit"<?php echo $btn_style; // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $button_text ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* =========================================================================
 * 5. Captura (admin-post): nonce, honeypot, rate limit, sanitização
 * ====================================================================== */

/**
 * Processa o envio do formulário e grava o lead.
 */
function infleet_cl_handle_submit() {
	$referer = wp_get_referer();
	$back    = $referer ? remove_query_arg( array( 'infleet', 'infleet_c' ), $referer ) : home_url( '/' );

	// phpcs:disable WordPress.Security.NonceVerification -- nonce verificado logo abaixo.
	$campaign = infleet_cl_clean_campaign( isset( $_POST['infleet_campaign'] ) ? wp_unslash( $_POST['infleet_campaign'] ) : '' );
	// phpcs:enable

	$redirect = function ( $status ) use ( $back, $campaign ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'infleet'   => $status,
					'infleet_c' => $campaign,
				),
				$back
			) . '#infleet-' . $campaign
		);
		exit;
	};

	// 1) Nonce.
	$nonce = isset( $_POST['infleet_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['infleet_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'infleet_submit_lead' ) ) {
		$redirect( 'erro' );
	}

	// 2) Honeypot: bot preenche; finge sucesso e não salva.
	if ( ! empty( $_POST['infleet_website'] ) ) {
		$redirect( 'ok' );
	}

	// 3) Rate limit por IP (só o hash fica no transient; o IP não é armazenado).
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'infleet_rl_' . md5( $ip );
	if ( get_transient( $key ) ) {
		$redirect( 'limite' );
	}

	// 4) Sanitização e validação: formato rígido em cada campo.
	$raw_name  = isset( $_POST['infleet_name'] ) ? trim( (string) wp_unslash( $_POST['infleet_name'] ) ) : '';
	$raw_email = isset( $_POST['infleet_email'] ) ? trim( (string) wp_unslash( $_POST['infleet_email'] ) ) : '';
	$raw_phone = isset( $_POST['infleet_phone'] ) ? trim( (string) wp_unslash( $_POST['infleet_phone'] ) ) : '';

	// Nome: remove tags e caracteres de controle e aceita apenas
	// letras (com acentos), números, espaço, apóstrofo, hífen e ponto.
	$name = wp_strip_all_tags( $raw_name );
	$name = preg_replace( '/[\x00-\x1F\x7F]/u', '', $name );
	$name = is_string( $name ) ? trim( $name ) : '';
	$name_ok = '' !== $name
		&& mb_strlen( $name ) <= 120
		&& 1 === preg_match( "/^[\p{L}][\p{L}\p{N}'\-\. ]*$/u", $name );

	// E-mail: precisa sobreviver intacto à sanitização (nada de < > " )
	// e ser um endereço válido, com no máximo 190 caracteres.
	$email     = sanitize_email( $raw_email );
	$email_ok  = '' !== $email
		&& $email === $raw_email
		&& strlen( $email ) <= 190
		&& false !== is_email( $email );

	// Telefone (obrigatório quando o formulário exibe o campo): separadores
	// removidos e o que sobra precisa ser um número válido — + opcional e
	// 8 a 15 dígitos (E.164, ex.: +5511999998888).
	$phone         = preg_replace( '/[\s().\-]/', '', $raw_phone );
	$phone         = is_string( $phone ) ? $phone : '';
	$phone_required = isset( $_POST['infleet_show_phone'] )
		&& '1' === sanitize_key( wp_unslash( $_POST['infleet_show_phone'] ) );

	if ( '' === $phone ) {
		$phone_ok = ! $phone_required;
	} else {
		$phone_ok = 1 === preg_match( '/^\+?\d{8,15}$/', $phone );
	}

	if ( ! $name_ok || ! $email_ok || ! $phone_ok ) {
		$redirect( 'erro' );
	}

	// 5) Grava no CPT lead (privado). Meta com "_" não aparece em Custom Fields.
	$lead_id = wp_insert_post(
		array(
			'post_type'   => 'lead',
			'post_status' => 'private',
			'post_title'  => mb_substr( $name, 0, 120 ),
			'post_author' => 0,
		),
		true
	);

	if ( is_wp_error( $lead_id ) ) {
		$redirect( 'erro' );
	}

	update_post_meta( $lead_id, '_infleet_email', $email );
	update_post_meta( $lead_id, '_infleet_phone', $phone );
	update_post_meta( $lead_id, '_infleet_campaign', $campaign );
	update_post_meta( $lead_id, '_infleet_consent', empty( $_POST['infleet_consent'] ) ? 0 : 1 );

	set_transient( $key, 1, 20 );
	$redirect( 'ok' );
}
add_action( 'admin_post_nopriv_infleet_submit_lead', 'infleet_cl_handle_submit' );
add_action( 'admin_post_infleet_submit_lead', 'infleet_cl_handle_submit' );

/* =========================================================================
 * 6. Exibição na tela "Leads" existente (somente leitura)
 * ====================================================================== */

/**
 * Colunas da lista de leads.
 *
 * @param array $cols Colunas atuais.
 * @return array
 */
function infleet_cl_columns( $cols ) {
	return array(
		'cb'       => isset( $cols['cb'] ) ? $cols['cb'] : '',
		'title'    => 'Nome',
		'email'    => 'E-mail',
		'phone'    => 'Telefone',
		'campaign' => 'Campanha',
		'date'     => 'Data',
	);
}
add_filter( 'manage_lead_posts_columns', 'infleet_cl_columns' );

/**
 * Conteúdo das colunas.
 *
 * @param string $col     Coluna.
 * @param int    $post_id ID do lead.
 */
function infleet_cl_column_content( $col, $post_id ) {
	$map = array(
		'email'    => '_infleet_email',
		'phone'    => '_infleet_phone',
		'campaign' => '_infleet_campaign',
	);
	if ( isset( $map[ $col ] ) ) {
		echo esc_html( get_post_meta( $post_id, $map[ $col ], true ) );
	}
}
add_action( 'manage_lead_posts_custom_column', 'infleet_cl_column_content', 10, 2 );

/**
 * Metabox "Dados do lead".
 */
function infleet_cl_metabox() {
	add_meta_box( 'infleet_lead_data', 'Dados do lead', 'infleet_cl_metabox_render', 'lead', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'infleet_cl_metabox' );

/**
 * Renderiza a metabox (somente leitura).
 *
 * @param WP_Post $post Lead.
 */
function infleet_cl_metabox_render( $post ) {
	$rows = array(
		'E-mail'    => get_post_meta( $post->ID, '_infleet_email', true ),
		'Telefone'  => get_post_meta( $post->ID, '_infleet_phone', true ),
		'Campanha'  => get_post_meta( $post->ID, '_infleet_campaign', true ),
		'Consentiu' => get_post_meta( $post->ID, '_infleet_consent', true ) ? 'Sim' : 'Não',
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		echo '<tr><th style="width:160px">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Filtro por campanha na lista de leads.
 *
 * @param string $post_type Post type da tela atual.
 */
function infleet_cl_filter_dropdown( $post_type ) {
	if ( 'lead' !== $post_type ) {
		return;
	}

	global $wpdb;
	$campanhas = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s AND p.post_type = %s
			 ORDER BY pm.meta_value",
			'_infleet_campaign',
			'lead'
		)
	);

	$atual = isset( $_GET['infleet_campaign'] ) ? sanitize_title( wp_unslash( $_GET['infleet_campaign'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	echo '<select name="infleet_campaign"><option value="">Todas as campanhas</option>';
	foreach ( $campanhas as $c ) {
		printf(
			'<option value="%1$s"%2$s>%1$s</option>',
			esc_attr( $c ),
			selected( $atual, $c, false ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'infleet_cl_filter_dropdown' );

/**
 * Aplica o filtro por campanha na query da lista.
 *
 * @param WP_Query $query Query principal.
 */
function infleet_cl_filter_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'lead' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! empty( $_GET['infleet_campaign'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$query->set(
			'meta_query',
			array(
				array(
					'key'   => '_infleet_campaign',
					'value' => sanitize_title( wp_unslash( $_GET['infleet_campaign'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'infleet_cl_filter_query' );

/* =========================================================================
 * 7. Formulários e campanhas (autonomia do admin)
 * ====================================================================== */

/**
 * Capabilities do CPT de formulários e da taxonomia de campanhas.
 *
 * @return string[]
 */
function infleet_cl_form_caps() {
	return array(
		'edit_infleet_forms',
		'edit_others_infleet_forms',
		'edit_private_infleet_forms',
		'edit_published_infleet_forms',
		'read_private_infleet_forms',
		'publish_infleet_forms',
		'delete_infleet_forms',
		'delete_others_infleet_forms',
		'delete_private_infleet_forms',
		'delete_published_infleet_forms',
		'assign_infleet_forms',
	);
}

/**
 * Registra o CPT "Formulário" e a taxonomia "Campanha".
 *
 * Ambos entram como submenu de "Leads": o admin cria campanhas e
 * formulários e atribui cada formulário à sua campanha, sem código.
 */
function infleet_cl_register_form_cpt() {
	register_post_type(
		'infleet_form',
		array(
			'labels'          => array(
				'name'               => 'Formulários',
				'singular_name'      => 'Formulário',
				'menu_name'          => 'Formulários',
				'add_new'            => 'Adicionar novo',
				'add_new_item'       => 'Adicionar novo formulário',
				'edit_item'          => 'Editar formulário',
				'view_item'          => 'Ver formulário',
				'search_items'       => 'Buscar formulários',
				'not_found'          => 'Nenhum formulário encontrado',
				'not_found_in_trash' => 'Nenhum formulário na lixeira',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'edit.php?post_type=lead',
			'show_in_rest'    => false,
			'supports'        => array( 'title' ),
			'capability_type' => array( 'infleet_form', 'infleet_forms' ),
			'map_meta_cap'    => true,
		)
	);

	register_taxonomy(
		'infleet_campaign',
		'infleet_form',
		array(
			'labels'            => array(
				'name'          => 'Campanhas',
				'singular_name' => 'Campanha',
				'menu_name'     => 'Campanhas',
				'all_items'     => 'Campanhas',
				'edit_item'     => 'Editar campanha',
				'view_item'     => 'Ver campanha',
				'update_item'   => 'Atualizar campanha',
				'add_new_item'  => 'Adicionar nova campanha',
				'new_item_name' => 'Nome da nova campanha',
				'search_items'  => 'Buscar campanhas',
				'not_found'     => 'Nenhuma campanha encontrada',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'show_in_rest'      => false,
			'rewrite'           => false,
			'capabilities'      => array(
				'manage_terms' => 'edit_infleet_forms',
				'edit_terms'   => 'edit_infleet_forms',
				'delete_terms' => 'delete_infleet_forms',
				'assign_terms' => 'assign_infleet_forms',
			),
		)
	);
}
add_action( 'init', 'infleet_cl_register_form_cpt' );

/**
 * Campos cadastrados de um formulário publicado.
 *
 * @param int $form_id ID do formulário.
 * @return array
 */
function infleet_cl_form_settings( $form_id ) {
	$form_id  = absint( $form_id );
	$settings = array(
		'active'         => false,
		'title'          => '',
		'subtitle'       => '',
		'buttonText'     => '',
		'buttonColor'    => '',
		'successMessage' => '',
		'consentText'    => '',
		'showPhone'      => false,
		'campaign'       => 'geral',
	);

	if ( ! $form_id || 'publish' !== get_post_status( $form_id ) ) {
		return $settings;
	}

	$terms = wp_get_object_terms( $form_id, 'infleet_campaign', array( 'fields' => 'slugs' ) );
	if ( ! is_wp_error( $terms ) && $terms ) {
		$settings['campaign'] = infleet_cl_clean_campaign( $terms[0] );
	}

	$color = sanitize_hex_color( (string) get_post_meta( $form_id, '_ifl_button_color', true ) );

	$settings['active']         = true;
	$settings['title']          = sanitize_text_field( (string) get_post_meta( $form_id, '_ifl_title', true ) );
	$settings['subtitle']       = sanitize_text_field( (string) get_post_meta( $form_id, '_ifl_subtitle', true ) );
	$settings['buttonText']     = sanitize_text_field( (string) get_post_meta( $form_id, '_ifl_button_text', true ) );
	$settings['buttonColor']    = $color ? $color : '';
	$settings['successMessage'] = sanitize_text_field( (string) get_post_meta( $form_id, '_ifl_success_message', true ) );
	$settings['consentText']    = sanitize_text_field( (string) get_post_meta( $form_id, '_ifl_consent_text', true ) );
	$settings['showPhone']      = '1' === (string) get_post_meta( $form_id, '_ifl_show_phone', true );

	return $settings;
}

/**
 * Lista de formulários publicados para o seletor do editor.
 *
 * @return array
 */
function infleet_cl_forms_payload() {
	$forms = get_posts(
		array(
			'post_type'   => 'infleet_form',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	$payload = array();
	foreach ( $forms as $form ) {
		$payload[] = infleet_cl_form_payload_row( $form );
	}

	return $payload;
}

/**
 * Linha de payload de um formulário para o editor de blocos.
 *
 * @param WP_Post $form Formulário.
 * @return array
 */
function infleet_cl_form_payload_row( $form ) {
	$settings = infleet_cl_form_settings( $form->ID );

	return array(
		'id'             => $form->ID,
		'name'           => $form->post_title,
		'title'          => $settings['title'],
		'subtitle'       => $settings['subtitle'],
		'buttonText'     => $settings['buttonText'],
		'buttonColor'    => $settings['buttonColor'],
		'successMessage' => $settings['successMessage'],
		'consentText'    => $settings['consentText'],
		'showPhone'      => $settings['showPhone'],
		'campaign'       => $settings['campaign'],
	);
}

/**
 * Metabox com os campos editáveis do formulário.
 */
function infleet_cl_form_metabox() {
	add_meta_box( 'infleet_form_fields', 'Campos do formulário', 'infleet_cl_form_metabox_render', 'infleet_form', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'infleet_cl_form_metabox' );

/**
 * Renderiza o metabox do formulário.
 *
 * @param WP_Post $post Formulário.
 */
function infleet_cl_form_metabox_render( $post ) {
	wp_nonce_field( 'infleet_form_save', 'infleet_form_nonce' );

	$v = array(
		'title'          => (string) get_post_meta( $post->ID, '_ifl_title', true ),
		'subtitle'       => (string) get_post_meta( $post->ID, '_ifl_subtitle', true ),
		'buttonText'     => (string) get_post_meta( $post->ID, '_ifl_button_text', true ),
		'buttonColor'    => (string) get_post_meta( $post->ID, '_ifl_button_color', true ),
		'successMessage' => (string) get_post_meta( $post->ID, '_ifl_success_message', true ),
		'consentText'    => (string) get_post_meta( $post->ID, '_ifl_consent_text', true ),
		'showPhone'      => '1' === (string) get_post_meta( $post->ID, '_ifl_show_phone', true ),
	);
	?>
	<p class="description">Campos exibidos no front. Campo vazio = valor padrão do bloco. A campanha é escolhida na caixa "Campanhas" (coluna da direita).</p>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="ifl_title">Título</label></th>
				<td><input type="text" class="widefat" id="ifl_title" name="ifl_title" value="<?php echo esc_attr( $v['title'] ); ?>" placeholder="Receba nossas novidades"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ifl_subtitle">Subtítulo</label></th>
				<td><input type="text" class="widefat" id="ifl_subtitle" name="ifl_subtitle" value="<?php echo esc_attr( $v['subtitle'] ); ?>" placeholder="Adicione um subtítulo (opcional)"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ifl_button_text">Texto do botão</label></th>
				<td><input type="text" class="widefat" id="ifl_button_text" name="ifl_button_text" value="<?php echo esc_attr( $v['buttonText'] ); ?>" placeholder="Quero receber"></td>
			</tr>
			<tr>
				<th scope="row"><label for="ifl_button_color">Cor do botão</label></th>
				<td>
					<input type="text" class="ifl-color" id="ifl_button_color" name="ifl_button_color" value="<?php echo esc_attr( $v['buttonColor'] ); ?>">
					<p class="description">Vazio = cor padrão (#1d4ed8).</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ifl_success_message">Mensagem de sucesso</label></th>
				<td><input type="text" class="widefat" id="ifl_success_message" name="ifl_success_message" value="<?php echo esc_attr( $v['successMessage'] ); ?>" placeholder="Obrigado! Entraremos em contato."></td>
			</tr>
			<tr>
				<th scope="row"><label for="ifl_consent_text">Texto de consentimento</label></th>
				<td>
					<textarea class="widefat" rows="3" id="ifl_consent_text" name="ifl_consent_text" placeholder="Concordo em receber contatos e com o tratamento dos meus dados conforme a política de privacidade."><?php echo esc_textarea( $v['consentText'] ); ?></textarea>
					<p class="description">Vazio = formulário sem checkbox de consentimento.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Campo de telefone</th>
				<td><label><input type="checkbox" name="ifl_show_phone" id="ifl_show_phone" value="1" <?php checked( $v['showPhone'] ); ?>> Exibir campo de telefone (obrigatório para o visitante)</label></td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Salva os campos do formulário.
 *
 * @param int $post_id ID do formulário.
 */
function infleet_cl_save_form( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['infleet_form_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['infleet_form_nonce'] ) ), 'infleet_form_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$texts = array(
		'ifl_title'           => '_ifl_title',
		'ifl_subtitle'        => '_ifl_subtitle',
		'ifl_button_text'     => '_ifl_button_text',
		'ifl_success_message' => '_ifl_success_message',
		'ifl_consent_text'    => '_ifl_consent_text',
	);

	foreach ( $texts as $field => $meta ) {
		update_post_meta(
			$post_id,
			$meta,
			isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : ''
		);
	}

	$color = isset( $_POST['ifl_button_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['ifl_button_color'] ) ) : '';
	update_post_meta( $post_id, '_ifl_button_color', $color ? $color : '' );
	update_post_meta( $post_id, '_ifl_show_phone', isset( $_POST['ifl_show_phone'] ) ? '1' : '0' );
}
add_action( 'save_post_infleet_form', 'infleet_cl_save_form' );

/**
 * Salva um campo individual do formulário a partir do editor de blocos.
 *
 * Endpoint admin-ajax usado pelo bloco Gutenberg para editar os textos
 * do formulário diretamente na página.
 */
function infleet_cl_save_form_field_ajax() {
	check_ajax_referer( 'infleet_form_field', 'nonce' );

	$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
	$field   = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
	$value   = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';

	if ( ! $form_id || 'infleet_form' !== get_post_type( $form_id ) ) {
		wp_send_json_error( array( 'message' => 'Formulário inválido.' ), 400 );
	}

	if ( ! current_user_can( 'edit_post', $form_id ) ) {
		wp_send_json_error( array( 'message' => 'Sem permissão para editar este formulário.' ), 403 );
	}

	if ( 'campaign' === $field ) {
		$form   = get_post( $form_id );
		$slug   = infleet_cl_clean_campaign( (string) $value );
		$term   = term_exists( $slug, 'infleet_campaign' );
		if ( ! $term ) {
			$term = wp_insert_term( ucfirst( str_replace( '-', ' ', $slug ) ), 'infleet_campaign', array( 'slug' => $slug ) );
		}
		if ( is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => 'Campanha inválida.' ), 400 );
		}
		$term_id = is_array( $term ) ? $term['term_id'] : (int) $term;
		wp_set_object_terms( $form_id, $term_id, 'infleet_campaign' );

		wp_send_json_success( array( 'form' => infleet_cl_form_payload_row( $form ) ) );
	}

	$texts = array(
		'title'          => '_ifl_title',
		'subtitle'       => '_ifl_subtitle',
		'buttontext'     => '_ifl_button_text',
		'successmessage' => '_ifl_success_message',
		'consenttext'    => '_ifl_consent_text',
	);

	if ( isset( $texts[ $field ] ) ) {
		update_post_meta( $form_id, $texts[ $field ], sanitize_text_field( (string) $value ) );
		wp_send_json_success( array( 'form' => infleet_cl_form_payload_row( get_post( $form_id ) ) ) );
	}

	if ( 'buttoncolor' === $field ) {
		$color = sanitize_hex_color( (string) $value );
		update_post_meta( $form_id, '_ifl_button_color', $color ? $color : '' );
		wp_send_json_success( array( 'form' => infleet_cl_form_payload_row( get_post( $form_id ) ) ) );
	}

	if ( 'showphone' === $field ) {
		update_post_meta( $form_id, '_ifl_show_phone', $value ? '1' : '0' );
		wp_send_json_success( array( 'form' => infleet_cl_form_payload_row( get_post( $form_id ) ) ) );
	}

	wp_send_json_error( array( 'message' => 'Campo desconhecido.' ), 400 );
}
add_action( 'wp_ajax_infleet_save_form_field', 'infleet_cl_save_form_field_ajax' );

/**
 * Color picker na tela do formulário.
 *
 * @param string $hook Tela atual do admin.
 */
function infleet_cl_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'infleet_form' !== get_post_type() ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', 'jQuery( function( $ ) { $( ".ifl-color" ).wpColorPicker(); } );' );
}
add_action( 'admin_enqueue_scripts', 'infleet_cl_admin_assets' );
