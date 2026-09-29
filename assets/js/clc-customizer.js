( function ( $, api ) {
	'use strict';

	if ( 'undefined' === typeof api ) {
		return;
	}

	var urls = window.CLCUrls || {};

	/*
	 * Show only the logo controls that apply to the chosen logo mode. The
	 * server-side active_callbacks cover the initial state; this keeps the
	 * panel in step while the user switches modes.
	 */
	function syncLogoControls( mode ) {
		var controls = {
			    textColor:      api.control( 'clc-options[logo-text-color]' ),
			    textColorHover: api.control( 'clc-options[logo-text-color-hover]' ),
			    textSize:       api.control( 'clc-options[logo-text-size]' ),
			    image:          api.control( 'clc-options[custom-logo]' ),
			    width:          api.control( 'clc-options[logo-width]' ),
			    height:         api.control( 'clc-options[logo-height]' ),
			    title:          api.control( 'clc-options[logo-title]' ),
			    url:            api.control( 'clc-options[logo-url]' )
		    },
		    hidden = 'hide-logo' === mode,
		    text   = 'show-text-only' === mode || 'use-both' === mode,
		    image  = 'show-image-only' === mode || 'use-both' === mode;

		$.each( controls, function ( name, control ) {
			var visible;

			if ( ! control ) {
				return;
			}

			if ( hidden ) {
				visible = false;
			} else if ( 'textColor' === name || 'textColorHover' === name || 'textSize' === name ) {
				visible = text;
			} else if ( 'image' === name || 'width' === name || 'height' === name ) {
				visible = image;
			} else {
				visible = true;
			}

			control.toggle( visible );
		} );
	}

	// Switch the preview to the login page while the plugin's panel is open,
	// and put the visitor back where they were when it closes.
	api.panel( 'clc_main_panel', function ( panel ) {
		var previousUrl = '';

		panel.expanded.bind( function ( isExpanded ) {
			var previewUrl = api.previewer.previewUrl;

			if ( isExpanded ) {
				if ( previewUrl.get() !== urls.previewUrl ) {
					previousUrl = previewUrl.get();
				}
				previewUrl.set( urls.previewUrl );
			} else {
				previewUrl.set( previousUrl || api.settings.url.home );
			}
		} );
	} );

	api.section( 'clc_logo', function ( section ) {
		section.expanded.bind( function ( isExpanded ) {
			if ( isExpanded ) {
				syncLogoControls( api( 'clc-options[logo-settings]' ).get() );
			}
		} );
	} );

	// Show the matching form in the preview while its texts are being edited.
	$.each( { 'clc_register-form': 'register', 'clc_lostpassword-form': 'lostpassword' }, function ( sectionId, form ) {
		api.section( sectionId, function ( section ) {
			section.expanded.bind( function ( isExpanded ) {
				api.previewer.send( 'change-form', isExpanded ? form : 'login' );
			} );
		} );
	} );

	api.controlConstructor[ 'clc-templates' ] = api.Control.extend( {
		ready: function () {
			var control = this;

			control.container.find( 'input:radio[value="' + control.setting.get() + '"]' ).prop( 'checked', true );

			control.container.on( 'change', 'input:radio', function () {
				var template = $( this ).val();

				control.loadTemplate( 'default' );

				if ( 'default' !== template ) {
					control.loadTemplate( template );
				}

				// Remember the choice so the selection survives a reload.
				control.setting.set( template );
			} );
		},

		loadTemplate: function ( optionName ) {
			var options = this.params.options[ optionName ];

			$.each( options, function ( index, option ) {
				var currentControl;

				// #172: switching template runs a 'default' reset first; don't let
				// it wipe the user's uploaded logo. Templates only ever reset
				// custom-logo (never set a real value), so preserving it here keeps
				// the logo across template changes.
				if ( 'default' === optionName && 'clc-options[custom-logo]' === option.name ) {
					return true;
				}

				currentControl = api.control( option.name );

				if ( currentControl ) {
					currentControl.setting( option.value );
				}
			} );
		}
	} );

	api.controlConstructor[ 'clc-button-group' ] = api.Control.extend( {
		ready: function () {
			var control = this;

			function markActive( value ) {
				control.container.find( '.colorlib-login-customizer-control-group > a' ).each( function () {
					var active = String( $( this ).attr( 'data-value' ) ) === String( value );

					$( this ).toggleClass( 'active', active ).attr( 'aria-pressed', active ? 'true' : 'false' );
				} );
			}

			control.container.on( 'click', '.colorlib-login-customizer-control-group > a', function ( event ) {
				event.preventDefault();
				control.setting.set( $( this ).attr( 'data-value' ) );
			} );

			control.setting.bind( markActive );
			markActive( control.setting.get() );
		}
	} );

	api.controlConstructor[ 'clc-column-width' ] = api.Control.extend( {
		ready: function () {
			var control = this;

			control.values = control.normalize( control.params.value );

			control.container.on( 'click', '.clc-layouts-setup .clc-column > a', function ( event ) {
				event.preventDefault();
				control.updateColumns( $( this ).data( 'action' ) );
			} );

			control.setting.bind( function ( value ) {
				control.values = control.normalize( value );
				control.renderColumns();
			} );
		},

		// Always work on integers that add up to 12, whatever was stored.
		normalize: function ( value ) {
			var left = value ? parseInt( value.left, 10 ) : NaN;

			if ( isNaN( left ) || left < 1 || left > 11 ) {
				left = 6;
			}

			return { left: left, right: 12 - left };
		},

		updateColumns: function ( grow ) {
			var values = $.extend( {}, this.values ),
			    shrink = 'left' === grow ? 'right' : 'left';

			if ( values[ shrink ] <= 1 ) {
				return;
			}

			values[ grow ] += 1;
			values[ shrink ] -= 1;

			// A new object, so the setting registers the change.
			this.setting.set( values );
		},

		renderColumns: function () {
			var classes = 'col12 col11 col10 col9 col8 col7 col6 col5 col4 col3 col2 col1';

			this.container.find( '.clc-column-left' ).removeClass( classes ).addClass( 'col' + this.values.left );
			this.container.find( '.clc-column-right' ).removeClass( classes ).addClass( 'col' + this.values.right );
		}
	} );

	api.bind( 'ready', function () {

		api.previewer.bind( 'clc-focus-section', function ( sectionName ) {
			var section = api.section( sectionName );

			if ( section ) {
				section.focus();
			}
		} );

		api( 'clc-options[columns]', function ( setting ) {
			setting.bind( function ( to ) {
				var twoColumns = 2 === parseInt( to, 10 );

				$.each( [ 'form-column-align', 'custom-background-form', 'columns-width', 'custom-background-color-form' ], function ( index, id ) {
					var control = api.control( 'clc-options[' + id + ']' );

					if ( control ) {
						control.toggle( twoColumns );
					}
				} );
			} );
		} );

		// An empty button label is allowed (the page keeps WordPress's own
		// text), but say so rather than leave the user guessing.
		$.each( [ 'login-label', 'register-button-label', 'lostpassword-button-label' ], function ( index, id ) {
			api( 'clc-options[' + id + ']', function ( setting ) {
				function check( value ) {
					if ( value ) {
						setting.notifications.remove( 'clc-empty-label' );
					} else {
						setting.notifications.add( new api.Notification( 'clc-empty-label', {
							type:    'info',
							message: urls.emptyLabel
						} ) );
					}
				}

				setting.bind( check );
				check( setting.get() );
			} );
		} );

		api( 'clc-options[logo-settings]', function ( setting ) {
			setting.bind( syncLogoControls );
		} );
	} );
}( jQuery, window.wp && window.wp.customize ) );
