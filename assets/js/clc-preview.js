( function( $ ) {

  var clcCustomCSS = {
    selectors: {},
    settings: {},
    style: '',
    init: function( settings, selectors ) {
      this.selectors = selectors;
      this.settings = settings;

      this.style = $( '#clc-style' );
      this._binds();
    },
    _binds: function() {
      var self = this;
      $.each( self.settings, function( index, setting ) {
        wp.customize( setting.name, function( value ) {
          value.bind( function( to ) {
            self.settings[ index ].value = to;
            self.createCSSLines();
          } );
        } );
      });
    },
    createCSSLines: function() {
      var style = '',
          self = this;
      $.each( self.selectors, function( index, selector ) {
        var cssLine = index + '{';
        $.each( selector, function( index, option ) {
          cssLine = cssLine + self.generateCSSLine( option );
        });
        style = style + cssLine + '}';
      });

      self.style.html( style );

    },
    generateCSSLine: function( option ) {
        var line = this.settings[ option ].attribute + ':';

        if ( '' === this.settings[ option ].value ) {
          return '';
        }
        if ( undefined === this.settings[ option ].attribute || undefined === this.settings[ option ].value ) {
          return '';
        }

      if ( $.inArray( this.settings[ option ].attribute, [ 'width', 'min-width', 'max-width', 'background-size', 'height', 'min-height', 'max-height', 'font-size' ] ) >= 0 ) {
        line += this.settings[ option ].value + 'px';
      }else if ( 'background-image' === this.settings[ option ].attribute ) {
        line += 'url(' + this.settings[ option ].value + ')';
      }else if ( 'display' === this.settings[ option ].attribute ) {
        if ( this.settings[ option ].value ) {
          line += 'none';
        } else {
          line += 'block';
        }
      } else {
        line += this.settings[ option ].value;
      }
      line += ';';

      return line;
    }
  };

  clcCustomCSS.init( CLC.settings, CLC.selectors );

  // Live edits
  /* Columns */
  wp.customize( 'clc-options[columns]', function( value ) {
    value.bind( function( to ) {
      if ( '2' === to ) {
        $( 'body' ).addClass( 'ml-half-screen' );
      } else {
        $( 'body' ).removeClass( 'ml-half-screen' );
      }
    } );
  } );

  // Add class if we have text logo
  wp.customize( 'clc-options[use-text-logo]', function( value ) {
    value.bind( function( to ) {
      if ( to ) {
        $( 'body' ).addClass( 'clc-text-logo' );
      } else {
        $( 'body' ).removeClass( 'clc-text-logo' );
      }
    } );
  } );

  wp.customize( 'clc-options[logo-text]', function( value ) {
    value.bind( function( to ) {
      $( '#logo-text' ).text( to );
    } );
  } );

  /* Column Align */
  wp.customize( 'clc-options[form-column-align]', function( value ) {
    value.bind( function( to ) {
      $( 'body' ).removeClass( 'ml-login-align-1 ml-login-align-2 ml-login-align-3 ml-login-align-4' );
      $( 'body' ).addClass( 'ml-login-align-' + to );
    } );
  } );

  /* Column Vertical Align */
  wp.customize( 'clc-options[form-vertical-align]', function( value ) {
    value.bind( function( to ) {
      $( 'body' ).removeClass( 'ml-login-vertical-align-1 ml-login-vertical-align-2 ml-login-vertical-align-3' );
      $( 'body' ).addClass( 'ml-login-vertical-align-' + to );
    } );
  } );

  /* Column Horizontal Align */
  wp.customize( 'clc-options[form-horizontal-align]', function( value ) {
    value.bind( function( to ) {
      $( 'body' ).removeClass( 'ml-login-horizontal-align-1 ml-login-horizontal-align-2 ml-login-horizontal-align-3' );
      $( 'body' ).addClass( 'ml-login-horizontal-align-' + to );
    } );
  } );

  // Custom CSS
  wp.customize( 'clc-options[custom-css]', function( value ) {
    value.bind( function( to ) {
      $( '#clc-custom-css' ).text( to );
    } );
  } );

  // Username label
  wp.customize( 'clc-options[username-label]', function( value ) {
    value.bind( function( to ) {
      $( '#clc-username-label' ).text( to );
    } );
  } );

  // Password label
  wp.customize( 'clc-options[password-label]', function( value ) {
    value.bind( function( to ) {
      $( '#clc-password-label' ).text( to );
    } );
  } );

  // Columns width
  wp.customize( 'clc-options[columns-width]', function( value ) {
    value.bind( function( to ) {
      var customCSS = '',
          leftWidth,
          rightWidth;
      if ( '' !== to && undefined !== to.left && undefined !== to.right ) {
        leftWidth = ( 100 / 12 )*parseInt( to.left, 10 );
        rightWidth = ( 100 / 12 )*parseInt( to.right, 10 );
        customCSS = '.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-1 .ml-container .ml-form-container{ width:' + leftWidth + '%; }';
        customCSS += '.ml-half-screen.ml-login-align-4 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-2 .ml-container .ml-form-container{ flex-basis:' + leftWidth + '%; }';

        customCSS += '.ml-half-screen.ml-login-align-3 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div{ width:' + rightWidth + '%; }';
        customCSS += '.ml-half-screen.ml-login-align-4 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-2 .ml-container .ml-extra-div{ flex-basis:' + rightWidth + '%; }';

        $( '#clc-columns-style' ).text( customCSS );

      }
    } );
  } );

  $( '.clc-preview-event' ).click( function() {
    wp.customize.preview.send( 'clc-focus-section', $( this ).data( 'section' ) );
  } );

})( jQuery );
