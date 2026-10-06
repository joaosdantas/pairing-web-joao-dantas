( function ( blocks, element, blockEditor, components ) {
	var el = element.createElement;
	var fragment = element.Fragment;
	var useState = element.useState;
	var RichText = blockEditor.RichText;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;
	var ColorPalette = components.ColorPalette;

	var forms = window.infleetForms || [];
	var editorCfg = window.infleetEditor || { nonce: '', ajaxUrl: '' };
	var saveTimers = {};

	function findForm( id ) {
		for ( var i = 0; i < forms.length; i++ ) {
			if ( forms[ i ].id === id ) {
				return forms[ i ];
			}
		}
		return null;
	}

	function formOptions() {
		var opts = [ { label: '— Usar campos do próprio bloco —', value: 0 } ];
		for ( var i = 0; i < forms.length; i++ ) {
			opts.push( {
				label: forms[ i ].name + ' · ' + ( forms[ i ].campaign || 'geral' ),
				value: forms[ i ].id,
			} );
		}
		return opts;
	}

	function fakeField( label ) {
		return el(
			'label',
			{ className: 'infleet-fake' },
			label,
			el( 'input', { type: 'text', disabled: true } )
		);
	}

	function persistFormField( formId, field, value ) {
		if ( ! editorCfg.nonce || ! editorCfg.ajaxUrl ) {
			return;
		}
		var body = new FormData();
		body.append( 'action', 'infleet_save_form_field' );
		body.append( 'nonce', editorCfg.nonce );
		body.append( 'form_id', formId );
		body.append( 'field', field );
		body.append( 'value', value );

		fetch( editorCfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( res && res.success && res.data && res.data.form ) {
					syncForm( res.data.form );
				}
			} )
			.catch( function () {} );
	}

	function scheduleSave( formId, field, value ) {
		var key = formId + ':' + field;
		if ( saveTimers[ key ] ) {
			clearTimeout( saveTimers[ key ] );
		}
		saveTimers[ key ] = setTimeout( function () {
			persistFormField( formId, field, value );
		}, 600 );
	}

	function syncForm( payload ) {
		var f = findForm( payload.id );
		if ( f ) {
			for ( var k in payload ) {
				f[ k ] = payload[ k ];
			}
		}
	}

	blocks.registerBlockType( 'infleet/lead-form', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var f = a.formId ? findForm( a.formId ) : null;
			var hasForm = !! f;

			var forceRender = useState( 0 )[ 1 ];

			var options = formOptions();
			if ( forms.length === 0 && ! a.formId ) {
				options = [ { label: 'Nenhum formulário criado (Leads → Formulários)', value: 0 } ];
			}

			function changeFormField( field, value ) {
				if ( ! f ) {
					return;
				}
				f[ field ] = value;
				forceRender( function ( n ) {
					return n + 1;
				} );
				scheduleSave( a.formId, field, value );
			}

			var inspectorBody;

			if ( hasForm ) {
				inspectorBody = el(
					fragment,
					null,
					el(
						PanelBody,
						{ title: 'Formulário e campanha', initialOpen: true },
						el( SelectControl, {
							label: 'Formulário',
							help: 'Os campos abaixo salvam direto no formulário (Leads → Formulários).',
							value: a.formId,
							options: options,
							onChange: function ( v ) {
								set( { formId: parseInt( v, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: 'Campos do formulário' },
						el( TextControl, {
							label: 'Campanha (identificador)',
							help: 'Usado para filtrar os leads. Ex.: black-friday',
							value: f.campaign,
							onChange: function ( v ) {
								changeFormField( 'campaign', v );
							},
						} ),
						el( ToggleControl, {
							label: 'Pedir telefone',
							checked: !! f.showPhone,
							onChange: function ( v ) {
								changeFormField( 'showPhone', v );
							},
						} ),
						el( TextControl, {
							label: 'Mensagem de sucesso',
							value: f.successMessage,
							onChange: function ( v ) {
								changeFormField( 'successMessage', v );
							},
						} ),
						el( TextControl, {
							label: 'Texto de consentimento (vazio = sem checkbox)',
							value: f.consentText,
							onChange: function ( v ) {
								changeFormField( 'consentText', v );
							},
						} ),
						el( 'div', { className: 'components-base-control' },
							el(
								'div',
								{ className: 'components-base-control__field' },
								el(
									'span',
									{ className: 'components-base-control__label' },
									'Cor do botão'
								),
								el( ColorPalette, {
									value: f.buttonColor || undefined,
									onChange: function ( v ) {
										changeFormField( 'buttonColor', v || '' );
									},
								} )
							)
						)
					)
				);
			} else {
				inspectorBody = el(
					fragment,
					null,
					el(
						PanelBody,
						{ title: 'Formulário e campanha', initialOpen: true },
						el( SelectControl, {
							label: 'Formulário',
							help: 'Você também pode preencher os campos do próprio bloco (abaixo).',
							value: a.formId,
							options: options,
							onChange: function ( v ) {
								set( { formId: parseInt( v, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: 'Campos deste bloco' },
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
				);
			}

			var canvas;
			if ( hasForm ) {
				canvas = el(
					'div',
					useBlockProps( { className: 'infleet-lead-form' } ),
					el( RichText, {
						tagName: 'h3',
						className: 'infleet-title',
						value: f.title,
						placeholder: 'Título',
						allowedFormats: [ 'core/bold', 'core/italic' ],
						onChange: function ( v ) {
							changeFormField( 'title', v );
						},
					} ),
					el( RichText, {
						tagName: 'p',
						className: 'infleet-subtitle',
						value: f.subtitle,
						placeholder: 'Subtítulo (opcional)',
						allowedFormats: [ 'core/bold', 'core/italic' ],
						onChange: function ( v ) {
							changeFormField( 'subtitle', v );
						},
					} ),
					fakeField( 'Nome' ),
					fakeField( 'E-mail' ),
					f.showPhone ? fakeField( 'Telefone' ) : null,
					el( RichText, {
						tagName: 'span',
						className: 'infleet-btn-preview',
						style: f.buttonColor ? { backgroundColor: f.buttonColor } : null,
						value: f.buttonText,
						placeholder: 'Texto do botão',
						allowedFormats: [],
						onChange: function ( v ) {
							changeFormField( 'buttonText', v );
						},
					} )
				);
			} else {
				canvas = el(
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
				);
			}

			return el(
				fragment,
				null,
				el( InspectorControls, null, inspectorBody ),
				canvas
			);
		},
		save: function () {
			return null; // Bloco dinâmico: renderizado em PHP.
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components );