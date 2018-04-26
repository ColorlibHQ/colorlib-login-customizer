(function( $ ) {

  var clcCustomCSS = {
    selectors : {},
    settings : {},
    style : '',
    generateCSS : function(){

    },
    init : function( settings, selectors ) {
      this.selectors = selectors;
      this.settings = settings;

      this.style = $( '#clc-style' );
      this._binds();
    },
    _binds : function() {
      var self = this;
      $.each( self.settings, function( index, setting ){
        wp.customize( setting['name'], function( value ) {
          value.bind( function( to ) {
            self.settings[ index ]['value'] = to;
            self.createCSSLines();
          } );
        } );
      });
    },
    createCSSLines : function() {
      var style = '',
          self = this;
        console.log( self.settings );
      $.each( self.selectors, function( index, selector ){
        var cssLine = index + '{';
        $.each( selector, function( index, option ){
          cssLine = cssLine + self.generateCSSLine( option );
        });
        style = style + cssLine + '}';
      });

      console.log( style );

      self.style.html( style );

    },
    generateCSSLine : function( option ) {

      if ( '' == this.settings[ option ]['value'] ) { console.log( option ); return ''; }
      if ( undefined == this.settings[ option ]['attribute'] || undefined == this.settings[ option ]['value'] ) { 
        console.log( 'is_undefined' );
        return '';
      }

      var line = this.settings[ option ]['attribute'] + ':';
      if ( $.inArray( this.settings[ option ]['attribute'], [ 'width', 'min-width', 'max-width', 'background-size', 'height', 'min-height', 'max-height' ] ) >= 0 ) {
        line += this.settings[ option ]['value'] + 'px';
      }else if ( 'background-image' == this.settings[ option ]['attribute'] ) {
        line += 'url(' + this.settings[ option ]['value'] + ')';
      }else{
        line += this.settings[ option ]['value'];
      }
      line += ';';

      return line;
    }
  };

  clcCustomCSS.init( CLC.settings, CLC.selectors );

  // Live edits
  /* Templates */
  wp.customize( 'clc-options[templates]', function( value ) {
    value.bind( function( to ) {

      if ( '01' === to ) {
        $( 'body' ).addClass( 'ml-half-screen' );
      } else {
        $( 'body' ).removeClass( 'ml-half-screen' );
      }
    } );
  } );

  $( '.clc-preview-event' ).click( function() {
    wp.customize.preview.send( 'clc-focus-section', $( this ).data( 'section' ) );
  } );

})( jQuery );
