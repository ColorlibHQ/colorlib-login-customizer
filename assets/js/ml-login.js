(function( $ ) {
  'use strict';

  if ( 'undefined' === typeof( EpsilonFramework ) ) {
    var EpsilonFramework = {};
  }

  EpsilonFramework.predefinedSchemes = {
    /**
     * Register the container, scoping purposes
     */
    context: null,
    /**
     * Register api
     */
    api: null,
    /**
     * Option prefix
     */
    handle: null,
    /**
     * Macho Login Predefined Schemes Init
     *
     * @private
     */
    init: function( context, handle ) {
      this.context = context;
      this.api = wp.customize;
      this.handle = handle;
      this.handleAnchorClicks();
    },

    /**
     * Handle the anchor clicks
     */
    handleAnchorClicks: function() {
      var anchors = this.context.find( '.ml-color-schemes-list a' ),
          self = this,
          json;

      $.each( anchors, function() {
        $( this ).click( function( e ) {
          e.preventDefault();
          json = $.parseJSON( $( this ).attr( 'data-scheme-json' ) );
          self._setOptions( json );
        } );
      } );
    },

    /**
     * Set customizer options based on a JSON
     */
    _setOptions: function( json ) {
      var api = this.api,
          self = this;
      /**
       * Find the customizer options
       */
      $.each( json, function( index, value ) {
        api( self.handle + index ).set( value );
      } );
    }
  };

  if ( 'undefined' !== typeof( wp ) && 'undefined' !== typeof(wp.customize) ) {
    wp.customize.sectionConstructor[ 'epsilon-section-predefined-schemes' ] = wp.customize.Section.extend(
        {
          ready: function() {
            EpsilonFramework.predefinedSchemes.init(
                $( '#macho-login-color-schemes' ),
                'ml_'
            );
          },

          isContextuallyActive: function() {
            return true;
          }
        } );
  }
})( jQuery );