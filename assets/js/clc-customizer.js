(function( $ ) {
  'use strict';

  if ( 'undefined' !== typeof( wp ) && 'undefined' !== typeof(wp.customize) ) {

    // Detect when the templates section is expanded (or closed) so we can hide the templates shortcut when it's open.
    wp.customize.panel( 'clc_main_panel', function( section ) {
      section.expanded.bind( function( isExpanding ) {

        // Value of isExpanding will = true if you're entering the section, false if you're leaving it.
        if ( isExpanding ) {
          wp.customize.previewer.previewUrl.set( CLCUrls.siteurl + '?colorlib-login-customizer-customization=true' );
        } else {
          wp.customize.previewer.previewUrl.set( CLCUrls.siteurl );
        }
      } );
    } );

    wp.customize.controlConstructor['colorlib-login-customizer-templates'] = wp.customize.Control.extend( {
      ready: function() {
        var control = this;

        this.container.on( 'change', 'input:radio', function() {
          control.setting.set( $( this ).val() );
        } );
      }
    } );

    wp.customize.controlConstructor['clc-range-slider'] = wp.customize.Control.extend({
      ready: function() {
        var control = this,
          controlField = control.container.find( 'input.clc-slider' ),
          controlSlider = control.container.find( 'div.clc-slider' ),
          controlSliderData = control.params.choices;

        controlSlider.slider({
          range: 'min',
          min: controlSliderData.min,
          max: controlSliderData.max,
          step: controlSliderData.step,
          value: controlField.val(),
          slide: function( event, ui ) {
            controlField.val( ui.value ).keyup();
          },
          stop: function( event, ui ) {
            controlField.val( ui.value );
            control.setting.set( ui.value );
          }
        });
      }
    });

    // Listen for previewer events
    wp.customize.bind( 'ready', function() {
      wp.customize.previewer.bind( 'clc-focus-section', function( section_name ) {
        var section = wp.customize.section( section_name );
        
        if ( undefined !== section ) {
          section.focus();
        }
      });
    });

  }
})( jQuery );


