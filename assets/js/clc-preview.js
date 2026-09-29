/**
 * Customizer live preview for the login page.
 *
 * #clc-style is rebuilt from CLC.rules, the same rule list PHP prints on the
 * real login page, so the preview and the saved result cannot drift apart.
 * cssValue(), backgroundFilter() and bothLogoCSS() mirror css_value(),
 * background_filter_css() and generate_css() in
 * includes/lib/class-colorlib-login-customizer-css-customization.php —
 * change them together.
 */
( function ( $, api, data ) {
	'use strict';

	var PX_PROPERTIES = [ 'width', 'max-width', 'height', 'min-height', 'font-size' ],
	    values        = {},
	    queued        = false;

	if ( ! data || ! data.rules ) {
		return;
	}

	// Mirrors PHP empty() for the value types a setting can hold.
	function isEmpty( value ) {
		return undefined === value || null === value || false === value || '' === value || '0' === value || 0 === value;
	}

	function absint( value ) {
		return Math.abs( parseInt( value, 10 ) ) || 0;
	}

	// Last line of defence, like clc_escape_css_value(): nothing may leave the declaration.
	function escapeValue( value ) {
		return String( value ).replace( /[<>{};\\]/g, '' ).trim();
	}

	function cssValue( property, option, value ) {
		value = String( value ).trim();

		switch ( property ) {
			case 'background-image':
				return 'url("' + value.replace( /["'()]/g, '' ) + '")';

			case 'display':
				if ( 'logo-settings' === option ) {
					return 'hide-logo' === value ? 'none' : 'block';
				}
				return 'none';
		}

		if ( -1 !== PX_PROPERTIES.indexOf( property ) && /^-?\d*\.?\d+$/.test( value ) ) {
			return value + 'px';
		}

		return value;
	}

	function backgroundFilter() {
		var blur       = absint( values[ 'background-blur' ] ),
		    brightness = values[ 'background-brightness' ],
		    filters    = [];

		brightness = ( undefined === brightness || null === brightness ) ? 100 : absint( brightness );

		if ( blur > 0 ) {
			filters.push( 'blur(' + blur + 'px)' );
		}

		if ( 100 !== brightness ) {
			filters.push( 'brightness(' + brightness + '%)' );
		}

		return filters.length ? '.ml-container .ml-extra-div{filter:' + filters.join( ' ' ) + ';}' : '';
	}

	function buildCSS() {
		var css = '';

		$.each( data.rules, function ( index, rule ) {
			var lines = '';

			$.each( rule.declarations, function ( property, option ) {
				var value;

				if ( isEmpty( values[ option ] ) ) {
					return;
				}

				value = escapeValue( cssValue( property, option, values[ option ] ) );

				if ( '' !== value ) {
					lines += property + ':' + value + ';\n';
				}
			} );

			if ( lines ) {
				css += rule.selector + '{\n' + lines + '}\n';
			}
		} );

		return css + backgroundFilter();
	}

	function bothLogoCSS() {
		var width  = absint( values[ 'logo-width' ] ),
		    height = absint( values[ 'logo-height' ] ),
		    image  = String( values[ 'custom-logo' ] || '' ).replace( /["'()\\]/g, '' ) || data.defaultLogo,
		    size   = ( width && height ) ? width + 'px ' + height + 'px' : '20px 20px';

		return '.login.clc-both-logo h1 a{width:100%;height:100%;text-indent:unset;background-position:top center !important;' +
			'padding-top:' + ( 30 + height ) + 'px;background-size:' + size + ';margin-top:-' + ( 15 + height ) + 'px;' +
			'position:relative;background-image:url("' + image + '")}';
	}

	// Coalesce bursts (a template sets dozens of settings at once) into one repaint.
	function render() {
		if ( queued ) {
			return;
		}

		queued = true;

		window.requestAnimationFrame( function () {
			queued = false;

			// textContent, not innerHTML: stylesheet text must never be parsed as markup.
			$( '#clc-style' ).text( buildCSS() );
			$( '#clc-logo-style' ).text( bothLogoCSS() );
		} );
	}

	$.each( data.settings, function ( option, setting ) {
		values[ option ] = setting.value;

		api( setting.name, function ( value ) {
			value.bind( function ( to ) {
				values[ option ] = to;
				render();
			} );
		} );
	} );

	// Replace the text of an element when a setting changes.
	function bindText( setting, selector, keepWhenEmpty ) {
		api( 'clc-options[' + setting + ']', function ( value ) {
			value.bind( function ( to ) {
				if ( keepWhenEmpty && ! to ) {
					return;
				}
				$( selector ).text( to );
			} );
		} );
	}

	// Replace the value of a submit button when a setting changes.
	function bindButton( setting, selector ) {
		api( 'clc-options[' + setting + ']', function ( value ) {
			value.bind( function ( to ) {
				if ( to ) {
					$( selector ).val( to );
				}
			} );
		} );
	}

	// Swap one body class from a numbered family.
	function bindBodyClass( setting, prefix, count ) {
		api( 'clc-options[' + setting + ']', function ( value ) {
			value.bind( function ( to ) {
				var i, classes = [];

				for ( i = 1; i <= count; i++ ) {
					classes.push( prefix + i );
				}

				$( document.body ).removeClass( classes.join( ' ' ) ).addClass( prefix + to );
			} );
		} );
	}

	/* Layout */
	api( 'clc-options[columns]', function ( value ) {
		value.bind( function ( to ) {
			$( document.body ).toggleClass( 'ml-half-screen', 2 === parseInt( to, 10 ) );
		} );
	} );

	bindBodyClass( 'form-column-align', 'ml-login-align-', 4 );
	bindBodyClass( 'form-vertical-align', 'ml-login-vertical-align-', 3 );
	bindBodyClass( 'form-horizontal-align', 'ml-login-horizontal-align-', 3 );

	api( 'clc-options[columns-width]', function ( value ) {
		value.bind( function ( to ) {
			var css, left, right;

			if ( ! to || undefined === to.left || undefined === to.right ) {
				return;
			}

			left  = ( 100 / 12 ) * parseInt( to.left, 10 );
			right = ( 100 / 12 ) * parseInt( to.right, 10 );

			css  = '.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-1 .ml-container .ml-form-container{ width:' + left + '%; }';
			css += '.ml-half-screen.ml-login-align-4 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-2 .ml-container .ml-form-container{ flex-basis:' + left + '%; }';
			css += '.ml-half-screen.ml-login-align-3 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div{ width:' + right + '%; }';
			css += '.ml-half-screen.ml-login-align-4 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-2 .ml-container .ml-extra-div{ flex-basis:' + right + '%; }';

			$( '#clc-columns-style' ).text( css );
		} );
	} );

	/* Logo */
	api( 'clc-options[logo-settings]', function ( value ) {
		value.bind( function ( to ) {
			$( document.body )
				.toggleClass( 'clc-text-logo', 'show-text-only' === to || 'use-both' === to )
				.toggleClass( 'clc-both-logo', 'use-both' === to );
		} );
	} );

	api( 'clc-options[logo-title]', function ( value ) {
		value.bind( function ( to ) {
			$( '#logo-text' ).text( to );
			$( '#clc-logo-link' ).attr( 'title', to );
		} );
	} );

	api( 'clc-options[logo-url]', function ( value ) {
		value.bind( function ( to ) {
			$( '#clc-logo-link' ).attr( 'href', to );
		} );
	} );

	/* Custom CSS */
	api( 'clc-options[custom-css]', function ( value ) {
		value.bind( function ( to ) {
			$( '#clc-custom-css' ).text( to );
		} );
	} );

	/* Form texts */
	bindText( 'username-label', '#clc-username-label' );
	bindText( 'password-label', '#clc-password-label' );
	bindText( 'rememberme-label', '#clc-rememberme-label' );
	bindText( 'lost-password-text', '#clc-lost-password-text' );
	bindText( 'register-username-label', '#clc-register-username-label' );
	bindText( 'register-email-label', '#clc-register-email-label' );
	bindText( 'register-confirmation-email', '#reg_passmail' );
	bindText( 'lostpassword-username-label', '#clc-lostpassword-username-label' );
	bindText( 'register-link-label', '#register-link-label', true );
	bindText( 'login-link-label', '#login-link-label', true );

	bindButton( 'login-label', '#loginform input[name="wp-submit"]' );
	bindButton( 'register-button-label', '#registerform input[name="wp-submit"]' );
	bindButton( 'lostpassword-button-label', '#lostpasswordform input[name="wp-submit"]' );

	api( 'clc-options[back-to-text]', function ( value ) {
		value.bind( function ( to ) {
			$( '#clc-back-to-text' ).text( '← ' + to );
		} );
	} );

	// Shortcut buttons in the preview open the matching Customizer section.
	$( '.clc-preview-event' ).on( 'click', function ( event ) {
		event.preventDefault();
		event.stopPropagation();
		api.preview.send( 'clc-focus-section', $( this ).data( 'section' ) );
	} );

	api.bind( 'preview-ready', function () {
		api.preview.bind( 'change-form', function ( form ) {
			var forms = [ 'login', 'register', 'lostpassword' ];

			if ( -1 === forms.indexOf( form ) ) {
				form = 'login';
			}

			$.each( forms, function ( index, name ) {
				if ( name !== form ) {
					$( '.show-only_' + name ).hide();
				}
			} );

			$( '.show-only_' + form ).show();
		} );
	} );
}( jQuery, wp.customize, window.CLC ) );
