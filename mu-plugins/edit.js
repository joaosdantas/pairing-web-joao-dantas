( function ( blocks, element, blockEditor, components ) {
	var el = element.createElement;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;

	function fakeField( label ) {
		return el(
			'label',
			{ className: 'infleet-fake' },
			label,
			el( 'input', { type: 'text', disabled: true } )
		);
	}

	blocks.registerBlockType( 'infleet/lead-form', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;

			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Configurações da campanha' },
						el( TextControl, {
							label: 'Campanha (identificador)',
							help: 'Usado para filtrar os leads. Ex.: black-friday',
							value: a.campaign,
							onChange: function ( v ) {
								set( { campaign: v } );
							},
						} ),
						el( ToggleControl, {
							label: 'Pedir telefone',
							checked: a.showPhone,
							onChange: function ( v ) {
								set( { showPhone: v } );
							},
						} ),
						el( TextControl, {
							label: 'Mensagem de sucesso',
							value: a.successMessage,
							onChange: function ( v ) {
								set( { successMessage: v } );
							},
						} ),
						el( TextControl, {
							label: 'Texto de consentimento (vazio = sem checkbox)',
							value: a.consentText,
							onChange: function ( v ) {
								set( { consentText: v } );
							},
						} )
					)
				),
				el(
					'div',
					useBlockProps( { className: 'infleet-lead-form' } ),
					el( RichText, {
						tagName: 'h3',
						value: a.title,
						placeholder: 'Título',
						allowedFormats: [ 'core/bold', 'core/italic' ],
						onChange: function ( v ) {
							set( { title: v } );
						},
					} ),
					el( RichText, {
						tagName: 'p',
						value: a.subtitle,
						placeholder: 'Subtítulo (opcional)',
						allowedFormats: [ 'core/bold', 'core/italic' ],
						onChange: function ( v ) {
							set( { subtitle: v } );
						},
					} ),
					fakeField( 'Nome' ),
					fakeField( 'E-mail' ),
					a.showPhone ? fakeField( 'Telefone' ) : null,
					el( RichText, {
						tagName: 'span',
						className: 'infleet-btn-preview',
						value: a.buttonText,
						placeholder: 'Texto do botão',
						allowedFormats: [],
						onChange: function ( v ) {
							set( { buttonText: v } );
						},
					} )
				)
			);
		},
		save: function () {
			return null; // Bloco dinâmico: renderizado em PHP.
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );
