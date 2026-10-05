<?php
/**
 * Landing: Frota Conectada.
 *
 * @package infleet
 */

get_header();
?>

<style>
	.lp-frota-hero {
		position: relative;
		height: 460px;
		overflow: hidden;
	}
	.lp-frota-hero img {
		position: absolute;
		inset: 0;
		display: block;
		width: 100%;
		height: 100%;
		object-fit: cover;
	}
	.lp-frota-hero__texto {
		position: relative;
		z-index: 1;
		max-width: 1120px;
		margin: 0 auto;
		padding: 0 24px;
		height: 100%;
		display: flex;
		flex-direction: column;
		justify-content: center;
		color: #fff;
	}
	.lp-frota-hero__texto h1 {
		font-size: 44px;
		line-height: 1.12;
		max-width: 18ch;
		margin: 0 0 14px;
		text-shadow: 0 2px 18px rgba(6, 24, 47, 0.45);
	}
	.lp-frota-hero__texto p {
		font-size: 19px;
		max-width: 46ch;
		margin: 0;
		color: rgba(255, 255, 255, 0.92) !important;
		text-shadow: 0 1px 12px rgba(6, 24, 47, 0.45);
	}
	.lp-frota-form {
		background: #f6f8fb;
		border-top: 1px solid #dde3ea;
		padding: 56px 24px 64px;
	}
	.lp-frota-form__inner {
		max-width: 560px;
		margin: 0 auto;
	}
	.lp-frota-form h2 {
		color: #0b2f5e;
		font-size: 28px;
		margin: 0 0 8px;
	}
	.lp-frota-form p {
		color: #5c6b7a;
		margin: 0 0 24px;
	}
	.lp-frota-form input {
		width: 100%;
		padding: 12px 14px;
		margin-bottom: 12px;
		border: 1px solid #c9d3de;
		border-radius: 6px;
		font-size: 15px;
	}
	.lp-frota-form button {
		width: 100%;
		padding: 14px 20px;
		background: #17c07b;
		color: #fff;
		border: 0;
		border-radius: 6px;
		font-size: 16px;
		font-weight: 600;
		cursor: pointer;
	}
	.lp-frota-form__ok {
		background: #e7f8f0;
		border: 1px solid #17c07b;
		border-radius: 6px;
		padding: 12px 14px;
		color: #0b2f5e !important;
	}
</style>

<main class="landing">

	<section class="lp-frota-hero">
		<img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-frota-conectada.png" alt="">
		<div class="lp-frota-hero__texto">
			<h1>Sua frota conectada de ponta a ponta</h1>
			<p>Telemetria, checklist e manutenção num painel só, com alerta no momento em que o desvio acontece.</p>
		</div>
	</section>

	<div class="landing__copy entry-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</div>

	<section class="lp-frota-form">
		<div class="lp-frota-form__inner">
			<h2>Fale com um especialista</h2>
			<p>Deixe seus dados e nosso time entra em contato em até um dia útil.</p>

			<?php if ( isset( $_POST['nome'] ) ) { ?>
				<p class="lp-frota-form__ok">
					Obrigado, <?php echo $_POST['nome']; ?>! Recebemos seu contato.
				</p>
			<?php } ?>

			<form method="post" id="form-frota">
				<input type="text" name="nome" placeholder="Nome completo">
				<input type="text" name="email" placeholder="E-mail corporativo">
				<input type="text" name="telefone" placeholder="Telefone">
				<input type="text" name="frota" placeholder="Tamanho da frota">
				<button type="submit">Quero falar com um especialista</button>
			</form>
		</div>
	</section>

</main>

<script>
	document.getElementById( 'form-frota' ).onsubmit = function ( e ) {
		if ( this.nome.value == '' ) {
			alert( 'Preencha o seu nome' );
			e.preventDefault();
		}
	};
</script>

<?php
get_footer();
