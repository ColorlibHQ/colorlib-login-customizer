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

      if ( $.inArray( this.settings[ option ].attribute, [ 'width', 'min-width', 'max-width', 'background-size', 'height', 'min-height', 'max-height' ] ) >= 0 ) {
        line += this.settings[ option ].value + 'px';
      }else if ( 'background-image' === this.settings[ option ].attribute ) {
        line += 'url(' + this.settings[ option ].value + ')';
      }else if ( 'display' === this.settings[ option ].attribute ) {
        if ( '1' === this.settings[ option ].value ) {
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

  $( '.clc-preview-event' ).click( function() {
    wp.customize.preview.send( 'clc-focus-section', $( this ).data( 'section' ) );
  } );

})( jQuery );
